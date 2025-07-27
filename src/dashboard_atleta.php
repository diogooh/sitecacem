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

// Buscar equipamentos atuais do atleta
$stmt = $conn->prepare('SELECT * FROM equipamentos WHERE atleta_id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result_equip = $stmt->get_result();
$equip = $result_equip->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Atleta - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .dashboard-content {
            flex: 1 1 0%;
            padding: 40px 30px;
            min-width: 0;
        }
        .dashboard-grid {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
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
        .equipamentos-grid {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 8px;
        }
        .equipamento-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.06);
            padding: 10px 16px;
            min-width: 0;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 260px;
            margin: 0 auto;
        }
        .equip-icone {
            font-size: 1.5em;
            color: #1976d2;
            flex-shrink: 0;
        }
        .equip-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .equip-nome {
            font-weight: 600;
            color: #222;
            font-size: 1em;
            margin-bottom: 0;
        }
        .equip-valor {
            font-size: 0.95em;
            color: #1976d2;
            margin-bottom: 0;
        }
        .equip-status {
            border-radius: 10px;
            padding: 2px 10px;
            font-size: 0.95em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 8px;
        }
        .equip-status.entregue {
            background: #e8f5e9;
            color: #28a745;
        }
        .equip-status.pendente {
            background: #fff3cd;
            color: #ffc107;
        }
        .equip-status i {
            font-size: 1em;
        }
        .equipamentos-explicacao {
            font-size: 0.97em;
            color: #555;
            margin-bottom: 6px;
            margin-top: 2px;
            text-align: center;
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
                <a href="dashboard_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'dashboard_atleta.php' ? ' active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a>
                <a href="perfil_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'perfil_atleta.php' ? ' active' : '' ?>"><i class="fas fa-user"></i> Perfil</a>
                <a href="treinos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'treinos.php' ? ' active' : '' ?>"><i class="fas fa-dumbbell"></i> Treinos</a>
                <a href="jogos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'jogos.php' ? ' active' : '' ?>"><i class="fas fa-futbol"></i> Jogos</a>
                <a href="mensagens.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'mensagens.php' ? ' active' : '' ?>"><i class="fas fa-envelope"></i> Mensagens</a>
                <a href="pagamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'pagamentos.php' ? ' active' : '' ?>"><i class="fas fa-euro-sign"></i> Pagamentos</a>
                <a href="atleta_equipamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'atleta_equipamentos.php' ? ' active' : '' ?>"><i class="fas fa-tshirt"></i> Equipamentos</a>
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

                <!-- Card de Equipamentos Atribuídos -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-tshirt"></i> Equipamentos Atribuídos</h3>
                    </div>
                    <div class="card-content">
                        <div class="equipamentos-explicacao">
                            Aqui podes consultar o estado dos teus equipamentos atribuídos.<br>
                            O estado será atualizado assim que o clube entregar cada item.
                        </div>
                        <div class="equipamentos-grid">
                            <div class="equipamento-card">
                                <div class="equip-icone"><i class="fas fa-tshirt"></i></div>
                                <div class="equip-info">
                                    <span class="equip-nome">Equip. Jogo</span>
                                    <span class="equip-valor"><?= htmlspecialchars($equip['equip_jogo']) ?></span>
                                </div>
                                <?php if ($equip['equip_jogo_status'] == 'entregue'): ?>
                                    <div class="equip-status entregue"><i class="fas fa-check-circle"></i> Entregue</div>
                                <?php else: ?>
                                    <div class="equip-status pendente"><i class="fas fa-clock"></i> Pendente</div>
                                <?php endif; ?>
                            </div>
                            <div class="equipamento-card">
                                <div class="equip-icone"><i class="fas fa-tshirt"></i></div>
                                <div class="equip-info">
                                    <span class="equip-nome">Alt. A</span>
                                    <span class="equip-valor"><?= htmlspecialchars($equip['alt_a']) ?></span>
                                </div>
                                <?php if ($equip['alt_a_status'] == 'entregue'): ?>
                                    <div class="equip-status entregue"><i class="fas fa-check-circle"></i> Entregue</div>
                                <?php else: ?>
                                    <div class="equip-status pendente"><i class="fas fa-clock"></i> Pendente</div>
                                <?php endif; ?>
                            </div>
                            <div class="equipamento-card">
                                <div class="equip-icone"><i class="fas fa-tshirt"></i></div>
                                <div class="equip-info">
                                    <span class="equip-nome">Alt. B</span>
                                    <span class="equip-valor"><?= htmlspecialchars($equip['alt_b']) ?></span>
                                </div>
                                <?php if ($equip['alt_b_status'] == 'entregue'): ?>
                                    <div class="equip-status entregue"><i class="fas fa-check-circle"></i> Entregue</div>
                                <?php else: ?>
                                    <div class="equip-status pendente"><i class="fas fa-clock"></i> Pendente</div>
                                <?php endif; ?>
                            </div>
                            <div class="equipamento-card">
                                <div class="equip-icone"><i class="fas fa-running"></i></div>
                                <div class="equip-info">
                                    <span class="equip-nome">Fato Treino</span>
                                    <span class="equip-valor"><?= htmlspecialchars($equip['fato_treino']) ?></span>
                                </div>
                                <?php if ($equip['fato_treino_status'] == 'entregue'): ?>
                                    <div class="equip-status entregue"><i class="fas fa-check-circle"></i> Entregue</div>
                                <?php else: ?>
                                    <div class="equip-status pendente"><i class="fas fa-clock"></i> Pendente</div>
                                <?php endif; ?>
                            </div>
                            <div class="equipamento-card">
                                <div class="equip-icone"><i class="fas fa-suitcase"></i></div>
                                <div class="equip-info">
                                    <span class="equip-nome">Mala</span>
                                    <span class="equip-valor"><?= htmlspecialchars($equip['mala']) ?></span>
                                </div>
                                <?php if ($equip['mala_status'] == 'entregue'): ?>
                                    <div class="equip-status entregue"><i class="fas fa-check-circle"></i> Entregue</div>
                                <?php else: ?>
                                    <div class="equip-status pendente"><i class="fas fa-clock"></i> Pendente</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 