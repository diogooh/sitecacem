<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'admin') {
    header("Location: login.php");
    exit();
}
require 'db.php';

$user_id = $_GET['id'] ?? 0;

$user = null;
if ($user_id) {
    $stmt = $conn->prepare("SELECT id, nome, email, tipo, status, cip, numero_socio FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    die("Utilizador não encontrado.");
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $tipo = $_POST['tipo'];
    $status = $_POST['status'];
    $cip = $_POST['cip'] ?? null;
    $numero_socio = $_POST['numero_socio'] ?? null;

    $stmt = $conn->prepare("UPDATE users SET nome = ?, email = ?, tipo = ?, status = ?, cip = ?, numero_socio = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $nome, $email, $tipo, $status, $cip, $numero_socio, $id);

    if ($stmt->execute()) {
        $message = "Utilizador atualizado com sucesso!";
        $message_type = 'success';

        // Check if the 'Guardar e Voltar' button was pressed
        if (isset($_POST['save_and_return'])) {
             header("Location: admin_dashboard.php");
             exit();
        }

        // Re-fetch user data after update if not redirecting
        $stmt_re = $conn->prepare("SELECT id, nome, email, tipo, status, cip, numero_socio FROM users WHERE id = ?");
        $stmt_re->bind_param("i", $id);
        $stmt_re->execute();
        $result_re = $stmt_re->get_result();
        $user = $result_re->fetch_assoc();
        $stmt_re->close();

    } else {
        $message = "Erro ao atualizar utilizador: " . $conn->error;
        $message_type = 'error';
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Utilizador - Painel Administrativo</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f6fb;
            display: flex; /* Use flexbox for layout */
        }
        .dashboard-layout {
            display: flex;
            width: 100%;
        }
        .dashboard-sidebar {
            width: 250px; /* Fixed width for sidebar */
            flex-shrink: 0; /* Prevent sidebar from shrinking */
        }
        .dashboard-content {
            flex-grow: 1; /* Allow content to take remaining width */
            padding: 40px 30px;
            margin-left: 250px; /* Add margin to content to avoid being hidden by fixed sidebar */
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
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"], 
        .form-group input[type="email"], 
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in element's total width */
        }
        /* Styles for the buttons */
        .form-buttons {
            margin-top: 20px;
            display: flex;
            gap: 10px; /* Space between buttons */
            align-items: center;
        }

        .form-buttons .btn-save,
        .form-buttons .btn-cancel {
            /* Inherit base button styles or define new ones */
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: 500;
            font-size: 1em;
            text-decoration: none; /* For the cancel link */
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex; /* Align text and icons if any */
            align-items: center;
            justify-content: center;
        }

        .form-buttons .btn-save {
            background: #1976d2;
            color: white;
            border: none;
        }

        .form-buttons .btn-save:hover {
            background: #1565c0;
        }

        .form-buttons .btn-cancel {
            background-color: #6c757d; /* Grey color for cancel */
            color: white;
            border: none;
        }

        .form-buttons .btn-cancel:hover {
            background-color: #5a6268;
        }

        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
         /* Styles for the sidebar, copied from admin_dashboard.php for consistency */
        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 100%);
            color: white;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            overflow-y: auto;
            width: 250px;
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
            color: white; /* Ensure default color is white */
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
        .staff-menu {
            flex-grow: 1;
        }
        .logout-button {
            margin-top: auto;
            padding: 15px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        .btn-logout {
             display: flex;
            align-items: center;
            justify-content: center;
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
            transform: translateX(0) scale(1.03);
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
                <!-- Logo ou imagem do utilizador -->
                <!-- <img src="../img/logoclub.png" alt="Logo"> -->
                <h3>Administração</h3>
                <p>Painel de Controlo</p>
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
                <!-- Outros itens do menu podem ser adicionados aqui -->
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
            <div class="card">
                <h2>Editar Utilizador: <?= htmlspecialchars($user['nome']) ?></h2>

                <?php if ($message): ?>
                    <div class="message <?= $message_type ?>">
                        <?= $message ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
                    
                    <div class="form-group">
                        <label for="nome">Nome:</label>
                        <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($user['nome']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo:</label>
                         <select id="tipo" name="tipo" required>
                            <option value="atleta" <?= $user['tipo'] == 'atleta' ? 'selected' : '' ?>>Atleta</option>
                            <option value="treinador" <?= $user['tipo'] == 'treinador' ? 'selected' : '' ?>>Treinador</option>
                            <option value="dirigente" <?= $user['tipo'] == 'dirigente' ? 'selected' : '' ?>>Dirigente</option>
                            <option value="admin" <?= $user['tipo'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="pendente" <?= $user['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="aprovado" <?= $user['status'] == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                            <option value="rejeitado" <?= $user['status'] == 'rejeitado' ? 'selected' : '' ?>>Rejeitado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cip">CIP:</label>
                        <input type="text" id="cip" name="cip" value="<?= htmlspecialchars($user['cip']) ?>">
                    </div>
                    
                    <div class="form-buttons">
                        <button type="submit" name="save_and_return" class="btn-save">Guardar e Voltar</button>
                        <a href="admin_dashboard.php" class="btn-cancel">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 