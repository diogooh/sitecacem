<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensagem = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nome'], $_POST['email'], $_POST['mensagem'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $texto = trim($_POST['mensagem']);

    // Validação simples
    if ($nome && filter_var($email, FILTER_VALIDATE_EMAIL) && $texto) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'antunesdiogo06@gmail.com';
            $mail->Password = 'rfde ypaa agsx ohik'; // Palavra-passe de app do Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom($email, $nome);
            $mail->addAddress('antunesdiogo06@gmail.com', 'ACC Contacto');
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = 'Novo contacto do site ACC';
            $mail->Body = "<strong>Nome:</strong> " . htmlspecialchars($nome) . "<br>"
                        . "<strong>Email:</strong> " . htmlspecialchars($email) . "<br>"
                        . "<strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($texto));
            $mail->send();
            $mensagem = '<span class="success-message">Mensagem enviada com sucesso! Obrigado pelo seu contacto.</span>';
        } catch (Exception $e) {
            $mensagem = '<span class="error-message">Erro ao enviar mensagem. Tente novamente mais tarde.</span>';
        }
    } else {
        $mensagem = '<span class="error-message">Por favor, preencha todos os campos corretamente.</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactos - Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="contactos.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <section class="contact-container">
        <h2>Entre em Contacto Connosco</h2>

        <!-- Notificação de alerta -->
        <?php if (!empty($mensagem)): ?>
            <div class="alert" id="alert">
                <?php echo $mensagem; ?>
            </div>
            <script>
                setTimeout(function() {
                    document.getElementById("alert").style.display = "none";
                }, 3000); // Oculta a notificação após 3 segundos
            </script>
        <?php endif; ?>

        <form action="contactos.php" method="POST">
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="email" name="email" placeholder="Email" required>
        <textarea name="mensagem" placeholder="Escreva a sua mensagem..." rows="5" required style="resize: none;"></textarea>
        <button type="submit">Enviar</button>
        </form>


        <div class="contact-info">
            <h3>Atlético Clube do Cacém</h3>
            <p>📍 Rua Casal De Ouressa N° 20, 2635-600 Rio de Mouro, Portugal</p>
            <p>📞 Telefone: +351 912 345 678</p>
            <p>✉️ Email: contacto@clubecacem.com</p>
        </div>

        <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d1268.2891198359473!2d-9.31805480143778!3d38.7748636284573!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd1ece15b18761a9%3A0xf9a7e4ac604a3d2b!2sAtl%C3%A9tico%20Clube%20do%20Cac%C3%A9m!5e0!3m2!1spt-PT!2sus!4v1739795968208!5m2!1spt-PT!2sus" 
            width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" 
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </section>
</body>
<?php include 'footer.php'; ?>
</html>