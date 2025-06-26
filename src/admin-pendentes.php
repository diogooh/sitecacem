<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'admin') {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar registros pendentes
$stmt = $conn->prepare("SELECT id, nome, email, tipo, data_registro FROM users WHERE status = 'pendente'");
$stmt->execute();
$pendentes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Pendentes - ACC</title>
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
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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
        .dashboard-content {
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
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            color: #1a237e;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f8f9fa;
        }
        .tipo-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            color: #333;
            background: white;
        }
        .cip-input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            width: 150px;
        }
        .btn-approve, .btn-reject {
            padding: 8px 18px;
            border-radius: 5px;
            border: none;
            font-weight: 500;
            font-size: 1em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-approve {
            background: #4caf50;
            color: white;
        }
        .btn-approve:hover {
            background: #388e3c;
            transform: scale(1.05);
        }
        .btn-reject {
            background: #f44336;
            color: white;
        }
        .btn-reject:hover {
            background: #d32f2f;
            transform: scale(1.05);
        }
        .actions-cell {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .logout-button {
            margin-top: auto;
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .btn-logout {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1.05em;
            background: rgba(244, 67, 54, 0.9);
            transition: all 0.2s;
            width: 100%;
            border: none;
            cursor: pointer;
        }
        .btn-logout:hover {
            background: #f44336;
            transform: translateX(7px) scale(1.03);
        }
        .btn-logout i {
            margin-right: 12px;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <h3>Administração</h3>
                <p>Painel de Controle</p>
            </div>
            
            <div class="staff-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_escaloes.php" class="menu-item">
                    <i class="fas fa-users"></i> Escalões
                </a>
                <a href="admin-pendentes.php" class="menu-item active">
                    <i class="fas fa-clock"></i> Pedidos Pendentes
                </a>
                <a href="admin_financas.php" class="menu-item">
                    <i class="fas fa-dollar-sign"></i> Finanças
                </a>
            </div>
            <div class="logout-button">
                <form method="post" action="logout.php">
                    <button type="submit" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <div class="section-header">
                <h1>Aprovações Pendentes</h1>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Ação realizada com sucesso!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> Ocorreu um erro ao processar a solicitação.
                </div>
            <?php endif; ?>

            <div class="card">
                <?php if ($pendentes->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Data Registro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($user = $pendentes->fetch_assoc()): ?>
                        <tr>
                            <form method="post" action="process_approval.php">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

                                <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>

                                <td>
                                    <select name="tipo" class="tipo-select">
                                        <option value="atleta" <?php if ($user['tipo'] == 'atleta') echo 'selected'; ?>>Atleta</option>
                                        <option value="treinador" <?php if ($user['tipo'] == 'treinador') echo 'selected'; ?>>Treinador</option>
                                        <option value="dirigente" <?php if ($user['tipo'] == 'dirigente') echo 'selected'; ?>>Dirigente</option>
                                    </select>
                                </td>

                                <td><?php echo date('d/m/Y H:i', strtotime($user['data_registro'])); ?></td>

                                <td class="actions-cell">
                                    <input type="text" name="cip" class="cip-input" placeholder="CIP obrigatório" />
                                    <button type="submit" name="action" value="approve" class="btn-approve">
                                        <i class="fas fa-check"></i> Aprovar
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn-reject">
                                        <i class="fas fa-times"></i> Rejeitar
                                    </button>
                                </td>
                            </form>
                        </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Não há registros pendentes de aprovação.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.querySelectorAll('form').forEach(form => {
            const select = form.querySelector('.tipo-select');
            const cipInput = form.querySelector('.cip-input');

            function toggleCIP() {
                const tipo = select.value;
                if (["atleta", "treinador", "dirigente"].includes(tipo)) {
                    cipInput.style.display = 'inline-block';
                    cipInput.required = true;
                } else {
                    cipInput.style.display = 'none';
                    cipInput.required = false;
                    cipInput.value = '';
                }
            }

            select.addEventListener('change', toggleCIP);
            toggleCIP(); // chama no início para definir com base no valor carregado
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</body>
</html>
