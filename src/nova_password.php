<?php
session_start();
require 'db.php';

// Verifica se o token foi passado
if (!isset($_GET['token'])) {
    echo 'Token inválido.';
    exit();
}
$token = $_GET['token'];

// Busca o token na base de dados
$stmt = $conn->prepare('SELECT pr.user_id, u.email, u.nome, pr.expires_at, pr.used FROM password_resets pr JOIN users u ON pr.user_id = u.id WHERE pr.token = ? LIMIT 1');
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();
$reset = $result->fetch_assoc();
$stmt->close();

if (!$reset || $reset['used'] || strtotime($reset['expires_at']) < time()) {
    echo 'Este link de recuperação é inválido ou expirou.';
    exit();
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    $confirma = $_POST['confirma'] ?? '';
    if (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirma) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Atualiza a senha do usuário
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        if (!$stmt) {
            echo '<div class="container"><div class="erro">Erro ao preparar query de atualização de senha: ' . $conn->error . '</div></div>';
            exit();
        }
        $stmt->bind_param('si', $hash, $reset['user_id']);
        $stmt->execute();
        $stmt->close();
        // Marca o token como usado
        $stmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
        if (!$stmt) {
            echo '<div class="container"><div class="erro">Erro ao preparar query de atualização do token: ' . $conn->error . '</div></div>';
            exit();
        }
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
        echo '<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Sucesso!</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(120deg, #f5f5f5 60%, #007bff11 100%); }
        .container { max-width: 400px; margin: 80px auto; background: #fff; padding: 40px 30px 30px 30px; border-radius: 12px; box-shadow: 0 4px 24px #0002; text-align: center; }
        .sucesso { color: #28a745; font-size: 1.2em; margin-bottom: 20px; }
        .btn-login { display: inline-block; margin-top: 18px; padding: 10px 28px; background: #007bff; color: #fff; border: none; border-radius: 5px; font-size: 1em; text-decoration: none; transition: background 0.2s; }
        .btn-login:hover { background: #0056b3; }
        @media (max-width: 500px) { .container { padding: 20px 8px; } }
    </style>
</head>
<body>
<div class="container">
    <div class="sucesso">Senha alterada com sucesso! Pode agora fazer login.</div>
    <a class="btn-login" href="login.php">Ir para Login</a>
</div>
</body>
</html>';
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Nova Password</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(120deg, #f5f5f5 60%, #007bff11 100%); }
        .container { max-width: 400px; margin: 60px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 2px 12px #0001; }
        h2 { text-align: center; color: #007bff; margin-bottom: 24px; }
        .erro { color: #dc3545; text-align: center; margin-bottom: 12px; }
        label { font-weight: bold; }
        input[type=password], input[type=submit] { width: 100%; padding: 12px; margin: 10px 0 18px 0; border-radius: 5px; border: 1px solid #ccc; font-size: 1em; }
        input[type=submit] { background: #007bff; color: #fff; border: none; cursor: pointer; font-weight: bold; transition: background 0.2s; }
        input[type=submit]:hover { background: #0056b3; }
        @media (max-width: 500px) { .container { padding: 18px 5px; } }
    </style>
</head>
<body>
<div class="container">
    <h2>Definir Nova Password</h2>
    <?php if (isset($erro)) echo '<div class="erro">' . $erro . '</div>'; ?>
    <form method="post">
        <label>Nova Password:</label>
        <input type="password" name="senha" required minlength="6">
        <label>Confirmar Password:</label>
        <input type="password" name="confirma" required minlength="6">
        <input type="submit" value="Alterar Password">
    </form>
</div>
</body>
</html> 