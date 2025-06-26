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
    <title>Andebol - Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="../src/nav.css">
    <link rel="stylesheet" href="../src/footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
    body { /* Ensure default text color is black */
        color: black;
    }
    .content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        margin-top: 100px;
        font-family: 'Roboto', sans-serif;
    }
    .content p,
    .content ul li {
        color: black;
    }
    .hero-section {
        position: relative;
        margin-bottom: 3rem;
    }
    .hero-image {
        width: 100%;
        height: 500px;
        object-fit: cover;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    .hero-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        padding: 2rem;
        color: white;
    }
    .hero-overlay h1 {
        font-size: 3rem;
        margin: 0;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin: 2rem 0;
    }
    .info-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        color: black;
    }
    .info-card h3 {
        color: #0077cc;
        margin-top: 0;
    }
    .schedule {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: 10px;
        margin: 2rem 0;
        color: black;
    }
    .schedule h2 {
        color: #0077cc;
        margin-top: 0;
    }
    .btn {
        display: inline-block;
        padding: 12px 30px;
        background-color: #0077cc;
        color: #fff;
        text-decoration: none;
        border-radius: 25px;
        transition: all 0.3s ease;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .btn:hover {
        background-color: #005fa3;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,119,204,0.3);
    }
</style>

</head>
<body>

<?php include '../src/nav.php'; ?>

<div class="content">
    <div class="hero-section">
        <img src="../img/jogo_fundo.png" alt="Equipa de Andebol" class="hero-image">
        <div class="hero-overlay">
            <h1>Andebol - Equipa Principal</h1>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-card">
            <h3>Treinador Principal</h3>
            <p>Helder Moutinho</p>
            <p>Com vasta experiência no andebol nacional, lidera a equipa com dedicação e profissionalismo.</p>
        </div>
        <div class="info-card">
            <h3>Competições</h3>
            <p>Campeonato Nacional da 3ª Divisão</p>
            <p>Participamos ativamente nas principais competições nacionais.</p>
        </div>
        <div class="info-card">
            <h3>Escalão</h3>
            <p>Sénior Masculino</p>
            <p>Equipa principal do clube com atletas experientes e dedicados.</p>
        </div>
    </div>

    <div class="schedule">
        <h2>Horários de Treino</h2>
        <p>Os nossos treinos realizam-se em dois pavilhões desportivos da junta de freguesia:</p>
        <ul>
            <li>Segunda-feira: 21h30 - 23h00</li>
            <li>Terça-feira: 20h30 - 22h30</li>
            <li>Quinta-feira: 20h30 - 22h30</li>
        </ul>
    </div>

    <p>O Atlético Clube do Cacém orgulha-se da sua equipa de andebol sénior. Com uma longa tradição e várias conquistas regionais, a nossa equipa treina regularmente para manter o alto nível competitivo e representa o clube nas principais competições nacionais.</p>

    <p>Procuramos atletas com experiência competitiva que queiram fazer parte de uma equipa ambiciosa e dedicada. Oferecemos um ambiente profissional, instalações de qualidade e um programa de treino estruturado para o desenvolvimento contínuo dos nossos atletas.</p>

    <a href="../src/contactos.php" class="btn">Quero inscrever-me</a>
</div>

<?php include '../src/footer.php'; ?>

</body>
</html>
