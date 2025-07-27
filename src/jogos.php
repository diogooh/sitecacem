<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'atleta') {
    header("Location: login.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Buscar informações do atleta
$stmt = $conn->prepare("SELECT nome, foto_perfil, cip FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query do atleta: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$atleta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar mensagens não lidas
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE atleta_id = ? AND lida = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    $mensagens = ['count' => 0];
}

// 1. Buscar os IDs dos escalões a que o atleta pertence
$escaloes_atleta_ids = [];
$stmt = $conn->prepare("SELECT escalao_id FROM escaloes_utilizadores WHERE utilizador_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result_escaloes = $stmt->get_result();
    while ($row = $result_escaloes->fetch_assoc()) {
        $escaloes_atleta_ids[] = $row['escalao_id'];
    }
    $stmt->close();
} else {
    error_log("Erro ao preparar query para escaloes_utilizadores: " . $conn->error);
}

$jogos = [];
if (!empty($escaloes_atleta_ids)) {
    // 2. Converter o array de IDs em uma string para a cláusula IN
    $in_clause = implode(',', array_fill(0, count($escaloes_atleta_ids), '?'));

    // 3. Buscar os jogos para esses escalões
    $query_jogos = "
        SELECT j.*, e.nome as escalao_nome, m.nome as modalidade_nome, j.pontuacao_acc, j.pontuacao_adversario 
        FROM jogos j 
        JOIN escaloes e ON j.escalao_id = e.id 
        JOIN modalidades m ON e.modalidade_id = m.id 
        WHERE j.escalao_id IN (" . $in_clause . ") 
        ORDER BY j.data_jogo DESC
    ";
    $stmt = $conn->prepare($query_jogos);
    
    if ($stmt) {
        // Criar a string de tipos dinamicamente para bind_param (todos são inteiros)
        $types = str_repeat('i', count($escaloes_atleta_ids));
        $stmt->bind_param($types, ...$escaloes_atleta_ids);
        $stmt->execute();
        $jogos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        error_log("Erro ao preparar query para jogos do atleta: " . $conn->error);
    }
} else {
    error_log("Nenhum escalão encontrado para o atleta " . $user_id . ".");
}

// Debug: Verifique se $jogos está vazio
// echo "<pre>";
// var_dump($jogos);
// echo "</pre>";

// Lógica para atualizar o status do jogo para exibição
$current_time = new DateTime();
foreach ($jogos as &$jogo) { // Usar & para modificar o array diretamente
    $game_datetime = new DateTime($jogo['data_jogo']);

    if ($jogo['status'] === 'agendado' && $game_datetime < $current_time) {
        // Atualizar apenas para exibição, o status no DB é gerenciado pelo treinador
        $jogo['status_display'] = 'finalizado'; 
    } else if ($jogo['status'] === 'agendado' && $game_datetime >= $current_time) {
        $jogo['status_display'] = 'agendado';
    } else if ($jogo['status'] === 'concluido') {
        $jogo['status_display'] = 'finalizado';
    } else {
        $jogo['status_display'] = $jogo['status']; // Usar o status do DB se for 'cancelado'
    }
}
unset($jogo); // Quebrar a referência com o último elemento do array

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jogos - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/dashboard_atleta.css">
    <link rel="stylesheet" href="/sitecacem/src/jogos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-rgb: 0, 123, 255;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-bg: #f8f9fa;
            --dark-bg: #343a40;
            --text-color: #212529;
            --text-muted: #6c757d;
            --border-color: #dee2e6;
            --shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            --shadow-hover: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            --border-radius: 0.5rem;
            --border-radius-small: 0.25rem;
        }

        /* NOVO VISUAL DO CARTÃO DE JOGO */
        .jogo-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px #0001;
            padding: 32px 24px;
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 1.1em;
        }

        .jogo-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }

        .jogo-competicao {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
        }

        .status-badge {
            background: #1976d2;
            color: #fff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 1em;
            font-weight: 600;
            text-transform: capitalize;
        }

        .jogo-equipas {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0 16px 0;
        }

        .equipa {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 100px;
        }

        .equipa-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-bottom: 6px;
        }

        .equipa-nome {
            font-size: 1em;
            font-weight: 500;
            color: #222;
            margin-bottom: 4px;
        }

        .pontuacao-final {
            font-size: 2em;
            font-weight: bold;
            color: #1976d2;
        }

        .jogo-vs {
            font-size: 1.2em;
            font-weight: bold;
            color: #444;
            margin: 0 18px;
        }

        .jogo-info {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            font-size: 1em;
            color: #555;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .jogo-footer {
            width: 100%;
            margin-top: 18px;
            text-align: right;
        }
        .dashboard-content {
            flex: 1 1 0%;
            padding: 40px 30px;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }
        .page-header {
            margin-bottom: 30px;
            margin-top: 10px;
        }
        .page-header h1 {
            color: #004080;
            font-size: 2.1em;
            font-weight: 700;
            margin: 0;
            text-align: left;
        }
        .jogos-grid {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 32px;
            justify-content: center;
            align-items: flex-start;
        }
        .jogo-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            padding: 32px 28px 28px 28px;
            border: none;
            transition: transform 0.18s, box-shadow 0.18s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            min-width: 0;
        }
        .jogo-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 8px 32px rgba(26,35,126,0.16);
        }
        .jogo-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .jogo-competicao {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
        }
        .status-badge, .jogo-status {
            background: #1976d2;
            color: #fff;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 1em;
            font-weight: 600;
            text-transform: capitalize;
            margin-bottom: 10px;
            align-self: flex-end;
        }
        .jogo-equipas {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 24px 0 16px 0;
        }
        .equipa {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 100px;
        }
        .equipa-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-bottom: 6px;
        }
        .equipa-nome {
            font-size: 1em;
            font-weight: 500;
            color: #222;
            margin-bottom: 4px;
        }
        .pontuacao-final {
            font-size: 2em;
            font-weight: bold;
            color: #1976d2;
        }
        .jogo-vs {
            font-size: 1.2em;
            font-weight: bold;
            color: #444;
            margin: 0 18px;
        }
        .jogo-info {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 18px;
            font-size: 1em;
            color: #555;
            flex-wrap: wrap;
            gap: 10px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .jogo-footer {
            width: 100%;
            margin-top: 18px;
            text-align: right;
        }
        .jogo-details-btn {
            background-color: #004080;
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: background 0.2s, transform 0.15s;
            box-shadow: 0 2px 8px rgba(0,64,128,0.08);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .jogo-details-btn:hover {
            background-color: #002b57;
            transform: translateY(-2px) scale(1.04);
        }
        @media (max-width: 900px) {
            .dashboard-content {
                padding: 20px 5px;
            }
            .jogos-grid {
                grid-template-columns: 1fr;
                gap: 18px;
            }
            .jogo-card {
                padding: 20px 10px;
            }
        }
    </style>
    <style>
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
</style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo !empty($atleta['foto_perfil']) ? '/sitecacem/' . ltrim(str_replace(['../uploads/', 'uploads/'], 'uploads/', $atleta['foto_perfil']), '/') : '/sitecacem/img/default-avatar.png'; ?>" alt="Perfil">
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
                    <?php if ($mensagens['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens['count']; ?></span>
                    <?php endif; ?>
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
        <main class="dashboard-content">
            <div class="page-header"><h1>Jogos do Atleta</h1></div>
            <div class="jogos-grid">
                <?php if (empty($jogos)): ?>
                    <p>Nenhum jogo encontrado para os seus escalões.</p>
                <?php else: ?>
                    <?php foreach ($jogos as $jogo): ?>
                        <div class="jogo-card">
                            <span class="jogo-status status-<?php echo htmlspecialchars($jogo['status_display']); ?>">
                                <?php 
                                    if ($jogo['status_display'] === 'agendado') echo 'Agendado';
                                    else if ($jogo['status_display'] === 'finalizado') echo 'Finalizado';
                                    else if ($jogo['status_display'] === 'cancelado') echo 'Cancelado';
                                    else echo ucfirst($jogo['status_display']); // Fallback
                                ?>
                            </span>
                            <div class="jogo-header">
                                <h3 class="jogo-competicao"><?php echo htmlspecialchars($jogo['titulo']); ?></h3>
                                <!-- <span class="jogo-jornada">Jornada X</span> -->
                            </div>
                            <div class="jogo-equipas">
                                <div class="equipa">
                                    <img src="img/logos_clube/cacem.png" alt="Logo ACC" class="equipa-logo">
                                    <span class="equipa-nome">ACC</span>
                                    <?php if ($jogo['status_display'] === 'finalizado' && $jogo['pontuacao_acc'] !== null): ?>
                                        <span class="pontuacao-final"><?php echo htmlspecialchars($jogo['pontuacao_acc']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="jogo-vs">VS</span>
                                <div class="equipa">
                                    <img src="<?php 
                                        $adversario_escaped = htmlspecialchars($jogo['adversario']);
                                        if ($adversario_escaped === 'Boa-Hora') {
                                            $image_path = 'img/logos_clube/boa_hora.jpg';
                                            echo $image_path;
                                        } else {
                                            $image_path = '../img/default-team-logo.png';
                                            echo $image_path;
                                        }
                                    ?>" alt="Logo Adversário" class="equipa-logo">
                                    <span class="equipa-nome"><?php echo htmlspecialchars($jogo['adversario']); ?></span>
                                    <?php if ($jogo['status_display'] === 'finalizado' && $jogo['pontuacao_adversario'] !== null): ?>
                                        <span class="pontuacao-final"><?php echo htmlspecialchars($jogo['pontuacao_adversario']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="jogo-info">
                                <div class="info-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span><?php echo date('d/m/Y H:i', strtotime($jogo['data_jogo'])); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($jogo['local']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo htmlspecialchars($jogo['escalao_nome']); ?></span>
                                </div>
                                <div class="info-item">
                                    <i class="fas fa-futbol"></i>
                                    <span><?php echo htmlspecialchars($jogo['modalidade_nome']); ?></span>
                                </div>
                            </div>
                            <div class="jogo-footer">
                                <a href="#" class="jogo-details-btn">
                                    <i class="fas fa-info-circle"></i> Detalhes
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 