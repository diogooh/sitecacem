<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar informações do staff
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// Buscar próximos eventos
$stmt = $conn->prepare("SELECT * FROM eventos WHERE data >= CURDATE() ORDER BY data LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $eventos = $stmt->get_result();
} else {
    $eventos = null;
}

// Buscar mensagens não lidas
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE destinatario_id = ? AND lida = 0");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_assoc();
} else {
    $mensagens = ['count' => 0];
}

// Buscar estatísticas de atletas
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE tipo = 'atleta' AND status = 'aprovado'");
if ($stmt) {
    $stmt->execute();
    $total_atletas = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_atletas = 0;
}

// --- Adicionar este bloco para buscar e mostrar o alerta da sessão ---
if (isset($_SESSION['alert'])) {
    $alert_message = $_SESSION['alert']['message'];
    $alert_type = $_SESSION['alert']['type'];
    // Usar JS para mostrar o alerta depois que a página carregar
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showAlert(\'" . addslashes($alert_message) . "\', \'" . addslashes($alert_type) . "\'); });</script>";
    unset($_SESSION['alert']); // Limpar a sessão para não mostrar novamente
}
// --- Fim do bloco do alerta da sessão ---
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Staff - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            overflow: hidden;
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
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
        .card-header .icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #e3eafc;
            color: #1976d2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }
        .card-content {
            padding: 25px;
        }
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .stat-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: transform 0.2s;
        }
        .stat-item:hover {
            transform: translateY(-3px);
        }
        .stat-value {
            font-size: 2.2em;
            color: #1a237e;
            font-weight: 700;
            margin: 0 0 5px;
            line-height: 1;
        }
        .stat-label {
            color: #666;
            font-size: 0.95em;
            font-weight: 500;
        }
        .recent-activity {
            margin-top: 25px;
        }
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 15px 0;
            border-bottom: 1px solid #e3e6f0;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #e3eafc;
            color: #1976d2;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2em;
            flex-shrink: 0;
        }
        .activity-content {
            flex: 1;
        }
        .activity-title {
            font-weight: 500;
            color: #333;
            margin: 0 0 5px;
            font-size: 1.05em;
        }
        .activity-time {
            font-size: 0.9em;
            color: #666;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .action-button {
            background: #1976d2;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 12px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(26,35,126,0.15);
        }
        .action-button:hover {
            background: #1565c0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26,35,126,0.2);
        }
        .action-button i {
            font-size: 1.2em;
        }
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            .stat-grid {
                grid-template-columns: 1fr;
            }
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .welcome-section {
                padding: 20px;
            }
            .welcome-section h1 {
        }
        /* Alertas de sucesso/erro */
        .alert {
            padding: 12px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
            min-width: 250px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .alert .close-btn {
            margin-left: auto;
            font-size: 1.2em;
            cursor: pointer;
            color: inherit;
            background: none;
            border: none;
            line-height: 1;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .alert-icon {
            font-size: 1.4em;
        }
    </style>
</head>
<body>
    <!-- Contêmpor para Alertas -->
    <div id="alert-container" style="position: fixed; top: 20px; right: 20px; z-index: 10000;">
        <!-- Alertas serão inseridos aqui pelo PHP/JS -->
    </div>
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
                <a href="dashboard_staff.php" class="menu-item active">
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
                    <?php if (isset($mensagens['count']) && $mensagens['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens['count']; ?></span>
                    <?php endif; ?>
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
            <h1>Bem-vindo, <?php echo htmlspecialchars($staff['nome']); ?></h1>
            
            <div class="dashboard-grid">
                <!-- Card de Estatísticas -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Estatísticas Gerais</h3>
                    </div>
                    <div class="card-content">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-value"><?php echo $total_atletas; ?></span>
                                <span class="stat-label">Atletas Ativos</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">12</span>
                                <span class="stat-label">Treinos/Mês</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">4</span>
                                <span class="stat-label">Jogos/Mês</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">95%</span>
                                <span class="stat-label">Presença</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Próximos Eventos -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar"></i> Próximos Eventos</h3>
                    </div>
                    <div class="card-content">
                        <?php if ($eventos && $eventos->num_rows > 0): ?>
                            <?php while ($evento = $eventos->fetch_assoc()): ?>
                                <div class="evento-item">
                                    <div class="evento-data">
                                        <?php echo date('d/m', strtotime($evento['data'])); ?>
                                    </div>
                                    <div class="evento-info">
                                        <strong><?php echo htmlspecialchars($evento['titulo']); ?></strong>
                                        <span><?php echo htmlspecialchars($evento['local']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Nenhum evento próximo.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card de Ações Rápidas -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> Ações Rápidas</h3>
                    </div>
                    <div class="card-content">
                        <a href="gerir_treinos.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Novo Treino
                        </a>
                        <a href="gerir_jogos.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Novo Jogo
                        </a>
                        <a href="mensagens_staff.php" class="btn-primary">
                            <i class="fas fa-envelope"></i> Nova Mensagem
                        </a>
                    </div>
                </div>

                <!-- Card de Últimas Atividades -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Últimas Atividades</h3>
                    </div>
                    <div class="card-content">
                        <ul class="activity-list">
                            <li>
                                <i class="fas fa-check-circle"></i>
                                <span>Treino de Andebol - 15 atletas presentes</span>
                                <small>Hoje, 18:00</small>
                            </li>
                            <li>
                                <i class="fas fa-user-plus"></i>
                                <span>Novo atleta registrado</span>
                                <small>Ontem, 14:30</small>
                            </li>
                            <li>
                                <i class="fas fa-file-alt"></i>
                                <span>Relatório mensal atualizado</span>
                                <small>2 dias atrás</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Função para mostrar um alerta
    function showAlert(message, type = 'info') {
        const alertContainer = document.getElementById('alert-container');
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

        // Adicionar listener para fechar o alerta
        alertElement.querySelector('.close-btn').addEventListener('click', function() {
            alertElement.style.opacity = '0';
            setTimeout(() => alertElement.remove(), 500);
        });

        // Ocultar automaticamente após 5 segundos
        setTimeout(() => {
            alertElement.style.opacity = '0';
            setTimeout(() => alertElement.remove(), 500);
        }, 5000);
    }

    // Verificar se há mensagens de sucesso ou erro na URL (do PHP redirect)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sucesso')) {
        showAlert('Operação realizada com sucesso!', 'success');
        // Remover o parâmetro da URL para não mostrar novamente ao atualizar
        urlParams.delete('sucesso');
        history.replaceState({}, '', '?' + urlParams.toString());
    }
    // Se o PHP definir uma sessão para erro, podemos buscar aqui também
    // if (urlParams.has('erro')) {
    //     showAlert('Ocorreu um erro na operação.', 'danger');
    //     urlParams.delete('erro');
    //     history.replaceState({}, '', '?' + urlParams.toString());
    // }

    // Exemplo de como você pode chamar um alerta manualmente (pode remover depois)
    // showAlert('Este é um alerta de informação.');
    // showAlert('Este é um alerta de sucesso!', 'success');
    // showAlert('Este é um alerta de erro!', 'danger');
    </script>
</body>
</html> 