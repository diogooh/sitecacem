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
    <title>Andebol Formação - Atlético Clube do Cacém</title>
    <link rel="stylesheet" href="../src/nav.css">
    <link rel="stylesheet" href="../src/footer.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
<style>
    body {
        color: black !important; /* Ensure all text on the body is black by default, and override any external stylesheets */
    }
    .content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        margin-top: 120px; /* Adjusted to give more space from the top navigation */
        font-family: 'Roboto', sans-serif;
    }
    .content p,
    .content ul li,
    .age-group-card p,
    .age-group-card ul li,
    .benefits p,
    .benefits .benefit-item p, /* Added for specific targeting */
    .schedule-section p,
    .schedule-item p { /* Explicitly target all relevant text elements to be black */
        color: black !important;
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
        background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0.5));
        padding: 2rem;
    }
    .hero-overlay h1, .hero-overlay p { /* Target hero title and paragraphs explicitly */
        font-size: 3rem;
        margin: 0;
        text-shadow: none;
        color: white !important; /* Force this to be white */
    }
    .age-groups {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
        margin: 2rem 0;
    }
    .age-group-card {
        background: white;
        padding: 1.5rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    .age-group-card:hover {
        transform: translateY(-5px);
    }
    .age-group-card h3 {
        color: #0077cc;
        margin-top: 0;
        border-bottom: 2px solid #0077cc;
        padding-bottom: 0.5rem;
    }
    .age-group-card ul {
        list-style-type: disc; /* Ensure standard disc bullet points */
        padding-left: 25px !important; /* Force padding for indentation */
        margin-left: 0 !important; /* Ensure no extra left margin */
        list-style-position: outside !important; /* Force bullet points to be outside the text block */
        margin-top: 10px; /* Add some space above the list */
        margin-bottom: 10px; /* Add some space below the list */
    }
    .age-group-card ul li {
        color: black !important; /* Ensure list items are black */
        margin-bottom: 8px !important; /* Add more space between list items */
        line-height: 1.5; /* Improve readability */
    }
    .benefits {
        background: #f8f9fa;
        padding: 2rem;
        border-radius: 10px;
        margin: 2rem 0;
    }
    .benefits h2 {
        color: #0077cc;
        margin-top: 0;
    }
    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    .benefit-item {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .benefit-item i {
        color: #0077cc;
        font-size: 1.5rem;
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
    .schedule-section {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin: 2rem 0;
    }
    .schedule-section h2 {
        color: #0077cc;
        margin-top: 0;
    }
    .schedule-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }
    .schedule-item {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
    }
    .schedule-item h4 {
        color: #0077cc;
        margin: 0 0 0.5rem 0;
    }
</style>
</head>
<body>

<?php include '../src/nav.php'; ?>

<div class="content">
    <div class="hero-section">
        <img src="../img/andebol_formacao.jpg" alt="Andebol Formação" class="hero-image">
        <div class="hero-overlay">
            <h1>Andebol Formação</h1>
        </div>
    </div>

    <p>No Atlético Clube do Cacém, acreditamos que o futuro do andebol começa na formação. O nosso programa de formação é dedicado ao desenvolvimento integral dos jovens atletas, combinando valores desportivos com crescimento pessoal.</p>
    <p>Dispomos de escalões masculinos e femininos em todas as categorias etárias, desde os mais jovens aos juniores, garantindo uma formação completa e adaptada a cada fase de desenvolvimento.</p>

    <div class="age-groups">
        <div class="age-group-card">
            <h3>Bambis, Manitas e Minis (6-12 anos)</h3>
            <p>Iniciação ao andebol através de atividades lúdicas e jogos adaptados. Foco no desenvolvimento motor, coordenação e primeiras noções de equipa.</p>
            <ul>
                <li>Jogos e atividades divertidas</li>
                <li>Desenvolvimento da coordenação</li>
                <li>Primeiros contactos com o andebol</li>
            </ul>
        </div>
        <div class="age-group-card">
            <h3>Infantis (12-14 anos)</h3>
            <p>Fase de consolidação dos fundamentos técnicos e táticos. Introdução a conceitos de jogo mais complexos e participação em competições regionais.</p>
            <ul>
                <li>Aperfeiçoamento técnico</li>
                <li>Treino tático básico</li>
                <li>Participação em torneios</li>
            </ul>
        </div>
        <div class="age-group-card">
            <h3>Iniciados (14-16 anos)</h3>
            <p>Desenvolvimento intensivo das capacidades técnicas e táticas individuais e coletivas. Preparação para um nível competitivo mais exigente e participação em campeonatos.</p>
            <ul>
                <li>Treino técnico e tático avançado</li>
                <li>Preparação física específica</li>
                <li>Competições distritais/regionais</li>
            </ul>
        </div>
        <div class="age-group-card">
            <h3>Juvenis (16-18 anos)</h3>
            <p>Consolidação do alto rendimento. Foco na especialização de posições, estratégias de jogo complexas e preparação para as transições para os escalões superiores.</p>
            <ul>
                <li>Especialização posicional</li>
                <li>Estratégias de jogo avançadas</li>
                <li>Competições nacionais</li>
            </ul>
        </div>
        <div class="age-group-card">
            <h3>Juniores (18-20 anos)</h3>
            <p>A etapa final da formação, com treinos focados na transição para o andebol sénior. Desenvolvimento de liderança e responsabilidade em campo.</p>
            <ul>
                <li>Transição para o sénior</li>
                <li>Foco em liderança e autonomia</li>
                <li>Competições de alto nível</li>
            </ul>
        </div>
        <div class="age-group-card">
            <h3>Séniores (20-34 anos)</h3>
            <p>Embora parte da formação, este escalão visa a manutenção do alto nível competitivo e a integração de novos talentos vindos da formação.</p>
            <ul>
                <li>Manutenção do alto rendimento</li>
                <li>Integração de jovens talentos</li>
                <li>Competições nacionais e internacionais (se aplicável)</li>
            </ul>
        </div>
    </div>

    <div class="benefits">
        <h2>Benefícios do Nosso Programa</h2>
        <div class="benefits-grid">
            <div class="benefit-item">
                <i>🏆</i>
                <div>
                    <h4>Formação de Qualidade</h4>
                    <p>Treinadores qualificados e experientes</p>
                </div>
            </div>
            <div class="benefit-item">
                <i>👥</i>
                <div>
                    <h4>Desenvolvimento Social</h4>
                    <p>Valores de equipa e amizade</p>
                </div>
            </div>
            <div class="benefit-item">
                <i>💪</i>
                <div>
                    <h4>Saúde e Bem-estar</h4>
                    <p>Desenvolvimento físico e mental</p>
                </div>
            </div>
            <div class="benefit-item">
                <i>🎯</i>
                <div>
                    <h4>Progressão</h4>
                    <p>Possibilidade de integrar a equipa sénior</p>
                </div>
            </div>
        </div>
    </div>

    <div class="schedule-section">
        <h2>Horários de Treino</h2>
        <p>Os horários específicos para cada escalão e género são definidos no início da época e comunicados aos atletas. Contate-nos para mais detalhes sobre os horários atuais.</p>
        <div class="schedule-grid">
            <div class="schedule-item">
                <h4>Bambis/Manitas/Minis</h4>
                <p>2x por semana (Ex: Seg/Qua 18h00-19h30)</p>
            </div>
            <div class="schedule-item">
                <h4>Infantis</h4>
                <p>3x por semana (Ex: Ter/Qui 18h00-19h30, Sáb 10h00-11h30)</p>
            </div>
            <div class="schedule-item">
                <h4>Iniciados</h4>
                <p>3x-4x por semana (Ex: Seg/Qua/Sex 19h30-21h00)</p>
            </div>
            <div class="schedule-item">
                <h4>Juvenis/Juniores</h4>
                <p>4x-5x por semana (Ex: Treinos específicos)</p>
            </div>
        </div>
    </div>

    <p>Junte-se à nossa família de andebol! Oferecemos um ambiente seguro, profissional e divertido para o desenvolvimento dos jovens atletas. As inscrições estão abertas durante todo o ano.</p>

    <a href="../src/contactos.php" class="btn">Inscrever Jovem Atleta</a>
</div>

<?php include '../src/footer.php'; ?>

</body>
</html>
