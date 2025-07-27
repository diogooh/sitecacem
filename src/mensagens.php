<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'atleta') {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar informações do atleta
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$atleta = $stmt->get_result()->fetch_assoc();

// Buscar mensagens não lidas para o badge
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE atleta_id = ? AND lida = 0");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $mensagens_nao_lidas = $stmt->get_result()->fetch_assoc();
} else {
    $mensagens_nao_lidas = ['count' => 0];
}

// Buscar mensagens do atleta
$stmt = $conn->prepare("
    SELECT m.*, u.nome as remetente_nome, u.tipo as remetente_tipo, u.escalao_id, e.nome as escalao_nome
    FROM mensagens m 
    JOIN users u ON m.remetente_id = u.id 
    LEFT JOIN escaloes e ON u.escalao_id = e.id
    WHERE m.atleta_id = ? 
    ORDER BY m.data_envio DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$mensagens = $stmt->get_result();

// Buscar treinadores e dirigentes disponíveis
$stmt = $conn->prepare("
    SELECT u.*, e.nome as escalao_nome 
    FROM users u 
    LEFT JOIN escaloes e ON u.escalao_id = e.id 
    WHERE u.tipo IN ('treinador', 'dirigente') 
    AND u.status = 'aprovado'
    ORDER BY u.tipo, u.nome
");
$stmt->execute();
$staff = $stmt->get_result();

// Processar envio de nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensagem'])) {
    $remetente_id = (int)$_POST['remetente_id'];
    $titulo = $conn->real_escape_string((string)$_POST['titulo']);
    error_log("DEBUG (Atleta): Raw POST title: " . (isset($_POST['titulo']) ? $_POST['titulo'] : 'NOT SET'));
    error_log("DEBUG (Atleta): Processed title before bind_param: \'" . $titulo . "\' (Type: " . gettype($titulo) . ")");
    $conteudo = $conn->real_escape_string($_POST['conteudo']);
    
    // O remetente é sempre o atleta logado, o destinatário é o staff selecionado
    $remetente_id_atleta = $_SESSION['user_id'];
    $destinatario_id_staff = $remetente_id; // $remetente_id é o ID do staff/treinador selecionado

    $stmt = $conn->prepare("INSERT INTO mensagens (atleta_id, remetente_id, titulo, conteudo, destinatario_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissi", $remetente_id_atleta, $remetente_id_atleta, $titulo, $conteudo, $destinatario_id_staff);
    
    if ($stmt->execute()) {
        header("Location: mensagens.php?success=1");
        exit();
    } else {
        $erro = "Erro ao enviar mensagem. Tente novamente.";
    }
}

// Marcar mensagem como lida
if (isset($_GET['marcar_lida'])) {
    $mensagem_id = (int)$_GET['marcar_lida'];
    $stmt = $conn->prepare("UPDATE mensagens SET lida = 1 WHERE id = ? AND atleta_id = ?");
    $stmt->bind_param("ii", $mensagem_id, $_SESSION['user_id']);
    $stmt->execute();
    header("Location: mensagens.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - ACC</title>
    <link rel="stylesheet" href="/sitecacem/src/dashboard_atleta.css">
    <link rel="stylesheet" href="/sitecacem/src/mensagens.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
    .dashboard-layout {
        display: flex !important;
        min-height: 100vh;
        background: #f4f6fb;
    }
    .dashboard-sidebar {
        width: 270px !important;
        background: linear-gradient(135deg, #004080 0%, #002b57 100%) !important;
        color: #fff !important;
        min-height: 100vh !important;
        padding: 0 !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        box-shadow: 0 4px 24px rgba(26,35,126,0.10);
    }
    .sidebar-header {
        padding: 40px 20px 20px 20px !important;
        text-align: center !important;
    }
    .sidebar-header img {
        width: 110px !important;
        height: 110px !important;
        border-radius: 50% !important;
        border: 4px solid #fff !important;
        margin-bottom: 10px !important;
        object-fit: cover !important;
    }
    .sidebar-header h3 {
        color: #fff !important;
        font-size: 1.3em !important;
        margin: 0 0 5px 0 !important;
    }
    .sidebar-header p {
        color: #b3c6e0 !important;
        font-size: 1em !important;
        margin: 0 !important;
    }
    .sidebar-menu {
        display: flex !important;
        flex-direction: column !important;
        gap: 5px !important;
        margin-top: 30px !important;
    }
    .sidebar-menu .menu-item {
        color: #fff !important;
        padding: 14px 30px !important;
        text-decoration: none !important;
        font-size: 1.08em !important;
        border: none !important;
        background: none !important;
        border-radius: 8px !important;
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        transition: background 0.2s !important;
    }
    .sidebar-menu .menu-item.active, .sidebar-menu .menu-item:hover {
        background: rgba(255,255,255,0.12) !important;
    }
    .logout-section {
        margin-top: auto !important;
        padding: 30px 20px !important;
    }
    .logout-btn {
        width: 100% !important;
        background: #dc3545 !important;
        color: #fff !important;
        border: none !important;
        border-radius: 8px !important;
        padding: 12px 0 !important;
        font-size: 1.1em !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        transition: background 0.2s !important;
    }
    .logout-btn:hover {
        background: #b52a37 !important;
    }
    @media (max-width: 900px) {
        .dashboard-layout {
            flex-direction: column !important;
        }
        .dashboard-sidebar {
            width: 100% !important;
            min-height: auto !important;
            flex-direction: row !important;
            overflow-x: auto !important;
        }
        .sidebar-menu {
            flex-direction: row !important;
            gap: 0 !important;
            margin-top: 0 !important;
        }
        .sidebar-menu .menu-item {
            padding: 10px 12px !important;
            font-size: 1em !important;
        }
    }
    .dashboard-content {
        flex: 1 1 0%;
        padding: 40px 30px;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }
    .mensagens-wrapper {
        display: flex;
        gap: 32px;
        margin-top: 10px;
        width: 100%;
        min-height: 600px;
    }
    .mensagens-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(26,35,126,0.10);
        flex: 1 1 350px;
        display: flex;
        flex-direction: column;
        min-width: 320px;
        max-width: 420px;
        padding: 0;
        overflow: hidden;
    }
    .mensagens-header {
        background: linear-gradient(135deg, #283593 0%, #1976d2 100%);
        color: #fff;
        padding: 24px 24px 18px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 16px 16px 0 0;
    }
    .mensagens-header h2 {
        margin: 0;
        font-size: 1.6em;
        font-weight: 700;
    }
    .nova-mensagem-btn {
        background: #1976d2;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 22px;
        font-size: 1em;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(25,118,210,0.10);
        cursor: pointer;
        transition: background 0.18s, transform 0.15s;
    }
    .nova-mensagem-btn:hover {
        background: #004080;
        transform: translateY(-2px) scale(1.04);
    }
    .lista-mensagens {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 18px 0 18px 0;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .item-mensagem {
        padding: 16px 24px;
        border-radius: 10px;
        background: #f8f9fa;
        box-shadow: 0 1px 4px rgba(26,35,126,0.04);
        cursor: pointer;
        transition: background 0.15s, box-shadow 0.15s;
        border: 1px solid #f0f0f0;
        margin-bottom: 0;
    }
    .item-mensagem:hover, .item-mensagem.active {
        background: #e3eafc;
        box-shadow: 0 2px 8px rgba(25,118,210,0.08);
    }
    .item-mensagem strong {
        color: #283593;
        font-size: 1.08em;
    }
    .item-mensagem .badge {
        background: #e3eafc;
        color: #1976d2;
        border-radius: 8px;
        padding: 2px 10px;
        font-size: 0.9em;
        margin-left: 8px;
    }
    .painel-leitura {
        flex: 2 1 0%;
        background: #f8f9fa;
        border-radius: 16px;
        box-shadow: 0 2px 12px rgba(26,35,126,0.07);
        padding: 40px 32px;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15em;
        color: #555;
    }
    @media (max-width: 1100px) {
        .mensagens-wrapper {
            flex-direction: column;
            gap: 18px;
        }
        .mensagens-card, .painel-leitura {
            max-width: 100%;
            min-width: 0;
        }
        .painel-leitura {
            padding: 24px 10px;
        }
    }
</style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
            <div class="sidebar-header">
                <img src="<?php echo !empty($atleta['foto_perfil']) ? '../' . $atleta['foto_perfil'] : 'img/default-avatar.png'; ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($atleta['nome']); ?></h3>
                <p>CIPA: <?php echo htmlspecialchars($atleta['cip'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'dashboard_atleta.php' ? ' active' : '' ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_atleta.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'perfil_atleta.php' ? ' active' : '' ?>">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="treinos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'treinos.php' ? ' active' : '' ?>">
                    <i class="fas fa-dumbbell"></i> Treinos
                </a>
                <a href="jogos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'jogos.php' ? ' active' : '' ?>">
                    <i class="fas fa-futbol"></i> Jogos
                </a>
                <a href="mensagens.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'mensagens.php' ? ' active' : '' ?>">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php if ($mensagens_nao_lidas['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens_nao_lidas['count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="pagamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'pagamentos.php' ? ' active' : '' ?>">
                    <i class="fas fa-euro-sign"></i> Pagamentos
                </a>
                <a href="atleta_equipamentos.php" class="menu-item<?= basename($_SERVER['PHP_SELF']) == 'atleta_equipamentos.php' ? ' active' : '' ?>">
                    <i class="fas fa-tshirt"></i> Equipamentos
                </a>
            </div>

            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <div class="mensagens-wrapper">
                <div class="mensagens-card">
                    <div class="mensagens-header">
                        <h2>Mensagens</h2>
                        <button class="nova-mensagem-btn" onclick="showNewMessageForm()">
                            <i class="fas fa-plus"></i> Nova Mensagem
                        </button>
                    </div>
                    <div class="lista-mensagens">
                        <?php if ($mensagens->num_rows > 0): ?>
                            <?php while ($mensagem = $mensagens->fetch_assoc()): ?>
                                <div class="item-mensagem <?php echo !$mensagem['lida'] ? 'unread' : ''; ?>" 
                                     onclick="showMessage(<?php echo htmlspecialchars(json_encode($mensagem)); ?>)">
                                    <strong><?php echo htmlspecialchars($mensagem['remetente_nome']); ?></strong>
                                    <span class="badge"><?php echo ucfirst($mensagem['remetente_tipo']); ?></span>
                                    <div class="message-title"><?php echo htmlspecialchars($mensagem['titulo']); ?></div>
                                    <div class="message-preview"><?php echo htmlspecialchars(substr($mensagem['conteudo'], 0, 100)) . '...'; ?></div>
                                    <div class="message-date">
                                        <?php echo date('d/m/Y H:i', strtotime($mensagem['data_envio'])); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-message">Nenhuma mensagem encontrada</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="painel-leitura">
                    <div id="message-view">
                        <div class="no-message">Selecione uma mensagem para visualizar</div>
                    </div>

                    <!-- Formulário de Nova Mensagem -->
                    <div id="new-message-form" style="display: none;">
                        <div class="new-message-form">
                            <h2>Nova Mensagem</h2>
                            <form method="post">
                                <div class="form-group">
                                    <label>Destinatário:</label>
                                    <select name="remetente_id" required>
                                        <option value="">Selecione um destinatário</option>
                                        <?php while ($staff_member = $staff->fetch_assoc()): ?>
                                            <option value="<?php echo $staff_member['id']; ?>">
                                                <?php echo htmlspecialchars($staff_member['nome']); ?> 
                                                (<?php echo ucfirst($staff_member['tipo']); ?>)
                                                <?php if ($staff_member['escalao_nome']): ?>
                                                    - <?php echo htmlspecialchars($staff_member['escalao_nome']); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Assunto:</label>
                                    <input type="text" name="titulo" required>
                                </div>
                                <div class="form-group">
                                    <label>Mensagem:</label>
                                    <textarea name="conteudo" required></textarea>
                                </div>
                                <button type="submit" name="enviar_mensagem" class="btn-send">
                                    <i class="fas fa-paper-plane"></i> Enviar Mensagem
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showMessage(message) {
        const messageView = document.getElementById('message-view');
        messageView.innerHTML = `
            <div class="message-view">
                <div class="message-header">
                    <h2>${escapeHtml(message.titulo)}</h2>
                    <div class="message-meta">
                        <strong>${message.remetente_id === <?php echo $_SESSION['user_id']; ?> ? 'Para:' : 'De:'}</strong> ${escapeHtml(message.remetente_nome)} (${escapeHtml(message.remetente_tipo)})
                        ${message.escalao_nome ? `<br><strong>Escalão:</strong> ${escapeHtml(message.escalao_nome)}` : ''}
                        <br>
                        <strong>Data:</strong> ${new Date(message.data_envio).toLocaleString()}
                    </div>
                </div>
                <div class="message-body">
                    ${escapeHtml(message.conteudo)}
                </div>
                <div class="message-actions">
                    ${message.remetente_id !== <?php echo $_SESSION['user_id']; ?> ? `
                    <button class="btn-send" onclick="replyToMessage(${message.id})">
                        <i class="fas fa-reply"></i> Responder
                    </button>
                    ` : ''}
                </div>
            </div>
        `;

        // Marcar como lida
        if (!message.lida) {
            window.location.href = `mensagens.php?marcar_lida=${message.id}`;
        }

        // Atualizar estilo da mensagem na lista
        document.querySelectorAll('.item-mensagem').forEach(item => {
            item.classList.remove('active');
            if (item.querySelector('.message-title').textContent === message.titulo) {
                item.classList.add('active');
                item.classList.remove('unread');
            }
        });
    }

    function showNewMessageForm() {
        document.getElementById('message-view').style.display = 'none';
        document.getElementById('new-message-form').style.display = 'block';
    }

    function replyToMessage(messageId) {
        const message = mensagens.find(m => m.id === messageId);
        if (!message) return;

        showNewMessageForm();
        
        // Preencher o destinatário
        const remetenteSelect = document.querySelector('select[name="remetente_id"]');
        if (remetenteSelect) {
            remetenteSelect.value = message.remetente_id;
        }

        // Preencher o assunto
        const tituloInput = document.querySelector('input[name="titulo"]');
        if (tituloInput) {
            tituloInput.value = `Re: ${message.titulo}`;
        }

        // Focar no campo de mensagem
        const conteudoTextarea = document.querySelector('textarea[name="conteudo"]');
        if (conteudoTextarea) {
            conteudoTextarea.focus();
        }
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Armazenar mensagens em variável global para uso nas funções
    const mensagens = <?php 
        $mensagens->data_seek(0);
        echo json_encode($mensagens->fetch_all(MYSQLI_ASSOC)); 
    ?>;
    </script>
</body>
</html> 