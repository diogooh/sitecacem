<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Password - ACC</title>
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="login.css">
    <style>
        .recovery-container {
            max-width: 400px;
            margin: 120px auto 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            padding: 32px 28px;
            text-align: center;
        }
        .recovery-container h2 {
            color: #1a237e;
            margin-bottom: 18px;
        }
        .recovery-container input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 18px;
            font-size: 1em;
        }
        .recovery-container button {
            background: #1976d2;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-weight: 500;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .recovery-container button:hover {
            background: #1565c0;
        }
        .recovery-container a {
            display: block;
            margin-top: 18px;
            color: #1976d2;
            text-decoration: none;
        }
        .recovery-container a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<?php include 'nav.php'; ?>
    <div class="recovery-container">
        <h2>Recuperar Palavra-passe</h2>
        <form method="post" action="enviar_recovery.php">
            <input type="email" name="email" placeholder="O seu email" required>
            <button type="submit">Enviar link de recuperação</button>
        </form>
        <a href="login.php">Voltar ao início de sessão</a>
    </div>
    <?php include 'footer.php'; ?>
</body>
</html> 