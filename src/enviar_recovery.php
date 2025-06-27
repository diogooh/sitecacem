<?php
session_start();
require 'db.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare('SELECT id, nome FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user) {
        // Gerar token seguro
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
        // Guardar token e expiração na base de dados (tabela password_resets)
        $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL,
            expires_at DATETIME NOT NULL,
            used TINYINT(1) DEFAULT 0,
            INDEX(user_id), INDEX(token)
        )");
        $stmt = $conn->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $user['id'], $token, $expira);
        $stmt->execute();
        $stmt->close();

        // Enviar email com link de recuperação
        $mail = new PHPMailer(true);
        try {
            // Habilitar debug
            $mail->SMTPDebug = 2; // Habilita debug detalhado
            $mail->Debugoutput = 'error_log'; // Envia debug para error_log do PHP

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'antunesdiogo06@gmail.com';
            $mail->Password = 'rfde ypaa agsx ohik'; // Palavra-passe de app do Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('antunesdiogo06@gmail.com', 'ACC'); // Usando o mesmo email como remetente
            $mail->addAddress($email, $user['nome']);
            
            $mail->CharSet = 'UTF-8'; // Garante que caracteres especiais sejam exibidos corretamente
            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Palavra-passe - ACC';
            $link = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/nova_password.php?token=' . $token;
            $mail->Body = "Olá {$user['nome']},<br><br>Recebemos um pedido para recuperação da sua palavra-passe na plataforma ACC.<br>Para definir uma nova palavra-passe, por favor clique no link abaixo:<br><a href='$link'>Definir nova palavra-passe</a><br><br>Se não foi você que fez este pedido, ignore este email.<br><br>Com os melhores cumprimentos,<br>Associação Clube de Cacém";
            $mail->send();
            $_SESSION['recovery_msg'] = 'Foi enviado um email com instruções para recuperar a sua password.';
        } catch (Exception $e) {
            $_SESSION['recovery_msg'] = 'Erro ao enviar email de recuperação. Tente novamente mais tarde.';
        }
    } else {
        $_SESSION['recovery_msg'] = 'Se o email existir, receberá instruções para recuperar a password.';
    }
    header('Location: recuperar_password.php');
    exit();
} else {
    header('Location: recuperar_password.php');
    exit();
} 