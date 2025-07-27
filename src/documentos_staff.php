<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Obter informações do staff para a sidebar
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$staff_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Processar upload de documento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_documento'])) {
    $titulo = $conn->real_escape_string($_POST['titulo']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $escalao_id = isset($_POST['escalao_id']) && $_POST['escalao_id'] !== '' ? (int)$_POST['escalao_id'] : NULL;
    $modalidade_id = isset($_POST['modalidade_id']) && $_POST['modalidade_id'] !== '' ? (int)$_POST['modalidade_id'] : NULL;

    if (isset($_FILES['documento']) && $_FILES['documento']['error'] == UPLOAD_ERR_OK) {
        $file_tmp_path = $_FILES['documento']['tmp_name'];
        $file_name_original = $_FILES['documento']['name'];
        $file_size = $_FILES['documento']['size'];
        $file_type = $_FILES['documento']['type'];
        $file_extension = pathinfo($file_name_original, PATHINFO_EXTENSION);

        // Gerar um nome único para o ficheiro
        $new_file_name = uniqid() . '.' . $file_extension;
        $upload_dir = '../uploads/documentos/';
        $dest_path = $upload_dir . $new_file_name;

        // Criar diretório se não existir
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file_tmp_path, $dest_path)) {
            $stmt = $conn->prepare("INSERT INTO documentos (user_id, titulo, descricao, nome_ficheiro_original, caminho_ficheiro, tipo_ficheiro, tamanho_ficheiro, escalao_id, modalidade_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("issssssii", $user_id, $titulo, $descricao, $file_name_original, $dest_path, $file_type, $file_size, $escalao_id, $modalidade_id);
                if ($stmt->execute()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Documento carregado com sucesso!'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro ao registar documento na base de dados.'];
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro na preparação da query de upload.'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro ao mover ficheiro para o diretório de uploads.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro no upload do ficheiro: ' . $_FILES['documento']['error']];
    }
    header("Location: documentos_staff.php");
    exit();
}

// Processar eliminação de documento
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $documento_id = (int)$_GET['id'];

    // Obter caminho do ficheiro antes de eliminar o registo na DB
    $stmt = $conn->prepare("SELECT caminho_ficheiro FROM documentos WHERE id = ? AND user_id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $documento_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $documento = $result->fetch_assoc();
        $stmt->close();

        if ($documento) {
            // Eliminar da base de dados
            $stmt = $conn->prepare("DELETE FROM documentos WHERE id = ? AND user_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $documento_id, $user_id);
                if ($stmt->execute()) {
                    // Eliminar o ficheiro do sistema de ficheiros
                    if (file_exists($documento['caminho_ficheiro'])) {
                        unlink($documento['caminho_ficheiro']);
                    }
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Documento apagado com sucesso!'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro ao apagar documento da base de dados.'];
                }
                $stmt->close();
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro na preparação da query de eliminação.'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Documento não encontrado ou não tem permissão para apagar.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro na preparação da query de busca de ficheiro.'];
    }
    header("Location: documentos_staff.php");
    exit();
}

