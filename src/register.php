<?php
session_start();
require 'db.php';

$erro = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $data_nascimento = $_POST['data_nascimento'];
    $nif = $_POST['nif'];
    $telefone = $_POST['telefone'];
    $tipo = 'atleta';

    // Verificar se email já existe
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erro = "Este email já está registado.";
        } else {
            // Inserir novo usuário
            $stmt = $conn->prepare("INSERT INTO users (nome, email, password_hash, data_nascimento, nif, telefone, tipo, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendente')");
            if ($stmt) {
                $stmt->bind_param("sssssss", $nome, $email, $password, $data_nascimento, $nif, $telefone, $tipo);
                
                if ($stmt->execute()) {
                    header("Location: pending_approval.php");
                    exit();
                } else {
                    $erro = "Erro ao registar. Tente novamente.";
                }
            } else {
                $erro = "Erro na preparação da query.";
            }
        }
    } else {
        $erro = "Erro na verificação do email.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/nav.css">
    <link rel="stylesheet" href="/sitecacem/src/register.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>

    <div class="register-page">
        <div class="register-wrapper">
            <!-- Coluna da esquerda -->
            <div class="register-left">
                <div class="register-info">
                    <img src="../img/logoclub.png" alt="ACC Logo" class="register-logo">
                    <h1>Bem-vindo à ACC</h1>
                    <p>Registe-se para fazer parte da nossa equipa e aceder aos recursos exclusivos do clube!</p>
                    
                    <div class="benefits-list">
                        <div class="benefit-item">
                            <i class="fas fa-users"></i>
                            <span>Acesso exclusivo para atletas, treinadores e dirigentes</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-medal"></i>
                            <span>Gestão de treinos, jogos e informações do clube</span>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-star"></i>
                            <span>Desenvolva o seu potencial desportivo e organização no clube</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna da direita -->
            <div class="register-right">
                <div class="register-form-container">
                    <h2>Criar Nova Conta</h2>
                    <?php if(isset($erro) && $erro != ''): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $erro; ?>
                        </div>
                    <?php endif; ?>

                    <div class="approval-notice">
                        <i class="fas fa-info-circle"></i>
                        <div class="notice-content">
                            <h4>Registo para Membros do Clube (Atletas, Treinadores, Dirigentes, Administradores)</h4>
                            <p>Após o registo, a sua conta ficará pendente de aprovação por um administrador do clube. Irá receber um email assim que a sua conta for ativada e o seu tipo de utilizador for definido.</p>
                        </div>
                    </div>

                    <form method="POST" action="" class="register-form">
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Palavra-passe</label>
                                <input type="password" id="password" name="password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirmar Palavra-passe</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="data_nascimento">Data de Nascimento</label>
                                <input type="date" id="data_nascimento" name="data_nascimento" required>
                            </div>
                            <div class="form-group">
                                <label for="telefone">Telemóvel</label>
                                <input type="tel" id="telefone" name="telefone" pattern="[0-9]{9}" maxlength="9" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="nif">NIF</label>
                            <input type="text" id="nif" name="nif" pattern="[0-9]{9}" maxlength="9" required>
                        </div>

                        <div class="form-info">
                            <p><i class="fas fa-info-circle"></i> A sua conta será validada por um administrador.</p>
                        </div>

                        <button type="submit" class="register-btn">
                            Criar Conta <i class="fas fa-arrow-right"></i>
                        </button>

                        <div class="login-link">
                            Já tem conta? <a href="login.php">Entrar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('password').addEventListener('input', validatePassword);
        document.getElementById('confirm_password').addEventListener('input', validatePassword);

        function validatePassword() {
            const password = document.getElementById('password');
            const confirm = document.getElementById('confirm_password');
            if (password.value !== confirm.value) {
                confirm.setCustomValidity('As palavras-passe não coincidem');
            } else {
                confirm.setCustomValidity('');
            }
        }
    </script>
</body>
</html>
