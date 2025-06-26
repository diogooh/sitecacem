<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'atleta') {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar informações do atleta
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$atleta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Buscar a última mensalidade paga pelo atleta
$latest_paid_stmt = $conn->prepare("SELECT mes, ano FROM mensalidades WHERE atleta_id = ? AND status = 'Paga' ORDER BY ano DESC, mes DESC LIMIT 1");
$latest_paid_stmt->bind_param("i", $_SESSION['user_id']);
$latest_paid_stmt->execute();
$latest_paid_result = $latest_paid_stmt->get_result();
$latest_paid_mensalidade = $latest_paid_result->fetch_assoc();
$latest_paid_stmt->close();

$latest_paid_month_year = 'N/A';
if ($latest_paid_mensalidade) {
    $monthNum  = $latest_paid_mensalidade['mes'];
    $dateObj   = DateTime::createFromFormat('!m', $monthNum);
    $monthName = $dateObj->format('F'); // Full month name in English

    // Translate month name to Portuguese
    $month_names_pt = [
        'January' => 'Janeiro', 'February' => 'Fevereiro', 'March' => 'Março',
        'April' => 'Abril', 'May' => 'Maio', 'June' => 'Junho',
        'July' => 'Julho', 'August' => 'Agosto', 'September' => 'Setembro',
        'October' => 'Outubro', 'November' => 'Novembro', 'December' => 'Dezembro'
    ];
    $monthNamePt = $month_names_pt[$monthName] ?? $monthName; // Fallback to English if translation not found

    $latest_paid_month_year = $monthNamePt . ' ' . $latest_paid_mensalidade['ano'];
}

// Calculate payment percentage
$total_mensalidades_stmt = $conn->prepare("SELECT COUNT(*) as total FROM mensalidades WHERE atleta_id = ?");
$total_mensalidades_stmt->bind_param("i", $_SESSION['user_id']);
$total_mensalidades_stmt->execute();
$total_mensalidades_result = $total_mensalidades_stmt->get_result()->fetch_assoc();
$total_mensalidades = $total_mensalidades_result['total'];
$total_mensalidades_stmt->close();

$paid_mensalidades_stmt = $conn->prepare("SELECT COUNT(*) as paid FROM mensalidades WHERE atleta_id = ? AND status = 'Paga'");
$paid_mensalidades_stmt->bind_param("i", $_SESSION['user_id']);
$paid_mensalidades_stmt->execute();
$paid_mensalidades_result = $paid_mensalidades_stmt->get_result()->fetch_assoc();
$paid_mensalidades = $paid_mensalidades_result['paid'];
$paid_mensalidades_stmt->close();

$payment_percentage = 0;
if ($total_mensalidades > 0) {
    $payment_percentage = ($paid_mensalidades / $total_mensalidades) * 100;
}

// Buscar próximos eventos
$stmt = $conn->prepare("SELECT * FROM eventos WHERE data >= CURDATE() ORDER BY data LIMIT 5");
if ($stmt) {
    $stmt->execute();
    $eventos = $stmt->get_result();
} else {
    $eventos = null;
}

// Buscar mensagens não lidas
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE atleta_id = ? AND lida = 0");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $mensagens = $stmt->get_result()->fetch_assoc();
} else {
    $mensagens = ['count' => 0];
}

