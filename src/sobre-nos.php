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
    <title>Sobre Nós - Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="../src/nav.css">
    <link rel="stylesheet" href="../src/footer.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 1000px;
            margin: 100px auto 50px auto;
            padding: 0 20px;
        }

        h1, h2 {
            color: #005fa3;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5rem;
        }

        h2 {
            margin-top: 40px;
            font-size: 1.8rem;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        ul {
            padding-left: 20px;
            margin-bottom: 20px;
        }

        ul li {
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .container {
                margin-top: 120px;
            }
        }
    </style>
</head>
<body>

<?php include '../src/nav.php'; ?>

<div class="container">
    <h1>Sobre Nós</h1>

    <h2>🏆 História e Tradição</h2>
    <p>Fundado a 28 de julho de 1941, o Atlético Clube do Cacém é uma instituição desportiva com mais de 80 anos de história. Ao longo das décadas, tem sido um pilar no desenvolvimento desportivo e comunitário no concelho de Sintra.</p>

    <h2>🎯 Missão e Valores</h2>
    <p>A nossa missão é formar atletas completos, promovendo tanto o desempenho desportivo como o sucesso académico e social. Valorizamos o equilíbrio entre o treino e os estudos, incentivando a responsabilidade, o respeito e o espírito de equipa.</p>

    <h2>🏟️ Infraestruturas</h2>
    <p>Contamos com um parque desportivo multifuncional, que inclui:</p>
    <ul>
        <li>Pavilhão desportivo para andebol</li>
        <li>Jardim de infância com capacidade para 80 crianças</li>
        <li>Restaurante e cervejaria com 60 lugares</li>
        <li>Ginásio com diversas modalidades (Kickboxing, Muay Thai, Jiu-Jitsu, etc.)</li>
    </ul>

    <h2>⚽ Modalidades</h2>
    <p>Oferecemos diversas atividades desportivas, com destaque para:</p>
    <ul>
        <li><strong>Andebol:</strong> equipas masculinas e femininas, formação e competição</li>
    </ul>

    <h2>🤝 Envolvimento com a Comunidade</h2>
    <p>Promovemos a integração de atletas, treinadores, dirigentes e famílias num ambiente saudável e formativo. Acreditamos que o desporto é uma ferramenta poderosa para moldar cidadãos mais conscientes, ativos e solidários.</p>
</div>

<?php include '../src/footer.php'; ?>

</body>
</html>
