<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="footer.css">
<header>
    <a href="../src/homepage.php">
        <img src="../img/logoclub.png" alt="Logo do Clube">
    </a>

    <nav>
        <a href="../pages/andebol.php">🤾 Andebol</a>
        <a href="../pages/andebol-formacao.php">👶 Andebol Formação</a>
        <a href="../src/sobre-nos.php">📍 Sobre Nós</a>
        <a href="../src/contactos.php">✉️ Contactos</a>
    </nav>

    <div class="nav-links">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (isset($_SESSION['status']) && $_SESSION['status'] === 'aprovado'): ?>
                <?php
                $dashboard_url = '';
                switch ($_SESSION['tipo']) {
                    case 'admin':
                        $dashboard_url = 'admin_dashboard.php';
                        break;
                    case 'treinador':
                    case 'dirigente':
                        $dashboard_url = 'dashboard_staff.php';
                        break;
                    case 'atleta':
                        $dashboard_url = 'dashboard_atleta.php';
                        break;
                }
                if ($dashboard_url): ?>
                    <a href="<?= $dashboard_url ?>" class="client-area">Dashboard</a>
                <?php endif; ?>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="/src/login.php" class="login-btn">Entrar</a>
        <?php endif; ?>
    </div>
</header>
