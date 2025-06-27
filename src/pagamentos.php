<?php
session_start();

// Verifica se o utilizador está logado e é um atleta
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'atleta') {
    header("Location: login.php");
    exit();
}

require 'db.php';

$user_id = $_SESSION['user_id'];

// Buscar informações do atleta
$stmt_user = $conn->prepare("SELECT nome, foto_perfil, cip FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$atleta = $user_result->fetch_assoc();
$stmt_user->close();

// Buscar mensalidades do atleta, ordenadas por ano e mês
$stmt_mensalidades = $conn->prepare("SELECT mes, ano, valor, status, data_pagamento FROM mensalidades WHERE atleta_id = ? ORDER BY ano DESC, mes DESC");
$stmt_mensalidades->bind_param("i", $user_id);
$stmt_mensalidades->execute();
$mensalidades = $stmt_mensalidades->get_result();
$stmt_mensalidades->close();

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Mensalidades - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f4f6fb;
        }
        .dashboard-layout {
            display: flex;
        }
        .dashboard-content {
            flex-grow: 1;
            padding: 40px 30px 30px 30px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            padding: 24px;
            margin-bottom: 24px;
        }
        .card h2 {
            color: #1a237e;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
        }
        .financial-table th,
        .financial-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .financial-table th {
            background-color: #1a237e;
            color: white;
            font-weight: bold;
        }
        .financial-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
            margin: 2px;
            display: inline-block;
        }
        .status-pendente {
            background: #fff3e0;
            color: #e65100;
        }
        .status-paga {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        /* Add styling for payment statistics card */
        .payment-status-card .card-header h3 {
            color: #1a237e;
        }
        .payment-status-card .payment-status {
            display: flex;
            flex-direction: column;
        }
        .payment-status-card .payment-info p strong {
             color: #1a237e;
        }
    </style>
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
                <a href="dashboard_atleta.php" class="menu-item">
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
                </a>
                <a href="pagamentos.php" class="menu-item active">
                    <i class="fas fa-euro-sign"></i> Pagamentos
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
            <div class="section-header">
                <h1>Minhas Mensalidades</h1>
            </div>

            <div class="card">
                <h2>Histórico de Mensalidades</h2>
                <?php if ($mensalidades->num_rows > 0): ?>
                    <table class="financial-table">
                        <thead>
                            <tr>
                                <th>Mês/Ano</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data Pagamento</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($mensalidade = $mensalidades->fetch_assoc()): ?>
                                <tr>
                                    <td><?= sprintf('%02d', $mensalidade['mes']) ?>/<?= $mensalidade['ano'] ?></td>
                                    <td><?= htmlspecialchars($mensalidade['valor']) ?> €</td>
                                    <td>
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
                                    </td>
                                    <td><?= htmlspecialchars($mensalidade['data_pagamento']) ?: 'N/A' ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Não há registo de mensalidades para o seu perfil.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 