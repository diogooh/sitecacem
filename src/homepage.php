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
    <title>Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="homepage.css">
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <section class="content">
        <div class="text-container">
            <h1>Bem-vindos ao site oficial da formação do Atlético Clube do Cacém.</h1>
            <p>
                O nosso clube é mais do que um lugar para treinar, é uma segunda casa para atletas, treinadores, famílias e fãs. 
                Aqui, trabalhamos todos os dias para construir uma cultura de respeito, compromisso e paixão pelo desporto.
            </p>
            <a href="sobre-nos.php">Clica aqui para saberes mais de nós -></a>
        </div>
        <img src="/img/fotojogo1.jpeg" alt="Foto da Equipa">
    </section>
</body>
<?php include 'footer.php'; ?>
</html>