// Obter todos os documentos carregados pelo staff
$stmt = $conn->prepare("
    SELECT d.*, e.nome as escalao_nome, m.nome as modalidade_nome 
    FROM documentos d
    LEFT JOIN escaloes e ON d.escalao_id = e.id
    LEFT JOIN modalidades m ON d.modalidade_id = m.id
    WHERE d.user_id = ?
    ORDER BY d.data_upload DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$documentos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obter escalões e modalidades para o formulário de upload
$escaloes_disponiveis = [];
$modalidades_disponiveis = [];

$stmt = $conn->prepare("SELECT id, nome FROM escaloes ORDER BY nome");
if ($stmt) {
    $stmt->execute();
    $escaloes_disponiveis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$stmt = $conn->prepare("SELECT id, nome FROM modalidades ORDER BY nome");
if ($stmt) {
    $stmt->execute();
    $modalidades_disponiveis = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Documentos - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/nav.css">
    <link rel="stylesheet" href="/sitecacem/src/documentos_staff.css">
    <link rel="stylesheet" href="dashboard_atleta.css"> <!-- Reutilizando estilos base do dashboard -->
    <link rel="stylesheet" href="mensagens.css"> <!-- Para estilos de alerta -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/sitecacem/src/dashboard_nav.css">
    <style>
        /* Estilos específicos para esta página */
        .document-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .document-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .document-card h3 {
            margin: 0;
            color: #1a237e;
            font-size: 1.3em;
        }

        .document-meta {
            font-size: 0.9em;
            color: #666;
        }

        .document-meta span {
            margin-right: 15px;
        }

        .document-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .document-actions a, .document-actions button {
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
        }

        .download-btn {
            background: #1976d2;
            color: white;
        }

        .download-btn:hover {
            background: #1565c0;
            transform: translateY(-2px);
        }

        .delete-btn {
            background: #f44336;
            color: white;
        }

        .delete-btn:hover {
            background: #d32f2f;
            transform: translateY(-2px);
        }

        .upload-form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .upload-form-container h2 {
            color: #1a237e;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group input[type="file"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions-upload {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn-upload {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-upload:hover {
            background: #218838;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php
                    if (!empty($staff_info['foto_perfil'])) {
                        echo str_replace(['../uploads/', 'uploads/'], '/sitecacem/uploads/', $staff_info['foto_perfil']);
                    } else {
                        echo '/sitecacem/img/default-avatar.png';
                    }
                ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($staff_info['nome']); ?></h3>
                <p><?php echo ucfirst($staff_info['tipo']); ?></p>
            </div>
            
            <div class="staff-menu">
                <a href="dashboard_staff.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_staff.php" class="menu-item">
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
                </a>
                <a href="documentos_staff.php" class="menu-item active">
                    <i class="fas fa-file-alt"></i> Documentos
                </a>
                <a href="estatisticas.php" class="menu-item">
                    <i class="fas fa-chart-line"></i> Estatísticas
                </a>
                <?php if ($staff_info['tipo'] == 'dirigente'): ?>
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
            <div class="page-header">
                <h1>Gestão de Documentos</h1>
                <button class="btn-primary" onclick="document.getElementById('uploadFormContainer').style.display = document.getElementById('uploadFormContainer').style.display === 'none' ? 'block' : 'none';">
                    <i class="fas fa-upload"></i> Upload Novo Documento
                </button>
            </div>

            <?php if (isset($_SESSION['message'])): ?>
                <div id="alertMessage" class="alert <?php echo $_SESSION['message']['type']; ?>">
                    <i class="fas <?php echo ($_SESSION['message']['type'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $_SESSION['message']['text']; ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <div class="upload-form-container" id="uploadFormContainer" style="display: none;">
                <h2>Carregar Novo Documento</h2>
                <form action="documentos_staff.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="upload_documento" value="1">
                    <div class="form-group">
                        <label for="titulo_documento">Título do Documento</label>
                        <input type="text" id="titulo_documento" name="titulo" required>
                    </div>
                    <div class="form-group">
                        <label for="descricao_documento">Descrição</label>
                        <textarea id="descricao_documento" name="descricao"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="escalao_documento">Associar a Escalão (Opcional)</label>
                        <select id="escalao_documento" name="escalao_id">
                            <option value="">Nenhum</option>
                            <?php foreach ($escaloes_disponiveis as $escalao): ?>
                                <option value="<?php echo $escalao['id']; ?>"><?php echo htmlspecialchars($escalao['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="modalidade_documento">Associar a Modalidade (Opcional)</label>
                        <select id="modalidade_documento" name="modalidade_id">
                            <option value="">Nenhum</option>
                            <?php foreach ($modalidades_disponiveis as $modalidade): ?>
                                <option value="<?php echo $modalidade['id']; ?>"><?php echo htmlspecialchars($modalidade['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="documento_file">Ficheiro do Documento</label>
                        <input type="file" id="documento_file" name="documento" required>
                    </div>
                    <div class="form-actions-upload">
                        <button type="submit" class="btn-upload">
                            <i class="fas fa-cloud-upload-alt"></i> Carregar Documento
                        </button>
                    </div>
                </form>
            </div>

            <h2>Documentos Carregados</h2>
            <?php if (empty($documentos)): ?>
                <div class="no-message">Nenhum documento carregado ainda.</div>
            <?php else: ?>
                <?php foreach ($documentos as $doc): ?>
                    <div class="document-card">
                        <div class="document-card-header">
                            <h3><?php echo htmlspecialchars($doc['titulo']); ?></h3>
                            <div class="document-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($doc['data_upload'])); ?></span>
                                <?php if ($doc['escalao_nome']): ?>
                                    <span><i class="fas fa-users"></i> Escalão: <?php echo htmlspecialchars($doc['escalao_nome']); ?></span>
                                <?php endif; ?>
                                <?php if ($doc['modalidade_nome']): ?>
                                    <span><i class="fas fa-futbol"></i> Modalidade: <?php echo htmlspecialchars($doc['modalidade_nome']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($doc['descricao'])); ?></p>
                        <div class="document-actions">
                            <a href="<?php echo htmlspecialchars($doc['caminho_ficheiro']); ?>" class="download-btn" download>
                                <i class="fas fa-download"></i> Download (<?php echo round($doc['tamanho_ficheiro'] / 1024 / 1024, 2); ?> MB)
                            </a>
                            <button class="delete-btn" onclick="confirmDeleteDocument(<?php echo $doc['id']; ?>)">
                                <i class="fas fa-trash"></i> Apagar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function confirmDeleteDocument(id) {
            if (confirm('Tem certeza que deseja apagar este documento? Esta ação é irreversível.')) {
                window.location.href = 'documentos_staff.php?action=delete&id=' + id;
            }
        }

        // Script para esconder o alerta após X segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.getElementById('alertMessage');
            if (alertMessage) {
                setTimeout(() => {
                    alertMessage.style.display = 'none';
                }, 5000); // Esconde após 5 segundos
            }
        });
    </script>
</body>
</html> 