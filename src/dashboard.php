<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Cliente - Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="/sitecacem/src/nav.css">
    <link rel="stylesheet" href="/sitecacem/src/dashboard.css">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <section class="dashboard-container">
        <div class="dashboard-box">
            <h2>Bem-vindo à Dashboard</h2>
            <p>Aqui você pode acessar seus dados e configurações.</p>
        </div>
    </section>
</body>
<?php include __DIR__ . '/footer.php'; ?>
</html>
