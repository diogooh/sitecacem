<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o utilizador está autenticado e é staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'treinador' && $_SESSION['tipo'] !== 'dirigente')) {
    header('Location: login.php');
    exit();
}

require_once 'db.php';

// Obter informações do utilizador (staff)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Obter jogos do utilizador (staff)
$stmt = $conn->prepare("
    SELECT j.*, e.nome as escalao_nome, m.nome as modalidade_nome 
    FROM jogos j 
    JOIN escaloes e ON j.escalao_id = e.id 
    JOIN modalidades m ON e.modalidade_id = m.id 
    WHERE j.user_id = ? 
    ORDER BY j.data_jogo DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$jogos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Lógica para atualizar o status do jogo com base na data
$current_time = new DateTime();
foreach ($jogos as &$jogo) { // Usar & para modificar o array diretamente
    $game_datetime = new DateTime($jogo['data_jogo']);

    // Se o jogo está agendado e a data/hora já passou, marcar como concluído
    if ($jogo['status'] === 'agendado' && $game_datetime < $current_time) {
        $update_stmt = $conn->prepare("UPDATE jogos SET status = 'concluido' WHERE id = ? AND user_id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("ii", $jogo['id'], $jogo['user_id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Atualizar o status no array para exibição imediata
            $jogo['status'] = 'concluido';
        } else {
            error_log("Erro ao preparar UPDATE de status do jogo: " . $conn->error);
        }
    }
}
unset($jogo); // Quebrar a referência com o último elemento do array

// Obter escalões disponíveis para o utilizador (staff)
$stmt = $conn->prepare("
    SELECT e.*, m.nome as modalidade_nome 
    FROM escaloes e 
    JOIN modalidades m ON e.modalidade_id = m.id 
    WHERE e.staff_id = ? 
");

if (!$stmt) {
    die("Erro ao preparar a consulta para escalões: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$escaloes = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Jogos - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a237e;
            --primary-light: #1976d2;
            --secondary-color: #f4f6fb;
            --text-dark: #333;
            --text-light: #666;
            --white: #fff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --border-radius: 12px;
            --box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: var(--secondary-color);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            color: var(--white);
            padding: 20px;
            min-height: 100vh;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .dashboard-content {
            margin-left: 280px;
            padding: 40px;
            width: calc(100% - 280px);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 2em;
            color: var(--primary-color);
            margin: 0;
        }

        .add-game-btn {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            border-radius: var(--border-radius);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1em;
            font-weight: 500;
            transition: var(--transition);
        }

        .add-game-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .games-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .game-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }

        .game-card:hover {
            transform: translateY(-5px);
        }

        .game-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .game-title {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }

        .game-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 500;
        }

        .status-agendado {
            background: var(--warning);
            color: #856404;
        }

        .status-concluido {
            background: var(--success);
            color: var(--white);
        }

        .status-cancelado {
            background: var(--danger);
            color: var(--white);
        }

        .game-info {
            margin-bottom: 15px;
        }

        .game-info p {
            margin: 5px 0;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .game-info i {
            color: var(--primary-color);
            width: 20px;
        }

        .game-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .game-actions button {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .edit-btn {
            background: var(--primary-light);
            color: var(--white);
        }

        .delete-btn {
            background: var(--danger);
            color: var(--white);
        }

        .view-btn {
            background: var(--secondary-color);
            color: var(--primary-color);
        }

        .game-actions button:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: var(--white);
            width: 90%;
            max-width: 600px;
            margin: 5vh auto;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-height: 90vh; /* Allow scrolling for long content */
            overflow-y: auto; /* Enable vertical scrolling */
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
            color: var(--primary-color);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 1em;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .form-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
        }

        .save-btn {
            background: var(--success);
            color: var(--white);
        }

        .cancel-btn {
            background: var(--danger);
            color: var(--white);
        }

        @media (max-width: 768px) {
            .dashboard-sidebar.staff-sidebar {
                width: 70px;
                padding: 15px 10px;
            }

            .staff-header h3,
            .staff-header p,
            .menu-item span {
                display: none;
            }

            .dashboard-content {
                margin-left: 70px;
                width: calc(100% - 70px);
                padding: 20px;
            }

            .games-grid {
                grid-template-columns: 1fr;
            }

            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .add-game-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php
                    if (!empty($user['foto_perfil'])) {
                        echo str_replace('../uploads/', '/uploads/', $user['foto_perfil']);
                    } else {
                        echo '../img/default-avatar.png';
                    }
                ?>" alt="Perfil">
                <h3><?php echo htmlspecialchars($user['nome']); ?></h3>
                <p><?php echo ucfirst($user['tipo']); ?></p>
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
                <a href="gerir_jogos.php" class="menu-item active">
                    <i class="fas fa-futbol"></i> Gerir Jogos
                </a>
                <a href="mensagens_staff.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Mensagens
                </a>
                <a href="documentos_staff.php" class="menu-item">
                    <i class="fas fa-file-alt"></i> Documentos
                </a>
                <a href="estatisticas.php" class="menu-item">
                    <i class="fas fa-chart-line"></i> Estatísticas
                </a>
                <?php if ($user['tipo'] == 'dirigente'): ?>
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
            <div class="page-header">
                <h1>Gestão de Jogos</h1>
                <button class="add-game-btn" onclick="openModal()">
                    <i class="fas fa-plus"></i> Novo Jogo
                </button>
            </div>

            <div class="games-grid">
                <?php foreach ($jogos as $jogo): ?>
                    <div class="game-card">
                        <div class="game-header">
                            <h3 class="game-title"><?php echo htmlspecialchars($jogo['titulo']); ?></h3>
                            <span class="game-status status-<?php echo strtolower($jogo['status']); ?>">
                                <?php echo ucfirst($jogo['status']); ?>
                            </span>
                        </div>
                        <div class="game-info">
                            <p><i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($jogo['data_jogo'])); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($jogo['local']); ?></p>
                            <p><i class="fas fa-users"></i> <?php echo htmlspecialchars($jogo['escalao_nome']); ?></p>
                            <p><i class="fas fa-futbol"></i> <?php echo htmlspecialchars($jogo['modalidade_nome']); ?></p>
                        </div>
                        <div class="game-actions">
                            <button class="view-btn" onclick="viewGame(<?php echo $jogo['id']; ?>)">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="edit-btn" onclick="editGame(<?php echo $jogo['id']; ?>)">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="delete-btn" onclick="deleteGame(<?php echo $jogo['id']; ?>)">
                                <i class="fas fa-trash"></i> Apagar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal para Adicionar/Editar Jogo -->
    <div id="gameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Jogo</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="gameForm" action="processar_jogo.php" method="POST">
                <input type="hidden" name="jogo_id" id="jogo_id">
                
                <div class="form-group">
                    <label for="titulo">Título do Jogo</label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>

                <div class="form-group">
                    <label for="escalao_id">Escalão</label>
                    <select id="escalao_id" name="escalao_id" required>
                        <option value="">Selecione um escalão</option>
                        <?php foreach ($escaloes as $escalao): ?>
                            <option value="<?php echo $escalao['id']; ?>">
                                <?php echo htmlspecialchars($escalao['nome'] . ' - ' . $escalao['modalidade_nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="data_jogo">Data e Hora</label>
                    <input type="datetime-local" id="data_jogo" name="data_jogo" required>
                </div>

                <div class="form-group">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local" required>
                </div>

                <div class="form-group">
                    <label for="adversario">Adversário</label>
                    <input type="text" id="adversario" name="adversario" required>
                </div>

                <div class="form-group">
                    <label for="pontuacao_acc">Pontuação ACC</label>
                    <input type="number" id="pontuacao_acc" name="pontuacao_acc" min="0">
                </div>

                <div class="form-group">
                    <label for="pontuacao_adversario">Pontuação Adversário</label>
                    <input type="number" id="pontuacao_adversario" name="pontuacao_adversario" min="0">
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="save-btn">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTitle').textContent = 'Novo Jogo';
            document.getElementById('gameForm').reset();
            document.getElementById('jogo_id').value = '';
            document.getElementById('gameModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('gameModal').style.display = 'none';
        }

        function editGame(id) {
            // Fazer uma requisição AJAX para obter os dados do jogo
            fetch(`get_jogo.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('modalTitle').textContent = 'Editar Jogo';
                        document.getElementById('jogo_id').value = data.id;
                        document.getElementById('titulo').value = data.titulo;
                        document.getElementById('escalao_id').value = data.escalao_id;
                        document.getElementById('data_jogo').value = data.data_jogo.substring(0, 16); // Formato YYYY-MM-DDTHH:MM
                        document.getElementById('local').value = data.local;
                        document.getElementById('adversario').value = data.adversario;
                        document.getElementById('pontuacao_acc').value = data.pontuacao_acc;
                        document.getElementById('pontuacao_adversario').value = data.pontuacao_adversario;
                        document.getElementById('descricao').value = data.descricao;
                        document.getElementById('gameModal').style.display = 'block';
                    } else {
                        alert('Jogo não encontrado.');
                    }
                })
                .catch(error => console.error('Erro ao buscar dados do jogo:', error));
        }

        function viewGame(id) {
            // Implementar lógica para visualizar detalhes do jogo
            window.location.href = `ver_jogo.php?id=${id}`;
        }

        function deleteGame(id) {
            if (confirm('Tem certeza que deseja apagar este jogo?')) {
                window.location.href = `processar_jogo.php?action=delete&id=${id}`;
            }
        }

        // Fechar modal quando clicar fora dele
        window.onclick = function(event) {
            if (event.target == document.getElementById('gameModal')) {
                closeModal();
            }
        }
    </script>
</body>
</html> 