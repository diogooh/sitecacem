<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o utilizador está autenticado e é staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'treinador' && $_SESSION['tipo'] !== 'dirigente')) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

// Verificar se o ID do jogo foi fornecido
if (!isset($_GET['id'])) {
    header('Location: gerir_jogos.php');
    exit();
}

$jogo_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Obter informações do jogo
$stmt = $conn->prepare("
    SELECT j.*, e.nome as escalao_nome, m.nome as modalidade_nome, u.nome as user_nome
    FROM jogos j 
    JOIN escaloes e ON j.escalao_id = e.id 
    JOIN modalidades m ON e.modalidade_id = m.id 
    JOIN users u ON j.user_id = u.id
    WHERE j.id = ? AND j.user_id = ?
");
$stmt->bind_param("ii", $jogo_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$jogo = $result->fetch_assoc();
$stmt->close();

if (!$jogo) {
    $_SESSION['error_message'] = "Jogo não encontrado ou você não tem permissão para vê-lo.";
    header('Location: gerir_jogos.php');
    exit();
}

// Obter informações do utilizador (staff)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Jogo - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
            --primary-light: #1976d2;
            --secondary-color: #f4f6fb;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --border-radius: 12px;
            --box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--secondary-color);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: var(--white);
            padding: 20px;
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .dashboard-content {
            margin-left: 280px;
            padding: 40px;
            width: calc(100% - 280px);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2em;
            color: var(--primary-color);
            margin: 0;
        }

        .back-btn {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1em;
            font-weight: 500;
            transition: var(--transition);
            text-decoration: none;
        }

        .back-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .game-details {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
        }

        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .game-title {
            font-size: 1.8em;
            color: var(--primary-color);
            margin: 0;
        }

        .game-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 1em;
            font-weight: 500;
        }

        .status-agendado {
            background: var(--warning);
            color: #856404;
        }

        .status-concluido {
            background: var(--success);
            color: var(--white);
        }

        .status-cancelado {
            background: var(--danger);
            color: var(--white);
        }

        .game-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .info-group {
            background: var(--secondary-color);
            padding: 20px;
            border-radius: var(--border-radius);
        }

        .info-group h3 {
            color: var(--primary-color);
            margin: 0 0 15px 0;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-group p {
            margin: 10px 0;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-group i {
            color: var(--primary-color);
            width: 20px;
        }

        .game-description {
            background: var(--secondary-color);
            padding: 20px;
            border-radius: var(--border-radius);
            margin-top: 30px;
        }

        .game-description h3 {
            color: var(--primary-color);
            margin: 0 0 15px 0;
            font-size: 1.2em;
        }

        .game-description p {
            margin: 0;
            color: var(--text-light);
            line-height: 1.8;
        }

        .game-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .game-actions button {
            padding: 12px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .edit-btn {
            background: var(--primary-light);
            color: var(--white);
        }

        .delete-btn {
            background: var(--danger);
            color: var(--white);
        }

        .game-actions button:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .dashboard-sidebar.staff-sidebar {
                width: 70px;
                padding: 15px 10px;
            }

            .staff-header h3,
            .staff-header p,
            .menu-item span {
                display: none;
            }

            .dashboard-content {
                margin-left: 70px;
                width: calc(100% - 70px);
                padding: 20px;
            }

            .game-info {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .back-btn {
                width: 100%;
                justify-content: center;
            }

            .game-actions {
                flex-direction: column;
            }

            .game-actions button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php
                    if (!empty($user['foto_perfil'])) {
                        echo str_replace('../uploads/', '/uploads/', $user['foto_perfil']);
                    } else {
                        echo '../img/default-avatar.png';
                    }
                ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($user['nome']); ?></h3>
                <p><?php echo ucfirst($user['tipo']); ?></p>
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
                <a href="gerir_jogos.php" class="menu-item active">
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
                <h1>Detalhes do Jogo</h1>
                <a href="gerir_jogos.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>

            <div class="game-details">
                <div class="game-header">
                    <h2 class="game-title"><?php echo htmlspecialchars($jogo['titulo']); ?></h2>
                    <span class="game-status status-<?php echo strtolower($jogo['status']); ?>">
                        <?php echo ucfirst($jogo['status']); ?>
                    </span>
                </div>

                <div class="game-info">
                    <div class="info-group">
                        <h3><i class="fas fa-calendar"></i> Informações do Jogo</h3>
                        <p><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($jogo['data_jogo'])); ?></p>
                        <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($jogo['local']); ?></p>
                        <p><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($jogo['adversario']); ?></p>
                    </div>

                    <div class="info-group">
                        <h3><i class="fas fa-users"></i> Informações da Equipa</h3>
                        <p><i class="fas fa-users"></i> <?php echo htmlspecialchars($jogo['escalao_nome']); ?></p>
                        <p><i class="fas fa-futbol"></i> <?php echo htmlspecialchars($jogo['modalidade_nome']); ?></p>
                        <p><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($jogo['user_nome']); ?></p>
                    </div>
                </div>

                <?php if (!empty($jogo['descricao'])): ?>
                <div class="game-description">
                    <h3><i class="fas fa-info-circle"></i> Descrição</h3>
                    <p><?php echo nl2br(htmlspecialchars($jogo['descricao'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="game-actions">
                    <button class="edit-btn" onclick="window.location.href='gerir_jogos.php?edit=<?php echo $jogo['id']; ?>'">
                        <i class="fas fa-edit"></i> Editar Jogo
                    </button>
                    <button class="delete-btn" onclick="if(confirm('Tem certeza que deseja apagar este jogo?')) window.location.href='processar_jogo.php?action=delete&id=<?php echo $jogo['id']; ?>'">
                        <i class="fas fa-trash"></i> Apagar Jogo
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 