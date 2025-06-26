<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar informações do staff
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

if (!isset($_GET['id'])) {
    header("Location: gerir_treinos.php");
    exit();
}

$id = $_GET['id'];

// Buscar informações do treino
$stmt = $conn->prepare("SELECT * FROM treinos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$treino = $stmt->get_result()->fetch_assoc();

if (!$treino) {
    $_SESSION['erro'] = "Treino não encontrado.";
    header("Location: gerir_treinos.php");
    exit();
}

// Buscar modalidades para o formulário
$stmt = $conn->prepare("SELECT * FROM modalidades WHERE ativo = 1");
$stmt->execute();
$modalidades = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Treino - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f6fb;
        }
        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 100%);
            color: white;
            padding: 20px;
            min-height: 100vh;
        }
        .staff-header img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid #fff;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .staff-header h3 {
            font-size: 1.3em;
            margin-bottom: 2px;
        }
        .staff-header p {
            font-size: 1em;
            color: #cfd8dc;
        }
        .staff-menu .menu-item {
            display: flex;
            align-items: center;
            padding: 13px 18px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 7px;
            font-weight: 500;
            font-size: 1.05em;
            transition: all 0.2s;
        }
        .staff-menu .menu-item:hover, .staff-menu .menu-item.active {
            background: rgba(255,255,255,0.18);
            color: #fff;
            transform: translateX(7px) scale(1.03);
        }
        .staff-menu .menu-item i {
            margin-right: 12px;
            font-size: 1.1em;
        }
        .badge {
            background: #ff4081;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            margin-left: auto;
        }
        .dashboard-content {
            padding: 40px 30px 30px 30px;
        }
        .edit-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        .edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .btn-back {
            background: #666;
            color: white;
            padding: 10px 22px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 1em;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-back:hover {
            background: #555;
            transform: scale(1.04);
        }
        .edit-form {
            background: white;
            padding: 28px 28px 20px 28px;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10), 0 1.5px 4px rgba(26,35,126,0.08);
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-secondary {
            background: #666;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-secondary:hover {
            background: #555;
        }
        .btn-primary {
            background: #1976d2;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            font-size: 1em;
            transition: background 0.2s, transform 0.2s;
        }
        .btn-primary:hover {
            background: #1565c0;
            transform: scale(1.04);
        }
        /* Alertas de sucesso/erro */
        .alert {
            padding: 14px 22px;
            border-radius: 7px;
            margin-bottom: 18px;
            font-size: 1.05em;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(26,35,126,0.07);
        }
        .alert-success {
            background: #e3fcec;
            color: #256029;
            border: 1px solid #b7ebc6;
        }
        .alert-error {
            background: #fff1f0;
            color: #a8071a;
            border: 1px solid #ffa39e;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php echo $staff['foto_perfil'] ?? '../img/default-avatar.png'; ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($staff['nome']); ?></h3>
                <p><?php echo ucfirst($staff['tipo']); ?></p>
            </div>
            
            <div class="staff-menu">
                <a href="dashboard_staff.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="gerir_atletas.php" class="menu-item">
                    <i class="fas fa-users"></i> Gerir Atletas
                </a>
                <a href="gerir_treinos.php" class="menu-item active">
                    <i class="fas fa-running"></i> Gerir Treinos
                </a>
                <a href="gerir_jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Gerir Jogos
                </a>
                <a href="mensagens_staff.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Mensagens
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
            <?php if (isset($_SESSION['sucesso'])): ?>
                <div class="alert alert-success" id="alerta-msg"><?php echo $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
            <?php elseif (isset($_SESSION['erro'])): ?>
                <div class="alert alert-error" id="alerta-msg"><?php echo $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
            <?php endif; ?>
            <div class="edit-container">
                <div class="edit-header">
                    <h1>Editar Treino</h1>
                    <a href="gerir_treinos.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>

                <div class="edit-form">
                    <form action="processar_treino.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $treino['id']; ?>">
                        
                        <div class="form-group">
                            <label for="modalidade">Modalidade</label>
                            <select name="modalidade_id" id="modalidade" required>
                                <option value="">Selecione uma modalidade</option>
                                <?php while ($modalidade = $modalidades->fetch_assoc()): ?>
                                    <option value="<?php echo $modalidade['id']; ?>" <?php echo ($modalidade['id'] == $treino['modalidade_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($modalidade['nome']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="data">Data</label>
                            <input type="date" id="data" name="data" value="<?php echo $treino['data']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="hora_inicio">Hora de Início</label>
                            <input type="time" id="hora_inicio" name="hora_inicio" value="<?php echo $treino['hora_inicio']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="hora_fim">Hora de Fim</label>
                            <input type="time" id="hora_fim" name="hora_fim" value="<?php echo $treino['hora_fim']; ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="local">Local</label>
                            <input type="text" id="local" name="local" value="<?php echo htmlspecialchars($treino['local']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="treinador">Treinador</label>
                            <input type="text" id="treinador" name="treinador" value="<?php echo htmlspecialchars($treino['treinador']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="descricao">Descrição</label>
                            <textarea id="descricao" name="descricao" rows="4"><?php echo htmlspecialchars($treino['descricao']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="anexo">Anexo (opcional)</label>
                            <input type="file" id="anexo" name="anexo" accept="application/pdf,image/*,.doc,.docx,.xls,.xlsx">
                            <?php if (!empty($treino['anexo'])): ?>
                                <br><a href="uploads/<?php echo htmlspecialchars($treino['anexo']); ?>" target="_blank">Ver anexo atual</a>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <a href="gerir_treinos.php" class="btn-secondary">Cancelar</a>
                            <button type="submit" class="btn-primary">Salvar Alterações</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Sumir alerta após 4 segundos
        window.onload = function() {
            var alerta = document.getElementById('alerta-msg');
            if (alerta) {
                setTimeout(function() {
                    alerta.style.display = 'none';
                }, 4000);
            }
        }
    </script>
</body>
</html> 