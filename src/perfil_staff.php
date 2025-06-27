<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Verificar se a conexão com a base de dados foi estabelecida
if (!$conn) {
    $_SESSION['alert'] = ['message' => 'Erro na conexão com a base de dados.', 'type' => 'danger'];
    header("Location: perfil_staff.php");
    exit();
}

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $email = $_POST['email'];
    $telefone = $_POST['telefone'] ?? null;
    // Get other fields if added to the form

    $update_fields = [];
    $bind_params = '';
    $bind_values = [];

    // Add fields to update based on form input
    $update_fields[] = 'email = ?';
    $bind_params .= 's';
    $bind_values[] = $email;

    if ($telefone !== null) {
        $update_fields[] = 'telefone = ?';
        $bind_params .= 's';
        $bind_values[] = $telefone;
    }
    
    // Handle photo upload
    $foto_perfil_path = null;
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "../uploads/";
        // Generate a unique filename
        $imageFileType = strtolower(pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $new_filename = 'perfil_' . $user_id . '_' . time() . '.' . $imageFileType;
        $target_file = $target_dir . $new_filename;

        // Check if file is an actual image or fake image
        $check = getimagesize($_FILES['foto_perfil']['tmp_name']);
        if($check !== false) {
            // Try to move the uploaded file
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $target_file)) {
                $foto_perfil_path = $target_dir . $new_filename; // Store relative path
                $update_fields[] = 'foto_perfil = ?';
                $bind_params .= 's';
                $bind_values[] = $foto_perfil_path;
            } else {
                $_SESSION['alert'] = ['message' => 'Erro ao fazer upload da imagem.', 'type' => 'danger'];
                header("Location: perfil_staff.php");
                exit();
            }
        } else {
            $_SESSION['alert'] = ['message' => 'O ficheiro não é uma imagem.', 'type' => 'danger'];
            header("Location: perfil_staff.php");
            exit();
        }
    }

    if (!empty($update_fields)) {
        $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            // Add user_id to bind values
            $bind_params .= 'i';
            $bind_values[] = $user_id;

            // Dynamically bind parameters
            $stmt->bind_param($bind_params, ...$bind_values);

            if ($stmt->execute()) {
                $_SESSION['alert'] = ['message' => 'Perfil atualizado com sucesso!', 'type' => 'success'];
                // Update session data if necessary
                // Fetch the updated staff data to refresh session variables accurately
                $stmt_fetch = $conn->prepare("SELECT * FROM users WHERE id = ?");
                if ($stmt_fetch) {
                    $stmt_fetch->bind_param("i", $user_id);
                    $stmt_fetch->execute();
                    $_SESSION['user'] = $stmt_fetch->get_result()->fetch_assoc();
                    $stmt_fetch->close();
                }

            } else {
                $_SESSION['alert'] = ['message' => 'Erro ao atualizar o perfil: ' . $stmt->error, 'type' => 'danger'];
            }
            $stmt->close();
        } else {
            $_SESSION['alert'] = ['message' => 'Erro na preparação da query: ' . $conn->error, 'type' => 'danger'];
        }
    }

    $conn->close();
    header("Location: perfil_staff.php");
    exit();
}

