<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['status'] != 'pendente') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovação Pendente - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/nav.css">
    <link rel="stylesheet" href="/sitecacem/src/pending.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    
    <div class="pending-container">
        <div class="pending-box">
            <div class="pending-icon">
                <i class="fas fa-user-clock"></i>
            </div>
            
            <h2>Registro em Análise</h2>
            
            <p class="status-text">
                Obrigado por se registrar! Seu cadastro está sendo analisado pela nossa equipe administrativa.
            </p>

            <div class="loading-indicator">
                <div class="loading-circle"></div>
            </div>

            <div class="status-steps">
                <div class="step-item">
                    <i class="fas fa-check-circle"></i>
                    <span class="step-text">Formulário de registro enviado com sucesso</span>
                </div>
                <div class="step-item">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span class="step-text">Aguardando aprovação administrativa</span>
                </div>
                <div class="step-item">
                    <i class="far fa-clock"></i>
                    <span class="step-text">Você receberá um email quando sua conta for ativada</span>
                </div>
            </div>

            <div class="contact-info">
                <p>Dúvidas? Entre em contato conosco através do email <a href="mailto:suporte@acc.pt">suporte@acc.pt</a></p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html> 