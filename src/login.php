<?php
include('db.php');
include('nav.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$mensagem = ""; // Variável para armazenar mensagens de erro

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $senha = $_POST["password_hash"]; // Pegando a senha corretamente

    // Consulta SQL com o nome correto das colunas
    $stmt = $conn->prepare("SELECT id, password_hash, tipo, status FROM users WHERE email = ?");
    
    if (!$stmt) {
        die("Erro ao preparar: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Verifica a senha
        if (password_verify($senha, $row["password_hash"])) {
            $_SESSION["user_id"] = $row["id"];
            $_SESSION["tipo"] = $row["tipo"];
            $_SESSION["status"] = $row["status"];

            // Redirecionamento baseado no tipo e status
            switch ($row["tipo"]) {
                case 'atleta':
                    if ($row["status"] == 'aprovado') {
                        header("Location: dashboard_atleta.php");
                    } else {
                        header("Location: pending_approval.php");
                    }
                    break;
                case 'treinador':
                case 'dirigente':
                    header("Location: dashboard_staff.php");
                    break;
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                default: // Tipos não permitidos (ex: socio, adepto)
                    // Destruir a sessão e redirecionar para a página de login com mensagem de erro
                    session_unset();
                    session_destroy();
                    $mensagem = "Seu tipo de usuário não tem acesso ao sistema.";
                    // Não exit aqui para que a mensagem seja exibida
            }
            // Se a mensagem foi definida no default, não redirecionar imediatamente aqui
            if (empty($mensagem)) {
                exit(); // Apenas redirecionar se não houver mensagem de erro do default
            }
        } else {
            $mensagem = "Palavra-passe incorreta!";
        }
    } else {
        $mensagem = "Utilizador não encontrado!";
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
    <title>Login - Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'nav.php'; ?>

    <?php if (!empty($mensagem)): ?>
        <div class="alert">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $mensagem; ?>
        </div>
        <script>
            setTimeout(function() {
                document.querySelector(".alert").style.display = "none";
            }, 5000);
        </script>
    <?php endif; ?>

    <section class="login-container">
        <div class="login-box">
            <div class="logo-container">
                <img src="../img/logoclub.png" alt="Logo ACC" class="logo">
            </div>
            <h2>Bem-vindo</h2>
            <p class="subtitle">Introduza as suas credenciais para aceder à sua conta</p>
            
            <form method="post" action="">
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password_hash" placeholder="Palavra-passe" required>
                </div>
                <button type="submit">
                    <i class="fas fa-sign-in-alt"></i>
                    Entrar
                </button>
            </form>
            
            <div class="forgot-password-link" style="text-align:center; margin-top:15px;">
                <a href="recuperar_password.php">Esqueceu-se da palavra-passe?</a>
            </div>
            
            <p class="register-link">
                Não tem uma conta? 
                <a href="register.php">
                    <i class="fas fa-user-plus"></i>
                    Registe-se
                </a>
            </p>
        </div>
    </section>
</body>
</html>