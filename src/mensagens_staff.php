<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Buscar informações do staff
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// Buscar mensagens não lidas para o badge
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE remetente_id = ? AND lida = 0");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $mensagens_nao_lidas = $stmt->get_result()->fetch_assoc();
} else {
    $mensagens_nao_lidas = ['count' => 0];
}

// Buscar todas as mensagens relevantes para o staff (recebidas e enviadas)
$staff_id = $_SESSION['user_id'];
$staff_escalao_id = $staff['escalao_id'];

$stmt = $conn->prepare("
    SELECT m.*, u.nome as atleta_nome, u.escalao_id, e.nome as escalao_nome 
    FROM mensagens m 
    JOIN users u ON m.atleta_id = u.id 
    LEFT JOIN escaloes e ON u.escalao_id = e.id
    WHERE (m.remetente_id = ?) OR (u.escalao_id = ? AND m.destinatario_id = ?) 
    ORDER BY m.data_envio DESC
");

// Debug: Verifique se a preparação da query falha
if (!$stmt) {
    die("Erro na preparação da query unificada: " . $conn->error);
}

$stmt->bind_param("iii", $staff_id, $staff_escalao_id, $staff_id);
$stmt->execute();
$mensagens_unificadas = $stmt->get_result();

// Buscar atletas do escalão
$stmt = $conn->prepare("
    SELECT u.*, e.nome as escalao_nome 
    FROM users u 
    JOIN escaloes e ON u.escalao_id = e.id 
    WHERE u.tipo = 'atleta' 
    AND u.status = 'aprovado'
    AND u.escalao_id = (
        SELECT escalao_id FROM users WHERE id = ?
    )
    ORDER BY u.nome
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$atletas = $stmt->get_result();

// Processar envio de nova mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_mensagem'])) {
    $atleta_id = (int)$_POST['atleta_id'];
    $titulo = $conn->real_escape_string((string)$_POST['titulo']);
    $conteudo = $conn->real_escape_string($_POST['conteudo']);
    
    // Determinar o destinatário. Se o staff envia para o atleta, o destinatário é o atleta. 
    // Se o staff responde, o destinatário é o atleta original da mensagem (já preenchido no reply).
    // A coluna 'destinatario_id' deve ser preenchida aqui.
    $destinatario_id = $atleta_id; // Se staff envia para atleta
    
    $stmt = $conn->prepare("INSERT INTO mensagens (atleta_id, remetente_id, titulo, conteudo, destinatario_id) VALUES (?, ?, ?, ?, ?)");
    
    // Debugging: verificar se a preparação da query falha
    if (!$stmt) {
        die("Erro na preparação da query de insert: " . $conn->error);
    }

    $stmt->bind_param("iissi", $atleta_id, $_SESSION['user_id'], $titulo, $conteudo, $destinatario_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Mensagem enviada com sucesso!'];
        header("Location: mensagens_staff.php");
        exit();
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Erro ao enviar mensagem. Tente novamente.'];
        header("Location: mensagens_staff.php");
        exit();
    }
}

// Marcar mensagem como lida
if (isset($_GET['marcar_lida'])) {
    $mensagem_id = (int)$_GET['marcar_lida'];
    
    // Apenas marcar como lida se o staff for o destinatário
    $stmt = $conn->prepare("UPDATE mensagens SET lida = 1 WHERE id = ? AND destinatario_id = ?");
    if (!$stmt) {
        die("Erro na preparação da query de marcar como lida: " . $conn->error);
    }
    $stmt->bind_param("ii", $mensagem_id, $_SESSION['user_id']);
    $stmt->execute();
    
    header("Location: mensagens_staff.php");
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
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php
                    if (!empty($staff['foto_perfil'])) {
                        echo str_replace('../uploads/', '/uploads/', $staff['foto_perfil']);
                    } else {
                        echo '../img/default-avatar.png';
                    }
                ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($staff['nome']); ?></h3>
                <p><?php echo ucfirst($staff['tipo']); ?></p>
            </div>
            
            <div class="staff-menu">
                <a href="dashboard_staff.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_staff.php" class="menu-item">
                    <i class="fas fa-user-circle"></i> Perfil
                </a>
                <a href="gerir_atletas.php" class="menu-item">
                    <i class="fas fa-users"></i> Gerir Atletas
                </a>
                <a href="gerir_treinos.php" class="menu-item">
                    <i class="fas fa-running"></i> Gerir Treinos
                </a>
                <a href="gerir_jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Gerir Jogos
                </a>
                <a href="mensagens_staff.php" class="menu-item active">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php if ($mensagens_nao_lidas['count'] > 0): ?>
                        <span class="badge"><?php echo $mensagens_nao_lidas['count']; ?></span>
                    <?php endif; ?>
                </a>
                <a href="documentos_staff.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Documentos
                </a>
                <a href="estatisticas.php" class="menu-item">
                    <i class="fas fa-chart-line"></i> Estatísticas
                </a>
                <?php if ($staff['tipo'] == 'dirigente'): ?>
                <a href="financeiro.php" class="menu-item">
                    <i class="fas fa-euro-sign"></i> Financeiro
                </a>
                <?php endif; ?>
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
                    <div class="messages-list" id="messages-list">
                        <?php if ($mensagens_unificadas->num_rows > 0): ?>
                            <?php while ($mensagem = $mensagens_unificadas->fetch_assoc()): ?>
                                <div class="message-item <?php echo ($mensagem['lida'] == 0 && $mensagem['destinatario_id'] == $_SESSION['user_id']) ? 'unread' : ''; ?>" 
                                     data-message-id="<?php echo $mensagem['id']; ?>"
                                     onclick="showMessage(<?php echo htmlspecialchars(json_encode($mensagem)); ?>)">
                                    <div class="message-sender">
                                        <strong>
                                            <?php if ($mensagem['remetente_id'] == $_SESSION['user_id']): ?>
                                                Para: <?php echo htmlspecialchars($mensagem['atleta_nome']); ?>
                                            <?php else: ?>
                                                De: <?php echo htmlspecialchars($mensagem['atleta_nome']); ?>
                                            <?php endif; ?>
                                        </strong>
                                        <span class="badge"><?php echo htmlspecialchars($mensagem['escalao_nome'] ?? 'Sem escalão'); ?></span>
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
                                <form method="post" onsubmit="console.log('Form submitted'); return true;">
                                    <div class="form-group">
                                        <label>Atleta:</label>
                                        <select name="atleta_id" required>
                                            <option value="">Selecione um atleta</option>
                                            <?php while ($atleta = $atletas->fetch_assoc()): ?>
                                                <option value="<?php echo $atleta['id']; ?>">
                                                    <?php echo htmlspecialchars($atleta['nome']); ?> 
                                                    (<?php echo htmlspecialchars($atleta['escalao_nome']); ?>)
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

    <?php if (isset($_SESSION['message'])): ?>
        <div id="alertMessage" class="alert <?php echo $_SESSION['message']['type']; ?>">
            <i class="fas <?php echo ($_SESSION['message']['type'] == 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <?php echo $_SESSION['message']['text']; ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <script>
    // Armazenar as mensagens em variáveis JavaScript
    const mensagensUnificadas = <?php 
        $mensagens_unificadas->data_seek(0); // Resetar o ponteiro para o início
        echo json_encode($mensagens_unificadas->fetch_all(MYSQLI_ASSOC)); 
    ?>;
    
    // Remover mensagensRecebidas e mensagensEnviadas
    // const mensagensRecebidas = []; 
    // const mensagensEnviadas = [];

    let currentTab = 'all'; // Não mais usado para tabs, mas pode ser útil para futuros filtros

    // updateMessagesList is no longer needed in this structure, as messages are directly rendered by PHP.
    // Keep it if there's a dynamic filter/sort functionality intended for the future.
    /*
    function updateMessagesList() {
        const messagesList = document.getElementById('messages-list');
        const messages = mensagensUnificadas;
        
        if (!messages || messages.length === 0) {
            messagesList.innerHTML = '<div class="no-message">Nenhuma mensagem encontrada</div>';
            return;
        }

        messagesList.innerHTML = messages.map(message => {
            const isUnread = message.lida === '0' || message.lida === 0;
            const isSentByMe = message.remetente_id == <?php echo $_SESSION['user_id']; ?>;

            return `
                <div class="message-item ${isUnread ? 'unread' : ''}" 
                     data-message-id="${message.id}"
                     onclick="showMessage(${message.id})">
                    <div class="message-sender">
                        <strong>${isSentByMe ? 'Para:' : 'De:'} ${escapeHtml(message.atleta_nome)}</strong>
                        <span class="badge">${escapeHtml(message.escalao_nome || 'Sem escalão')}</span>
                    </div>
                    <div class="message-title">${escapeHtml(message.titulo)}</div>
                    <div class="message-preview">${escapeHtml(message.conteudo.substring(0, 100))}...</div>
                    <div class="message-date">
                        ${new Date(message.data_envio).toLocaleString()}
                    </div>
                </div>
            `;
        }).join('');
    }
    */

    function showMessage(messageData) {
        const messageView = document.getElementById('message-view');
        messageView.style.display = 'block';
        document.getElementById('new-message-form').style.display = 'none';

        const isSentByMe = messageData.remetente_id == <?php echo $_SESSION['user_id']; ?>;

        messageView.innerHTML = `
            <div class="message-view">
                <div class="message-header">
                    <h2>${escapeHtml(messageData.titulo)}</h2>
                    <div class="message-meta">
                        <strong>${isSentByMe ? 'Para:' : 'De:'}</strong> ${escapeHtml(messageData.atleta_nome)}
                        <br>
                        <strong>Escalão:</strong> ${escapeHtml(messageData.escalao_nome || 'Não definido')}
                        <br>
                        <strong>Data:</strong> ${new Date(messageData.data_envio).toLocaleString()}
                    </div>
                </div>
                <div class="message-body">
                    ${escapeHtml(messageData.conteudo)}
                </div>
                <div class="message-actions">
                    ${!isSentByMe ? `
                    <button class="btn-send" onclick="replyToMessage(${messageData.id})">
                        <i class="fas fa-reply"></i> Responder
                    </button>
                    ` : ''}
                </div>
            </div>
        `;

        // Marcar como lida se for uma mensagem recebida e ainda não lida
        if (messageData.lida === '0' || messageData.lida === 0) {
            if (messageData.destinatario_id == <?php echo $_SESSION['user_id']; ?>) {
                // Apenas marca como lida se o utilizador atual for o destinatário
                fetch(`mensagens_staff.php?marcar_lida=${messageData.id}`)
                    .then(response => {
                        if (response.ok) {
                            const messageItem = document.querySelector(`.message-item[data-message-id="${messageData.id}"]`);
                            if (messageItem) {
                                messageItem.classList.remove('unread');
                            }
                            // Opcional: Atualizar o badge de mensagens não lidas
                            // window.location.reload(); 
                        }
                    })
                    .catch(error => console.error('Erro ao marcar mensagem como lida:', error));
            }
        }

        // Atualizar estilo da mensagem na lista
        document.querySelectorAll('.message-item').forEach(item => {
            item.classList.remove('active');
            if (parseInt(item.getAttribute('data-message-id')) === parseInt(messageData.id)) {
                item.classList.add('active');
            }
        });
    }

    function showNewMessageForm() {
        document.getElementById('message-view').style.display = 'none';
        document.getElementById('new-message-form').style.display = 'block';
        // Desabilitar seleção de atleta ao abrir novo formulário sem ser de resposta
        const atletaSelect = document.querySelector('select[name="atleta_id"]');
        if (atletaSelect) {
            atletaSelect.disabled = false;
            atletaSelect.value = ""; // Limpar seleção
        }
        document.querySelector('input[name="titulo"]').value = "";
        document.querySelector('textarea[name="conteudo"]').value = "";
    }

    function replyToMessage(messageId) {
        // Encontrar a mensagem original na lista unificada
        const originalMessage = mensagensUnificadas.find(m => parseInt(m.id) === parseInt(messageId));
        
        if (!originalMessage) {
            console.error('Mensagem original não encontrada:', messageId);
            return;
        }

        showNewMessageForm();
        
        // Preencher o destinatário com o remetente da mensagem original (o atleta)
        const atletaSelect = document.querySelector('select[name="atleta_id"]');
        if (atletaSelect) {
            atletaSelect.value = originalMessage.remetente_id; // Remetente da mensagem original é o atleta
            atletaSelect.disabled = true; // Desabilita para que não mude o destinatário da resposta
        }

        // Preencher o assunto
        const tituloInput = document.querySelector('input[name="titulo"]');
        if (tituloInput) {
            tituloInput.value = `Re: ${originalMessage.titulo}`;
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

    // Função para esconder o alerta após X segundos
    document.addEventListener('DOMContentLoaded', function() {
        const alertMessage = document.getElementById('alertMessage');
        if (alertMessage) {
            setTimeout(() => {
                alertMessage.style.display = 'none';
            }, 5000); // Esconde após 5 segundos
        }
    });
    </script>
</body>
</html>