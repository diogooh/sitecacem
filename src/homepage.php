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
    <link rel="stylesheet" href="/sitecacem/src/nav.css">
    <link rel="stylesheet" href="/sitecacem/src/homepage.css">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    
    <section class="content">
        <div class="text-container">
            <h1>Bem-vindos ao site oficial da formação do Atlético Clube do Cacém.</h1>
            <p>
                O nosso clube é mais do que um lugar para treinar, é uma segunda casa para atletas, treinadores, famílias e fãs. 
                Aqui, trabalhamos todos os dias para construir uma cultura de respeito, compromisso e paixão pelo desporto.
            </p>
            <a href="sobre-nos.php">Clica aqui para saberes mais de nós -></a>
        </div>
        <img src="/sitecacem/img/fotojogo1.jpeg" alt="Foto da Equipa">
    </section>
    <!-- Secção de Notícias Recentes -->
    <section class="noticias">
        <div class="divisor-amarelo divisor-section"></div>
        <h2>Notícias Recentes</h2>
        <div class="noticias-grid">
            <div class="noticia-card">
                <img src="/sitecacem/img/fotojogo1.jpeg" alt="Vitória no último jogo">
                <div class="noticia-content">
                    <h3>Vitória emocionante no último jogo!</h3>
                    <p>A nossa equipa de formação conquistou uma vitória importante no passado sábado, mostrando grande espírito de equipa e dedicação.</p>
                    <a href="#" class="read-more">Ler mais</a>
                </div>
            </div>
            <div class="noticia-card">
                <img src="/sitecacem/img/caneca.png" alt="Novo equipamento">
                <div class="noticia-content">
                    <h3>Novos equipamentos já disponíveis</h3>
                    <p>Os novos equipamentos oficiais do clube já estão disponíveis para encomenda. Garanta já o seu e apoie o clube!</p>
                    <a href="#" class="read-more">Ler mais</a>
                </div>
            </div>
            <div class="noticia-card">
                <img src="/sitecacem/img/camisola.png" alt="Treino especial">
                <div class="noticia-content">
                    <h3>Treino especial com ex-atleta internacional</h3>
                    <p>Esta semana tivemos a visita de um ex-atleta internacional que partilhou dicas e motivou os nossos jovens atletas.</p>
                    <a href="#" class="read-more">Ler mais</a>
                </div>
            </div>
        </div>
    </section>
    <!-- Testemunhos/Depoimentos -->
    <section class="testemunhos">
        <div class="divisor-amarelo divisor-section"></div>
        <h2>Testemunhos</h2>
        <div class="testemunhos-grid">
            <div class="testemunho-card">
                <p>"O clube é uma verdadeira família! O meu filho adora treinar aqui."</p>
                <span>- Ana, mãe de atleta</span>
            </div>
            <div class="divisor-amarelo divisor-testemunho"></div>
            <div class="testemunho-card">
                <p>"Aprendi muito e fiz grandes amigos. Recomendo a todos!"</p>
                <span>- João, atleta sub-17</span>
            </div>
            <div class="divisor-amarelo divisor-testemunho"></div>
            <div class="testemunho-card">
                <p>"O ambiente é fantástico e os treinadores são muito dedicados."</p>
                <span>- Pedro, treinador</span>
            </div>
        </div>
    </section>
</body>
<?php include __DIR__ . '/footer.php'; ?>
</html>
