<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'atleta') {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar informações detalhadas do atleta
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$atleta = $stmt->get_result()->fetch_assoc();

// Buscar documentos médicos do atleta
$stmt = $conn->prepare('SELECT * FROM documentos_medicos WHERE atleta_id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$docs = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil Atleta - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo !empty($atleta['foto_perfil']) ? '../' . $atleta['foto_perfil'] : 'img/default-avatar.png'; ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($atleta['nome']); ?></h3>
                <p>CIPA: <?php echo htmlspecialchars($atleta['cip'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'dashboard_atleta.php' ? ' active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'perfil_atleta.php' ? ' active' : '' ?>">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="treinos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'treinos.php' ? ' active' : '' ?>">
                    <i class="fas fa-dumbbell"></i> Treinos
                </a>
                <a href="jogos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'jogos.php' ? ' active' : '' ?>">
                    <i class="fas fa-futbol"></i> Jogos
                </a>
                <a href="mensagens.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'mensagens.php' ? ' active' : '' ?>">
                    <i class="fas fa-envelope"></i> Mensagens
                </a>
                <a href="pagamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'pagamentos.php' ? ' active' : '' ?>">
                    <i class="fas fa-euro-sign"></i> Pagamentos
                </a>
                <a href="atleta_equipamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'atleta_equipamentos.php' ? ' active' : '' ?>">
                    <i class="fas fa-tshirt"></i> Equipamentos
                </a>
            </div>

            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <div class="profile-container">
                <form id="profile-edit-form" action="perfil_atleta_save.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $atleta['id']; ?>">

                    <!-- Cabeçalho do Perfil com a foto -->
                    <div class="profile-header">
                        <div class="profile-cover">
                            <div class="profile-photo-container">
                                <img src="<?php echo !empty($atleta['foto_perfil']) ? '../' . $atleta['foto_perfil'] : 'img/default-avatar.png'; ?>" alt="Foto de Perfil" class="profile-photo" id="profile-actual-photo">
                                <input type="file" id="profile-photo-upload" name="profile-photo-upload" accept="image/*" style="display: none;">
                                <div class="photo-buttons">
                                    <button type="button" class="change-photo-btn" id="change-photo-btn">
                                        <i class="fas fa-camera"></i> Alterar Foto
                                    </button>
                                    <button type="submit" class="save-photo-btn" id="save-photo-btn" style="display: none;">
                                        <i class="fas fa-save"></i> Guardar Foto
                                    </button>
                                    <button type="button" class="cancel-photo-btn" id="cancel-photo-btn" style="display: none;">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="profile-info-header">
                            <h1><?php echo htmlspecialchars($atleta['nome']); ?></h1>
                            <p>Atleta #<?php echo $atleta['id']; ?></p>
                        </div>
                    </div>

                    <!-- Informações do Perfil -->
                    <div class="profile-content">
                        <div class="profile-section">
                            <h2>Informações Pessoais</h2>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label for="nome">Nome Completo</label>
                                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($atleta['nome']); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="data_nascimento">Data de Nascimento</label>
                                    <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo $atleta['data_nascimento'] ?? ''; ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($atleta['email']); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="telefone">Telefone</label>
                                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($atleta['telefone'] ?? ''); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="nif">NIF</label>
                                    <input type="text" id="nif" name="nif" value="<?php echo htmlspecialchars($atleta['nif'] ?? ''); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="morada">Morada</label>
                                    <input type="text" id="morada" name="morada" value="<?php echo htmlspecialchars($atleta['morada'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <button type="button" class="edit-btn" id="edit-personal-btn">
                                <i class="fas fa-edit"></i> Editar Informações Pessoais
                            </button>
                            <div class="save-cancel-buttons personal" style="display: none;">
                                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Guardar Alterações</button>
                                <button type="button" class="btn-secondary cancel-edit-btn"><i class="fas fa-times-circle"></i> Cancelar</button>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h2>Informações Desportivas</h2>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label for="escalao">Escalão</label>
                                    <input type="text" id="escalao" name="escalao" value="<?php echo htmlspecialchars($atleta['escalao'] ?? ''); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="posicao">Posição</label>
                                    <input type="text" id="posicao" name="posicao" value="<?php echo htmlspecialchars($atleta['posicao'] ?? ''); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="numero">Número</label>
                                    <input type="number" id="numero" name="numero" value="<?php echo htmlspecialchars($atleta['numero'] ?? ''); ?>" disabled>
                                </div>
                                <div class="info-item">
                                    <label for="pe_dominante">Lateralidade</label>
                                    <input type="text" id="pe_dominante" name="pe_dominante" value="<?php echo htmlspecialchars($atleta['pe_dominante'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h2>Dados Médicos</h2>
                            <div class="medicos-list">
                            <?php while ($doc = $docs->fetch_assoc()): ?>
                                <div class="medico-item">
                                    <strong><?php echo $doc['tipo'] === 'exame' ? 'Exame Médico Desportivo' : 'Seguro Desportivo'; ?></strong><br>
                                    <?php if ($doc['tipo'] === 'exame'): ?>
                                        Válido até: <?php echo htmlspecialchars($doc['validade']); ?>
                                    <?php else: ?>
                                        Apólice: <?php echo htmlspecialchars($doc['apolice']); ?>
                                    <?php endif; ?>
                                    <a href="download_documento.php?id=<?php echo $doc['id']; ?>" target="_blank" style="float:right;font-size:1.3em;color:#0a3997;"><i class="fas fa-download"></i></a>
                                </div>
                            <?php endwhile; ?>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h2>Segurança</h2>
                            <div class="security-options">
                                <button type="button" class="change-password-btn">
                                    <i class="fas fa-lock"></i>
                                    Alterar Palavra-passe
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const changePhotoBtn = document.getElementById('change-photo-btn');
            const savePhotoBtn = document.getElementById('save-photo-btn');
            const cancelPhotoBtn = document.getElementById('cancel-photo-btn');
            const photoInput = document.getElementById('profile-photo-upload');
            const profilePhoto = document.getElementById('profile-actual-photo');
            const sidebarPhoto = document.querySelector('.sidebar-header img');
            const profileForm = document.getElementById('profile-edit-form');
            let originalPhotoSrc = profilePhoto.src;

            changePhotoBtn.addEventListener('click', function() {
                photoInput.click();
            });

            photoInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // Mostrar preview da imagem
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePhoto.src = e.target.result;
                        sidebarPhoto.src = e.target.result;
                    }
                    reader.readAsDataURL(this.files[0]);

                    // Mostrar botões de salvar e cancelar e esconder o de alterar
                    savePhotoBtn.style.display = 'flex';
                    cancelPhotoBtn.style.display = 'flex';
                    changePhotoBtn.style.display = 'none';
                }
            });

            // savePhotoBtn agora é do tipo submit e submete o formulário

            cancelPhotoBtn.addEventListener('click', function() {
                // Restaurar a foto original
                profilePhoto.src = originalPhotoSrc;
                sidebarPhoto.src = originalPhotoSrc;
                
                // Limpar o input de arquivo
                photoInput.value = '';
                
                // Esconder botões de salvar e cancelar e mostrar o de alterar
                savePhotoBtn.style.display = 'none';
                cancelPhotoBtn.style.display = 'none';
                changePhotoBtn.style.display = 'flex';
            });

            // Habilitar edição de informações pessoais
            document.getElementById('edit-personal-btn').addEventListener('click', function() {
                const formElements = document.querySelectorAll('#profile-edit-form .info-grid input:disabled');
                formElements.forEach(element => {
                    element.disabled = false;
                });
                this.style.display = 'none';
                document.querySelector('.save-cancel-buttons.personal').style.display = 'flex';
            });

            // Cancelar edição de informações pessoais
            document.querySelector('.cancel-edit-btn').addEventListener('click', function() {
                const formElements = document.querySelectorAll('#profile-edit-form .info-grid input:not([disabled])');
                formElements.forEach(element => {
                    element.disabled = true;
                });
                document.getElementById('edit-personal-btn').style.display = 'flex';
                document.querySelector('.save-cancel-buttons.personal').style.display = 'none';
            });

            // Verificar se há mensagem de erro ou sucesso na URL
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const success = urlParams.get('success');

            if (error) {
                alert('Erro: ' + error);
            } else if (success === 'profile_updated') {
                alert('Perfil atualizado com sucesso!');
            } else if (success === 'palavra_passe_atualizada') {
                alert('Palavra-passe alterada com sucesso!');
            }

            // Funcionalidade do modal de alteração de senha
            const modal = document.getElementById('changePasswordModal');
            const changePasswordBtn = document.querySelector('.change-password-btn');
            const closeBtn = document.querySelector('.close');
            const cancelBtn = document.getElementById('cancelPasswordChange');
            const passwordForm = document.getElementById('change-password-form');

            // Abrir modal
            changePasswordBtn.addEventListener('click', function() {
                modal.style.display = 'block';
            });

            // Fechar modal
            function closeModal() {
                modal.style.display = 'none';
                passwordForm.reset();
            }

            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            });

            // Validação do formulário
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;

                if (newPassword !== confirmPassword) {
                    alert('As palavras-passe não coincidem!');
                    return;
                }

                if (newPassword.length < 8) {
                    alert('A nova palavra-passe deve ter pelo menos 8 caracteres!');
                    return;
                }

                this.submit();
            });
        });
    </script>

    <!-- Modal de Alteração de Senha -->
    <div id="changePasswordModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Alterar Palavra-passe</h2>
                <span class="close">&times;</span>
            </div>
            <form id="change-password-form" action="change_password.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="current_password">Palavra-passe Atual</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Nova Palavra-passe</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirmar Nova Palavra-passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary" id="cancelPasswordChange">Cancelar</button>
                    <button type="submit" class="btn-primary">Guardar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        /* Adicionar um wrapper para o cabeçalho e a foto para gerir o layout */
        .profile-header-and-photo-wrapper {
            position: relative;
            margin-bottom: 30px; /* Adicionar margem inferior para separar do conteúdo */
        }

        .profile-header {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            /* margin-bottom foi movido para .profile-header-and-photo-wrapper */
            text-align: left;
        }

        .profile-cover {
            background: linear-gradient(135deg, #004080 0%, #002b57 100%);
            height: 200px;
            position: relative;
        }

        .profile-photo-container {
            position: absolute;
            bottom: -50px;
            left: 25px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
            z-index: 10; /* Garante que a foto e botões fiquem acima do cabeçalho */
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            margin: 0;
        }

        .photo-buttons {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            justify-content: flex-start;
            width: auto; /* Ajusta a largura ao conteúdo */
            margin-left: 0;
            margin-right: 0;
            position: relative; /* Ajusta posicionamento para o flex */
            left: 0; /* Garante alinhamento a 0 */
        }

        .change-photo-btn, .save-photo-btn, .cancel-photo-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .change-photo-btn {
            background-color: #4CAF50;
            color: white;
        }

        .save-photo-btn {
            background-color: #2196F3;
            color: white;
        }

        .cancel-photo-btn {
            background-color: #f44336;
            color: white;
        }

        .change-photo-btn:hover, .save-photo-btn:hover, .cancel-photo-btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .profile-info-header {
            padding: 100px 25px 30px; /* Ajustado padding-top e padding-left para alinhar */
        }

        .profile-info-header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
        }

        .profile-info-header p {
            color: #666;
            font-size: 14px;
        }

        /* Conteúdo do Perfil */
        .profile-content {
            display: grid;
            gap: 30px;
        }

        .profile-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-section h2 {
            color: #004080;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* Grid de Informações */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .info-item p {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        /* Add styling for input fields within info-item */
        .info-item input[type="text"],
        .info-item input[type="date"],
        .info-item input[type="email"],
        .info-item input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            color: #333;
            transition: border-color 0.3s ease;
        }

        .info-item input:disabled {
            background-color: #e9ecef;
            opacity: 1;
            cursor: not-allowed;
        }

        .info-item input:focus:not(:disabled) {
            border-color: #004080;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 64, 128, 0.25);
        }

        /* Botões */
        .edit-btn, .change-password-btn, .enable-2fa-btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .edit-btn {
            background: #004080;
            color: white;
            margin-top: 20px;
        }

        .edit-btn:hover {
            background: #003366;
            transform: translateY(-2px);
        }

        /* Informações Médicas */
        .medicos-list {
            display: grid;
            gap: 15px;
        }

        .medico-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .medico-item i {
            font-size: 24px;
            color: #004080;
        }

        .medico-item h3 {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .medico-item p {
            font-size: 12px;
            color: #666;
        }

        .btn-download {
            margin-left: auto;
            color: #004080;
            text-decoration: none;
        }

        /* Opções de Segurança */
        .security-options {
            display: flex;
            gap: 15px;
        }

        .change-password-btn {
            background: #f8f9fa;
            color: #004080;
            border: 1px solid #004080;
        }

        .enable-2fa-btn {
            background: #f8f9fa;
            color: #004080;
            border: 1px solid #004080;
        }

        .change-password-btn:hover, .enable-2fa-btn:hover {
            background: #e2e6ea;
            transform: translateY(-2px);
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .dashboard-content {
                margin-left: 0;
                padding: 20px;
            }

            .profile-header {
                border-radius: 0;
            }

            .profile-photo-container {
                left: 50%;
                transform: translateX(-50%);
            }

            .profile-info-header {
                padding: 60px 20px 20px;
                text-align: center;
            }

            .security-options {
                flex-direction: column;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Estilos do Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 0;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            padding: 15px 20px;
            background-color: #004080;
            color: white;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            color: white;
            font-size: 20px;
            border: none;
            padding: 0;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #ddd;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus {
            border-color: #004080;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(0, 64, 128, 0.25);
        }

        .btn-primary {
            background-color: #004080;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary:hover, .btn-secondary:hover {
            opacity: 0.9;
        }
        .dashboard-layout {
            display: flex !important;
            min-height: 100vh;
            background: #f4f6fb;
        }
        .dashboard-sidebar {
            width: 270px !important;
            background: linear-gradient(135deg, #004080 0%, #002b57 100%) !important;
            color: #fff !important;
            min-height: 100vh !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: stretch !important;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
        }
        .sidebar-header {
            padding: 40px 20px 20px 20px !important;
            text-align: center !important;
        }
        .sidebar-header img {
            width: 110px !important;
            height: 110px !important;
            border-radius: 50% !important;
            border: 4px solid #fff !important;
            margin-bottom: 10px !important;
            object-fit: cover !important;
        }
        .sidebar-header h3 {
            color: #fff !important;
            font-size: 1.3em !important;
            margin: 0 0 5px 0 !important;
        }
        .sidebar-header p {
            color: #b3c6e0 !important;
            font-size: 1em !important;
            margin: 0 !important;
        }
        .sidebar-menu {
            display: flex !important;
            flex-direction: column !important;
            gap: 5px !important;
            margin-top: 30px !important;
        }
        .sidebar-menu .menu-item {
            color: #fff !important;
            padding: 14px 30px !important;
            text-decoration: none !important;
            font-size: 1.08em !important;
            border: none !important;
            background: none !important;
            border-radius: 8px !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            transition: background 0.2s !important;
        }
        .sidebar-menu .menu-item.active, .sidebar-menu .menu-item:hover {
            background: rgba(255,255,255,0.12) !important;
        }
        .logout-section {
            margin-top: auto !important;
            padding: 30px 20px !important;
        }
        .logout-btn {
            width: 100% !important;
            background: #dc3545 !important;
            color: #fff !important;
            border: none !important;
            border-radius: 8px !important;
            padding: 12px 0 !important;
            font-size: 1.1em !important;
            font-weight: 600 !important;
            cursor: pointer !important;
            transition: background 0.2s !important;
        }
        .logout-btn:hover {
            background: #b52a37 !important;
        }
        @media (max-width: 900px) {
            .dashboard-layout {
                flex-direction: column !important;
            }
            .dashboard-sidebar {
                width: 100% !important;
                min-height: auto !important;
                flex-direction: row !important;
                overflow-x: auto !important;
            }
            .sidebar-menu {
                flex-direction: row !important;
                gap: 0 !important;
                margin-top: 0 !important;
            }
            .sidebar-menu .menu-item {
                padding: 10px 12px !important;
                font-size: 1em !important;
            }
        }
        .dashboard-content {
            flex: 1 1 0%;
            padding: 40px 30px;
            min-width: 0;
        }
    </style>
</body>
</html> 