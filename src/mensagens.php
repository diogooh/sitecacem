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
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="mensagens.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <a href="dashboard_atleta.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_atleta.php" class="menu-item">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="treinos.php" class="menu-item">
                    <i class="fas fa-running"></i> Treinos
                </a>
                <a href="jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Jogos
                </a>
                <a href="mensagens.php" class="menu-item active">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php if ($mensagens_nao_lidas['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens_nao_lidas['count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="pagamentos.php" class="menu-item">
                    <i class="fas fa-euro-sign"></i> Pagamentos
                </a>
            </div>

            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <div class="messages-container">
                <div class="messages-header">
                    <h1>Mensagens</h1>
                    <button class="btn-primary" onclick="showNewMessageForm()">
                        <i class="fas fa-plus"></i> Nova Mensagem
                    </button>
                </div>

                <div class="messages-grid">
                    <!-- Lista de Mensagens -->
                    <div class="messages-list">
                        <?php if ($mensagens->num_rows > 0): ?>
                            <?php while ($mensagem = $mensagens->fetch_assoc()): ?>
                                <div class="message-item <?php echo !$mensagem['lida'] ? 'unread' : ''; ?>" 
                                     onclick="showMessage(<?php echo htmlspecialchars(json_encode($mensagem)); ?>)">
                                    <div class="message-sender">
                                        <strong><?php echo htmlspecialchars($mensagem['remetente_nome']); ?></strong>
                                        <span class="badge"><?php echo ucfirst($mensagem['remetente_tipo']); ?></span>
                                    </div>
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

                    <!-- Conteúdo da Mensagem -->
                    <div class="message-content">
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
        document.querySelectorAll('.message-item').forEach(item => {
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