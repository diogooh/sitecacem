<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'admin') {
    header("Location: login.php");
    exit();
}
require 'db.php';

// Estatísticas
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'];
$approvedUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'aprovado'")->fetch_assoc()['total'];
$pendingUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE status = 'pendente'")->fetch_assoc()['total'];

// Todos os usuários (agora também busca cip e numero_socio)
$users = $conn->query("SELECT id, nome, email, tipo, status, data_registro, cip, numero_socio FROM users ORDER BY data_registro DESC");
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - ACC</title>
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            text-align: center;
        }
        .stat-card h3 {
            color: #1a237e;
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
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
        .btn-edit, .btn-delete {
            padding: 8px 18px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1em;
            font-weight: 500;
            color: white;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-edit {
            background: #2196f3;
        }
        .btn-edit:hover {
            background: #1976d2;
        }
        .btn-delete {
            background: #f44336;
        }
        .btn-delete:hover {
            background: #c62828;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 24px;
            border-radius: 14px;
            width: 400px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.20);
        }
        .modal-close {
            float: right;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }
        .modal-content h3 {
            margin-top: 0;
            color: #1a237e;
        }
        .modal-content input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .btn-save {
            background: #1976d2;
            color: white;
            border: none;
            padding: 12px;
            margin-top: 15px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            font-size: 1em;
            transition: background 0.2s;
        }
        .btn-save:hover {
            background: #1565c0;
        }
        .staff-menu {
            flex-grow: 1;
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
                <a href="admin_dashboard.php" class="menu-item active">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_escaloes.php" class="menu-item">
                    <i class="fas fa-users"></i> Escalões
                </a>
                <a href="admin-pendentes.php" class="menu-item">
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
                <h1>Painel Administrativo</h1>
            </div>

            <!-- Estatísticas -->
            <div class="stats">
                <div class="stat-card">
                    <h3>Total de Utilizadores</h3>
                    <div class="number"><?= $totalUsers ?></div>
                </div>
                <div class="stat-card">
                    <h3>Utilizadores Aprovados</h3>
                    <div class="number"><?= $approvedUsers ?></div>
                </div>
                <div class="stat-card">
                    <h3>Utilizadores Pendentes</h3>
                    <div class="number"><?= $pendingUsers ?></div>
                </div>
            </div>

            <!-- Lista de Utilizadores -->
            <div class="card">
                <h2>Gestão de Utilizadores</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>CIP</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['nome']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= ucfirst($user['tipo']) ?></td>
                            <td><?= ucfirst($user['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($user['data_registro'])) ?></td>
                            <td><?= htmlspecialchars($user['cip']) ?></td>
                            <td>
                                <a href="admin_edit_user.php?id=<?= $user['id'] ?>" class="btn-edit">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                <form method="post" action="admin-apagar-utilizador.php" style="display:inline;" onsubmit="return confirm('Tem a certeza que deseja apagar este utilizador?');">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button class="btn-delete">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal-editar" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="fecharModal()">&times;</span>
            <h3>Editar Utilizador</h3>
            <form id="form-editar">
                <input type="hidden" name="id" id="user-id">
                <label>Nome:</label>
                <input type="text" name="nome" id="user-nome">
                <label>Email:</label>
                <input type="email" name="email" id="user-email">
                <label>Tipo:</label>
                <input type="text" name="tipo" id="user-tipo">
                <label>Status:</label>
                <input type="text" name="status" id="user-status">
                <button type="submit" class="btn-save">Guardar Alterações</button>
            </form>
        </div>
    </div>

    <script>
    function abrirModal(id) {
        fetch('obter-utilizador.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                document.getElementById('user-id').value = data.id;
                document.getElementById('user-nome').value = data.nome;
                document.getElementById('user-email').value = data.email;
                document.getElementById('user-tipo').value = data.tipo;
                document.getElementById('user-status').value = data.status;
                document.getElementById('modal-editar').style.display = 'block';
            });
    }

    function fecharModal() {
        document.getElementById('modal-editar').style.display = 'none';
    }

    document.getElementById('form-editar').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('atualizar-utilizador.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            alert('Utilizador atualizado com sucesso!');
            location.reload();
        });
    });
    </script>
</body>
</html>