// Processar atualização de senha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Buscar a senha hashed atual do utilizador
    $select_password_query = "SELECT password_hash FROM users WHERE id = ?";
    $stmt = $conn->prepare($select_password_query);
    if (!$stmt) {
        $_SESSION['alert'] = ['message' => 'Erro na preparação da query (seleção de senha): ' . $conn->error . ' Query: ' . $select_password_query, 'type' => 'danger'];
        header("Location: perfil_staff.php");
        exit();
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password) {
            // Validar complexidade da nova senha (pode adicionar mais regras aqui)
            if (strlen($new_password) >= 6) { // Exemplo: senha mínima de 6 caracteres
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_password_query = "UPDATE users SET password_hash = ? WHERE id = ?";
                $stmt_update = $conn->prepare($update_password_query);
                if (!$stmt_update) {
                    $_SESSION['alert'] = ['message' => 'Erro na preparação da query (atualização de senha): ' . $conn->error . ' Query: ' . $update_password_query, 'type' => 'danger'];
                    header("Location: perfil_staff.php");
                    exit();
                }
                $stmt_update->bind_param("si", $hashed_password, $user_id);

                if ($stmt_update->execute()) {
                    $_SESSION['alert'] = ['message' => 'Senha atualizada com sucesso!', 'type' => 'success'];
                } else {
                    $_SESSION['alert'] = ['message' => 'Erro ao atualizar a senha: ' . $stmt_update->error, 'type' => 'danger'];
                }
                $stmt_update->close();
            } else {
                $_SESSION['alert'] = ['message' => 'A nova senha deve ter pelo menos 6 caracteres.', 'type' => 'danger'];
            }
        } else {
            $_SESSION['alert'] = ['message' => 'A nova senha e a confirmação não correspondem.', 'type' => 'danger'];
        }
    } else {
        $_SESSION['alert'] = ['message' => 'A senha atual está incorreta.', 'type' => 'danger'];
    }

    $conn->close();
    header("Location: perfil_staff.php");
    exit();
}