// Buscar as últimas mensalidades para exibir no card (reuse the query for the payments page, limit to 3)
$recent_mensalidades_stmt = $conn->prepare("SELECT mes, ano, valor, status FROM mensalidades WHERE atleta_id = ? ORDER BY ano DESC, mes DESC LIMIT 3");
$recent_mensalidades_stmt->bind_param("i", $_SESSION['user_id']);
$recent_mensalidades_stmt->execute();
$recent_mensalidades = $recent_mensalidades_stmt->get_result();
$recent_mensalidades_stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Atleta - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
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
                <a href="dashboard_atleta.php" class="menu-item active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_atleta.php" class="menu-item">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="treinos.php" class="menu-item">
                    <i class="fas fa-running"></i> Treinos
                </a>
                <a href="jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Jogos
                </a>
                <a href="mensagens.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php if ($mensagens['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens['count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="pagamentos.php" class="menu-item">
                    <i class="fas fa-euro-sign"></i> Pagamentos
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
            <div class="dashboard-grid">
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

                <!-- Card de Estatísticas -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> Estatísticas</h3>
                    </div>
                    <div class="card-content stats-grid">
                        <div class="stat-item">
                            <span class="stat-value">15</span>
                            <span class="stat-label">Treinos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">8</span>
                            <span class="stat-label">Jogos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">5</span>
                            <span class="stat-label">Golos</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value">90%</span>
                            <span class="stat-label">Presença</span>
                        </div>
                    </div>
                </div>

                <!-- Card de Mensagens -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-envelope"></i> Mensagens</h3>
                    </div>
                    <div class="card-content">
                        <a href="mensagens.php" class="btn-primary">Ver Mensagens</a>
                    </div>
                </div>

                <!-- Card de Documentos -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-file-alt"></i> Documentos</h3>
                    </div>
                    <div class="card-content">
                        <ul class="doc-list">
                            <li><i class="fas fa-file-pdf"></i> Exame Médico <span class="status valid">Válido</span></li>
                            <li><i class="fas fa-file-pdf"></i> Seguro Desportivo <span class="status warning">A expirar</span></li>
                            <li><i class="fas fa-file-pdf"></i> Inscrição Federação <span class="status valid">Válido</span></li>
                        </ul>
                    </div>
                </div>

                <!-- Card de Pagamentos -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-euro-sign"></i> Pagamentos</h3>
                    </div>
                    <div class="card-content">
                        <div class="payment-status">
                            <div class="payment-info">
                                <div class="progress-bar">
                                    <div class="progress" style="width: <?php echo $payment_percentage; ?>%;"></div>
                                </div>
                                <p>Mensalidades em dia até: <strong><?php echo $latest_paid_month_year; ?></strong></p>
                                <!-- Display recent mensalidades -->
                                <div class="recent-mensalidades">
                                     <h4>Últimas Mensalidades:</h4>
                                     <?php if ($recent_mensalidades->num_rows > 0): ?>
                                         <ul>
                                             <?php while($mensalidade = $recent_mensalidades->fetch_assoc()): ?>
                                                 <li>
                                                     <?= sprintf('%02d', $mensalidade['mes']) ?>/<?= $mensalidade['ano'] ?> - <?= htmlspecialchars($mensalidade['valor']) ?> €
                                                     <?php
                                                         $status = strtolower(htmlspecialchars($mensalidade['status']));
                                                         $status_class = '';
                                                         if ($status == 'pendente') {
                                                             $status_class = 'status-pendente';
                                                         } else if ($status == 'paga') {
                                                             $status_class = 'status-paga';
                                                         }
                                                     ?>
                                                     <span class="status-badge <?= $status_class ?>"><?= ucfirst($status) ?></span>
                                                 </li>
                                             <?php endwhile; ?>
                                         </ul>
                                     <?php else: ?>
                                         <p>Nenhuma mensalidade recente encontrada.</p>
                                     <?php endif; ?>
                                </div>
                            </div>
                            <div class="payment-actions">
                                <a href="pagamentos.php" class="btn-secondary">Ver Histórico</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card de Equipamentos -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tshirt"></i> Equipamentos</h3>
                    </div>
                    <div class="card-content">
                        <ul class="equipment-list">
                            <li class="equipment-item">
                                <div class="equipment-info">
                                    <div class="equipment-icon">
                                        <i class="fas fa-tshirt"></i>
                                    </div>
                                    <span class="equipment-name">Equipamento Principal</span>
                                </div>
                                <span class="equipment-status status-entregue">Entregue</span>
                            </li>
                            
                            <li class="equipment-item">
                                <div class="equipment-info">
                                    <div class="equipment-icon">
                                        <i class="fas fa-tshirt"></i>
                                    </div>
                                    <span class="equipment-name">Equipamento Alternativo</span>
                                </div>
                                <span class="equipment-status status-entregue">Entregue</span>
                            </li>
                            
                            <li class="equipment-item">
                                <div class="equipment-info">
                                    <div class="equipment-icon">
                                        <i class="fas fa-running"></i>
                                    </div>
                                    <span class="equipment-name">Kit Treino</span>
                                </div>
                                <span class="equipment-status status-pendente">Pendente</span>
                            </li>
                            
                            <li class="equipment-item">
                                <div class="equipment-info">
                                    <div class="equipment-icon">
                                        <i class="fas fa-socks"></i>
                                    </div>
                                    <span class="equipment-name">Meias Oficiais</span>
                                </div>
                                <span class="equipment-status status-entregue">Entregue</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 