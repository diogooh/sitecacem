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

// --- Obtenção de Estatísticas --- //

// 1. Estatísticas de Utilizadores
$stats['users'] = [];
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$stats['users']['total'] = $result->fetch_assoc()['total_users'];

$result = $conn->query("SELECT tipo, COUNT(*) AS count FROM users GROUP BY tipo");
$stats['users']['by_type'] = $result->fetch_all(MYSQLI_ASSOC);

$result = $conn->query("SELECT status, COUNT(*) AS count FROM users GROUP BY status");
$stats['users']['by_status'] = $result->fetch_all(MYSQLI_ASSOC);

$result = $conn->query("SELECT e.nome AS escalao_nome, COUNT(u.id) AS count
                          FROM users u
                          JOIN escaloes e ON u.escalao_id = e.id
                          WHERE u.tipo = 'atleta'
                          GROUP BY e.nome
                          ORDER BY e.nome");
$stats['users']['athletes_by_escalao'] = $result->fetch_all(MYSQLI_ASSOC);

// 2. Estatísticas de Jogos
$stats['jogos'] = [];
$result = $conn->query("SELECT status, COUNT(*) AS count FROM jogos GROUP BY status");
$stats['jogos']['by_status'] = $result->fetch_all(MYSQLI_ASSOC);

// 3. Estatísticas de Mensagens
$stats['mensagens'] = [];
$result = $conn->query("SELECT COUNT(*) AS total_messages FROM mensagens");
$stats['mensagens']['total'] = $result->fetch_assoc()['total_messages'];

$stmt = $conn->prepare("SELECT COUNT(*) AS unread_messages FROM mensagens WHERE destinatario_id = ? AND lida = 0");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats['mensagens']['unread_for_me'] = $stmt->get_result()->fetch_assoc()['unread_messages'];
$stmt->close();

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/nav.css">
    <link rel="stylesheet" href="/sitecacem/src/estatisticas.css">
    <link rel="stylesheet" href="dashboard_atleta.css"> <!-- Reutilizando estilos base do dashboard -->
    <link rel="stylesheet" href="mensagens.css"> <!-- Para estilos de alerta, se necessário -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/sitecacem/src/dashboard_nav.css">
    <style>
        /* Estilos específicos para a página de Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            font-size: 2.5em;
            color: #1a237e;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2.2em;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .stat-card .label {
            font-size: 1em;
            color: #666;
            margin-top: 5px;
        }

        .stats-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .stats-section h2 {
            color: #1a237e;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.8em;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .stats-list {
            list-style: none;
            padding: 0;
        }

        .stats-list li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #eee;
            color: #333;
        }

        .stats-list li:last-child {
            border-bottom: none;
        }

        .stats-list li strong {
            color: #1976d2;
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
                <a href="documentos_staff.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Documentos
                </a>
                <a href="estatisticas.php" class="menu-item active">
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
                <h1>Estatísticas do Clube</h1>
            </div>

            <div class="stats-section">
                <h2><i class="fas fa-users"></i> Utilizadores</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-user"></i></div>
                        <p class="value"><?php echo $stats['users']['total']; ?></p>
                        <p class="label">Total de Utilizadores</p>
                    </div>
                    <?php foreach ($stats['users']['by_type'] as $type): ?>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-user-tag"></i></div>
                        <p class="value"><?php echo $type['count']; ?></p>
                        <p class="label">Utilizadores (<?php echo ucfirst($type['tipo']); ?>)</p>
                    </div>
                    <?php endforeach; ?>
                    <?php foreach ($stats['users']['by_status'] as $status): ?>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-user-check"></i></div>
                        <p class="value"><?php echo $status['count']; ?></p>
                        <p class="label">Utilizadores (<?php echo ucfirst($status['status']); ?>)</p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <h3>Atletas por Escalão</h3>
                <ul class="stats-list">
                    <?php foreach ($stats['users']['athletes_by_escalao'] as $escalao_stat): ?>
                        <li>
                            <span><?php echo htmlspecialchars($escalao_stat['escalao_nome']); ?></span>
                            <strong><?php echo $escalao_stat['count']; ?></strong>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($stats['users']['athletes_by_escalao'])): ?>
                        <li><span>Nenhum atleta encontrado por escalão.</span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="stats-section">
                <h2><i class="fas fa-futbol"></i> Jogos</h2>
                <div class="stats-grid">
                    <?php foreach ($stats['jogos']['by_status'] as $jogo_status): ?>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                        <p class="value"><?php echo $jogo_status['count']; ?></p>
                        <p class="label">Jogos (<?php echo ucfirst($jogo_status['status']); ?>)</p>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($stats['jogos']['by_status'])): ?>
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-info-circle"></i></div>
                            <p class="value">0</p>
                            <p class="label">Nenhum jogo registado.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="stats-section">
                <h2><i class="fas fa-envelope"></i> Mensagens</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-comments"></i></div>
                        <p class="value"><?php echo $stats['mensagens']['total']; ?></p>
                        <p class="label">Total de Mensagens</p>
                    </div>
                    <div class="stat-card">
                        <div class="icon"><i class="fas fa-inbox"></i></div>
                        <p class="value"><?php echo $stats['mensagens']['unread_for_me']; ?></p>
                        <p class="label">Mensagens Não Lidas (Staff)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Script para esconder o alerta após X segundos (se houver)
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