// Buscar informações do staff
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// --- Adicionar este bloco para buscar e mostrar o alerta da sessão ---
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert']['message'];
    $alert_type = $_SESSION['alert']['type'];
    // Usar JS para mostrar o alerta depois que a página carregar
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showAlert('" . addslashes($alert_message) . "', '" . addslashes($alert_type) . "'); });</script>";
    unset($_SESSION['alert']); // Limpar a sessão para não mostrar novamente
}
// --- Fim do bloco do alerta da sessão ---
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil Staff - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f6fb;
        }

        .dashboard-content {
            padding: 40px 30px 30px 30px;
            margin-left: 280px;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .profile-sidebar {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            overflow: hidden;
        }

        .profile-header {
            padding: 30px;
            text-align: center;
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 100%);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .profile-image-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 10px auto;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            object-position: center;
        }

        .profile-image-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #1976d2;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
            border: 2px solid white;
        }

        .profile-image-edit:hover {
            background: #1565c0;
            transform: scale(1.1);
        }

        .profile-name {
            font-size: 1.8em;
            margin: 0 0 5px;
            font-weight: 600;
        }

        .profile-role {
            font-size: 1.1em;
            opacity: 0.9;
            margin: 0;
        }

        .profile-stats {
            padding: 20px;
            border-bottom: 1px solid #e3e6f0;
        }

        .stat-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            color: #333;
        }

        .stat-item i {
            width: 40px;
            height: 40px;
            background: #e3eafc;
            color: #1976d2;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
        }

        .stat-info {
            flex: 1;
        }

        .stat-label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 2px;
        }

        .stat-value {
            font-size: 1.1em;
            font-weight: 600;
            color: #1a237e;
        }

        .profile-actions {
            padding: 20px;
        }

        .action-button {
            display: flex;
            align-items: center;
            width: 100%;
            padding: 12px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 10px;
            color: #333;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 10px;
        }

        .action-button:last-child {
            margin-bottom: 0;
        }

        .action-button i {
            margin-right: 12px;
            font-size: 1.2em;
            color: #1976d2;
        }

        .action-button:hover {
            background: #e3eafc;
            transform: translateX(5px);
        }

        .profile-main {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }

        .profile-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e3e6f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h2 {
            margin: 0;
            color: #1a237e;
            font-size: 1.3em;
            font-weight: 600;
        }

        .card-content {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.2s;
        }

        .form-group input:focus {
            border-color: #1976d2;
            box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
            outline: none;
        }

        .save-button {
            background: #1976d2;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .save-button:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .security-status {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .security-status i {
            font-size: 1.5em;
            margin-right: 15px;
            color: #4caf50;
        }

        .security-info {
            flex: 1;
        }

        .security-title {
            font-weight: 600;
            color: #333;
            margin: 0 0 5px;
        }

        .security-description {
            font-size: 0.9em;
            color: #666;
            margin: 0;
        }

        .activity-timeline {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .timeline-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #e3e6f0;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            background: #e3eafc;
            color: #1976d2;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 500;
            color: #333;
            margin: 0 0 5px;
        }

        .timeline-time {
            font-size: 0.9em;
            color: #666;
        }

        @media (max-width: 1024px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-content {
                margin-left: 70px;
                padding: 20px;
            }

            .profile-header {
                padding: 20px;
            }

            .profile-image-container {
                width: 120px;
                height: 120px;
                margin: 0 auto 8px auto;
            }

            .profile-name {
                font-size: 1.5em;
                margin: 0 0 5px;
            }

            .card-header {
                padding: 15px 20px;
            }

            .card-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div id="alert-container"></div>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php
                    if (!empty($staff['foto_perfil'])) {
                        echo str_replace('../uploads/', '/uploads/', $staff['foto_perfil']);
                    } else {
                        echo '../img/default-avatar.png';
                    }
                ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($staff['nome']); ?></h3>
                <p><?php echo ucfirst($staff['tipo']); ?></p>
            </div>
            
            <div class="staff-menu">
                <a href="dashboard_staff.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_staff.php" class="menu-item active">
                    <i class="fas fa-user-circle"></i> Perfil
                </a>
                <a href="gerir_atletas.php" class="menu-item">
                    <i class="fas fa-users"></i> Gerir Atletas
                </a>
                <a href="gerir_treinos.php" class="menu-item">
                    <i class="fas fa-running"></i> Gerir Treinos
                </a>
                <a href="gerir_jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Gerir Jogos
                </a>
                <a href="mensagens_staff.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php
                        // Fetch unread messages count for the badge
                        $stmt_messages = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE destinatario_id = ? AND lida = 0");
                        if ($stmt_messages) {
                            $stmt_messages->bind_param("i", $_SESSION['user_id']);
                            $stmt_messages->execute();
                            $mensagens_nao_lidas = $stmt_messages->get_result()->fetch_assoc();
                            if ($mensagens_nao_lidas['count'] > 0) {
                                echo '<span class="badge">' . $mensagens_nao_lidas['count'] . '</span>';
                            }
                            $stmt_messages->close();
                        }
                    ?>
                </a>
                <a href="documentos_staff.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Documentos
                </a>
                <a href="estatisticas.php" class="menu-item">
                    <i class="fas fa-chart-line"></i> Estatísticas
                </a>
                <?php if ($staff['tipo'] == 'dirigente'): ?>
                <a href="financeiro.php" class="menu-item">
                    <i class="fas fa-euro-sign"></i> Financeiro
                </a>
                <?php endif; ?>
            </div>

            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <div class="profile-grid">
                <!-- Sidebar do Perfil -->
                <div class="profile-sidebar">
                    <div class="profile-header">
                        <div class="profile-image-container">
                            <img src="<?php echo (!empty($staff['foto_perfil'])) ? str_replace('../uploads/', '/uploads/', $staff['foto_perfil']) : '../img/default-avatar.png'; ?>" alt="Perfil" class="profile-image">
                            <label for="foto_perfil" class="profile-image-edit">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                        <h2 class="profile-name"><?php echo htmlspecialchars($staff['nome']); ?></h2>
                        <p class="profile-role"><?php echo ucfirst($staff['tipo']); ?></p>
                    </div>

                    <div class="profile-stats">
                        <div class="stat-item">
                            <i class="fas fa-calendar-check"></i>
                            <div class="stat-info">
                                <div class="stat-label">Treinos Realizados</div>
                                <div class="stat-value">24</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-trophy"></i>
                            <div class="stat-info">
                                <div class="stat-label">Jogos Dirigidos</div>
                                <div class="stat-value">12</div>
                            </div>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <div class="stat-info">
                                <div class="stat-label">Atletas Ativos</div>
                                <div class="stat-value">18</div>
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button class="action-button" onclick="document.getElementById('foto_perfil').click()">
                            <i class="fas fa-camera"></i> Alterar Foto
                        </button>
                    </div>
                </div>

                <!-- Conteúdo Principal do Perfil -->
                <div class="profile-main">
                    <!-- Informações Pessoais -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>Informações Pessoais</h2>
                        </div>
                        <div class="card-content">
                            <form action="perfil_staff.php" method="post" enctype="multipart/form-data">
                                <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*" style="display: none;">
                                
                                <div class="form-group">
                                    <label for="nome">Nome Completo</label>
                                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($staff['nome']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="telefone">Telemóvel</label>
                                    <input type="tel" id="telefone" name="telefone" value="<?php echo htmlspecialchars($staff['telefone'] ?? ''); ?>">
                                </div>

                                <button type="submit" name="update_profile" class="save-button">Guardar Alterações</button>
                            </form>
                        </div>
                    </div>

                    <!-- Segurança -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>Segurança</h2>
                        </div>
                        <div class="card-content">
                            <form action="perfil_staff.php" method="post">
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

                                <button type="submit" name="update_password" class="save-button">Atualizar Palavra-passe</button>
                            </form>
                        </div>
                    </div>

                    <!-- Atividades Recentes -->
                    <div class="profile-card">
                        <div class="card-header">
                            <h2>Atividades Recentes</h2>
                        </div>
                        <div class="card-content">
                            <ul class="activity-timeline">
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-running"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Treino Realizado</div>
                                        <div class="timeline-time">Hoje, 15:30</div>
                                    </div>
                                </li>
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-futbol"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Jogo Programado</div>
                                        <div class="timeline-time">Ontem, 18:45</div>
                                    </div>
                                </li>
                                <li class="timeline-item">
                                    <div class="timeline-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="timeline-title">Novo Atleta Adicionado</div>
                                        <div class="timeline-time">2 dias atrás</div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Função para mostrar o modal de alteração de senha
    function showChangePasswordModal() {
        // Implementar lógica do modal
        alert('A funcionalidade de alteração de senha será ativada aqui.');
    }

    // Função para mostrar configurações de notificação
    function showNotificationSettings() {
        // Implementar lógica das configurações
        alert('A funcionalidade de configurações de notificação será ativada aqui.');
    }

    // Função para mostrar alertas
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
        console.log('showAlert called with message:', message, 'and type:', type);
        if (!alertContainer) {
            console.error('Element with ID "alert-container" not found.');
            return;
        }
        console.log('alertContainer found:', alertContainer);
        const alertElement = document.createElement('div');
        alertElement.classList.add('alert', 'alert-' + type);
        let iconClass = '';
        if (type === 'success') iconClass = 'fas fa-check-circle';
        else if (type === 'danger') iconClass = 'fas fa-times-circle';
        else iconClass = 'fas fa-info-circle';

        alertElement.innerHTML = `
            <i class="alert-icon ${iconClass}"></i>
            <span>${message}</span>
            <button class="close-btn">&times;</button>
        `;
        alertContainer.appendChild(alertElement);
        console.log('Alert element appended:', alertElement);

        // Add listener to close the alert
        alertElement.querySelector('.close-btn').addEventListener('click', function() {
            alertElement.style.opacity = '0';
            setTimeout(() => alertElement.remove(), 500);
        });

        // Auto-hide after 5 seconds
        setTimeout(() => {
            alertElement.style.opacity = '0';
            setTimeout(() => alertElement.remove(), 500);
        }, 5000);
    }

    // Preview da imagem antes do upload
    document.getElementById('foto_perfil').addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.querySelector('.profile-image').src = e.target.result;
            }
            reader.readAsDataURL(e.target.files[0]);
        }
    });
    </script>
</body>
</html> 