<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Verificar conexão com o banco de dados
if ($conn->connect_error) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Verificar se a tabela users existe
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    die("A tabela 'users' não existe. Por favor, verifique a estrutura do banco de dados.");
}

// Verificar se a tabela modalidades existe
$table_check = $conn->query("SHOW TABLES LIKE 'modalidades'");
if ($table_check->num_rows == 0) {
    // Criar a tabela modalidades
    $create_modalidades = "CREATE TABLE IF NOT EXISTS modalidades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome VARCHAR(255) NOT NULL,
        ativo TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$conn->query($create_modalidades)) {
        die("Erro ao criar tabela modalidades: " . $conn->error);
    }
    
    // Inserir algumas modalidades padrão
    $modalidades_padrao = [
        "Andebol",
        "Futebol"
    ];
    
    // Primeiro, vamos verificar se a tabela foi criada corretamente
    $check_table = $conn->query("DESCRIBE modalidades");
    if (!$check_table) {
        die("Erro ao verificar estrutura da tabela modalidades: " . $conn->error);
    }
    
    // Agora vamos inserir as modalidades
    foreach ($modalidades_padrao as $modalidade) {
        $query = "INSERT INTO modalidades (nome) VALUES ('" . $conn->real_escape_string($modalidade) . "')";
        if (!$conn->query($query)) {
            die("Erro ao inserir modalidade '$modalidade': " . $conn->error);
        }
    }
}

// Verificar se a tabela treinos existe
$table_check = $conn->query("SHOW TABLES LIKE 'treinos'");
if ($table_check->num_rows == 0) {
    // Criar a tabela treinos
    $create_treinos = "CREATE TABLE IF NOT EXISTS treinos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        modalidade_id INT NOT NULL,
        data DATE NOT NULL,
        hora_inicio TIME NOT NULL,
        hora_fim TIME NOT NULL,
        local VARCHAR(255) NOT NULL,
        treinador VARCHAR(255) NOT NULL,
        descricao TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (modalidade_id) REFERENCES modalidades(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if (!$conn->query($create_treinos)) {
        die("Erro ao criar tabela treinos: " . $conn->error);
    }
}

// Verificar se a coluna 'anexo' existe na tabela treinos
$col_check = $conn->query("SHOW COLUMNS FROM treinos LIKE 'anexo'");
if ($col_check->num_rows == 0) {
    $conn->query("ALTER TABLE treinos ADD COLUMN anexo VARCHAR(255) NULL AFTER descricao");
}

// Buscar informações do staff
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$stmt) {
    die("Erro na preparação da query de usuário: " . $conn->error);
}
$stmt->bind_param("i", $_SESSION['user_id']);
if (!$stmt->execute()) {
    die("Erro ao executar query de usuário: " . $stmt->error);
}
$staff = $stmt->get_result()->fetch_assoc();

if (!$staff) {
    die("Usuário não encontrado.");
}

// Buscar todos os treinos
$query_treinos = "SELECT t.*, m.nome as modalidade_nome 
                 FROM treinos t 
                 LEFT JOIN modalidades m ON t.modalidade_id = m.id 
                 ORDER BY t.data DESC";
                 
$stmt = $conn->prepare($query_treinos);
if (!$stmt) {
    die("Erro na preparação da query de treinos: " . $conn->error . "<br>Query: " . $query_treinos);
}
if (!$stmt->execute()) {
    die("Erro ao executar query de treinos: " . $stmt->error);
}
$treinos = $stmt->get_result();

// Buscar modalidades para o formulário - usando query direta primeiro para debug
$modalidades_query = "SELECT * FROM modalidades WHERE ativo = 1";
$modalidades_result = $conn->query($modalidades_query);
if (!$modalidades_result) {
    die("Erro na query de modalidades: " . $conn->error . "<br>Query: " . $modalidades_query);
}
$modalidades = $modalidades_result;

// --- FILTRO DE BUSCA ---
$filtro_modalidade = isset($_GET['modalidade_id']) ? $_GET['modalidade_id'] : '';
$filtro_data = isset($_GET['data']) ? $_GET['data'] : '';
$filtro_treinador = isset($_GET['treinador']) ? trim($_GET['treinador']) : '';

// Montar a query de treinos com filtros
$where = [];
$params = [];
$types = '';

// Filtrar treinos pelo treinador logado se for um treinador
if ($staff['tipo'] === 'treinador') {
    $where[] = 't.treinador = ?';
    $params[] = $staff['nome'];
    $types .= 's';
}

if ($filtro_modalidade !== '') {
    $where[] = 't.modalidade_id = ?';
    $params[] = $filtro_modalidade;
    $types .= 'i';
}
if ($filtro_data !== '') {
    $where[] = 't.data = ?';
    $params[] = $filtro_data;
    $types .= 's';
}

// Se o filtro de treinador via GET for usado (apenas para admin/dirigente poderem filtrar)
if ($filtro_treinador !== '' && $staff['tipo'] !== 'treinador') {
    $where[] = 't.treinador LIKE ?';
    $params[] = '%' . $filtro_treinador . '%';
    $types .= 's';
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$query_treinos = "SELECT t.*, m.nome as modalidade_nome 
                 FROM treinos t 
                 LEFT JOIN modalidades m ON t.modalidade_id = m.id 
                 $where_sql
                 ORDER BY t.data DESC";

$stmt = $conn->prepare($query_treinos);
if (!$stmt) {
    die("Erro na preparação da query de treinos: " . $conn->error . "<br>Query: " . $query_treinos);
}
if ($params) {
    $stmt->bind_param($types, ...$params);
}
if (!$stmt->execute()) {
    die("Erro ao executar query de treinos: " . $stmt->error);
}
$treinos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Treinos - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <aside class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <img src="<?php echo htmlspecialchars($staff['foto_perfil']); ?>" alt="Foto do Staff">
                <h3><?php echo htmlspecialchars($staff['nome']); ?></h3>
                <p><?php echo ucfirst($staff['tipo']); ?></p>
            </div>
            <nav class="staff-menu">
                <a href="dashboard_staff.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="perfil_staff.php" class="menu-item">
                    <i class="fas fa-user"></i> Perfil
                </a>
                <a href="gerir_atletas.php" class="menu-item">
                    <i class="fas fa-users"></i> Gerir Atletas
                </a>
                <a href="gerir_treinos.php" class="menu-item active">
                    <i class="fas fa-dumbbell"></i> Gerir Treinos
                </a>
                <a href="gerir_jogos.php" class="menu-item">
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
                <?php if ($staff['tipo'] == 'dirigente'): ?>
                <a href="financeiro.php" class="menu-item">
                    <i class="fas fa-euro-sign"></i> Financeiro
                </a>
                <?php endif; ?>
            </nav>
            <div class="logout-section">
                <form action="logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="dashboard-content">
            <div class="page-header">
                <h1>Gestão de Treinos</h1>
                <button class="add-training-btn" onclick="openModal()">
                    <i class="fas fa-plus"></i> Novo Treino
                </button>
            </div>

            <div class="trainings-container">
                <div class="trainings-header">
                    <h2>Lista de Treinos</h2>
                </div>

                <div class="filtro-treinos">
                    <div class="form-group">
                        <label for="modalidade">Modalidade:</label>
                        <select id="modalidade" name="modalidade_id" onchange="filterTreinos()">
                            <option value="">Todas</option>
                            <?php while ($mod = $modalidades->fetch_assoc()): ?>
                                <option value="<?php echo $mod['id']; ?>" <?php echo ($filtro_modalidade == $mod['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mod['nome']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data">Data:</label>
                        <input type="date" id="data" name="data" value="<?php echo htmlspecialchars($filtro_data); ?>" onchange="filterTreinos()">
                    </div>
                    <div class="form-group">
                        <label for="treinador">Treinador:</label>
                        <input type="text" id="treinador" name="treinador" placeholder="Nome do treinador" value="<?php echo htmlspecialchars($filtro_treinador); ?>" onkeyup="filterTreinos()">
                    </div>
                    <button class="btn-primary" onclick="filterTreinos()">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <button class="btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Limpar
                    </button>
                </div>

                <div class="trainings-grid">
                    <?php if ($treinos->num_rows > 0): ?>
                        <?php while ($treino = $treinos->fetch_assoc()): ?>
                            <div class="training-card">
                                <div class="training-header">
                                    <h3 class="training-title"><?php echo htmlspecialchars($treino['modalidade_nome']); ?></h3>
                                    <p class="training-category"><?php echo htmlspecialchars(date('d/m/Y', strtotime($treino['data']))); ?></p>
                                </div>
                                <div class="training-info">
                                    <p><i class="fas fa-clock"></i> Horário: <?php echo htmlspecialchars(date('H:i', strtotime($treino['hora_inicio']))) . ' - ' . htmlspecialchars(date('H:i', strtotime($treino['hora_fim']))); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> Local: <?php echo htmlspecialchars($treino['local']); ?></p>
                                    <p><i class="fas fa-user-tie"></i> Treinador: <?php echo htmlspecialchars($treino['treinador']); ?></p>
                                    <p><i class="fas fa-align-left"></i> Descrição: <?php echo htmlspecialchars($treino['descricao']); ?></p>
                                    <?php if (!empty($treino['anexo'])): ?>
                                        <p><i class="fas fa-paperclip"></i> Anexo: <a href="<?php echo htmlspecialchars(str_replace('../uploads/', '../uploads/', $treino['anexo'])); ?>" target="_blank">Ver ficheiro</a></p>
                                    <?php endif; ?>
                                </div>
                                <div class="training-actions">
                                    <button class="action-btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($treino)); ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form action="remover_treino.php" method="post" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja remover este treino?');">
                                        <input type="hidden" name="treino_id" value="<?php echo $treino['id']; ?>">
                                        <button type="submit" class="action-btn btn-delete">
                                            <i class="fas fa-trash"></i> Remover
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>Nenhum treino encontrado.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Modal para Adicionar/Editar Treino -->
            <div id="trainingModal" class="modal">
                <div class="modal-content">
                    <span class="close-button" onclick="closeModal()">&times;</span>
                    <h2 id="modalTitle">Adicionar Novo Treino</h2>
                    <form id="trainingForm" method="POST" action="processar_treino.php" enctype="multipart/form-data">
                        <input type="hidden" id="treino_id" name="treino_id">
                        <div class="form-group">
                            <label for="modalidade_id">Modalidade:</label>
                            <select id="modalidade_id" name="modalidade_id" required>
                                <?php 
                                    // Reposicionar o ponteiro do resultado para o início antes de iterar novamente
                                    if ($modalidades_result->num_rows > 0) {
                                        $modalidades_result->data_seek(0);
                                        while ($mod = $modalidades_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $mod['id']; ?>"><?php echo htmlspecialchars($mod['nome']); ?></option>
                                <?php 
                                        endwhile;
                                    } else {
                                        echo '<option value="">Nenhuma modalidade disponível</option>';
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-row-inline">
                            <div class="form-group">
                                <label for="data">Data:</label>
                                <input type="date" id="data" name="data" required>
                            </div>
                            <div class="form-group">
                                <label for="hora_inicio">Hora de Início:</label>
                                <input type="time" id="hora_inicio" name="hora_inicio" required>
                            </div>
                            <div class="form-group">
                                <label for="hora_fim">Hora de Fim:</label>
                                <input type="time" id="hora_fim" name="hora_fim" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="local">Local:</label>
                            <input type="text" id="local" name="local" required>
                        </div>
                        <div class="form-group">
                            <label for="treinador">Treinador:</label>
                            <input type="text" id="treinador" name="treinador" required>
                        </div>
                        <div class="form-group">
                            <label for="descricao">Descrição:</label>
                            <textarea id="descricao" name="descricao"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="anexo">Anexo (PDF, Imagem):</label>
                            <input type="file" id="anexo" name="anexo" accept=".pdf,image/*">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Guardar Treino</button>
                            <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        function openModal() {
            document.getElementById('trainingModal').style.display = 'flex';
            document.getElementById('modalTitle').innerText = 'Adicionar Novo Treino';
            document.getElementById('trainingForm').reset();
            document.getElementById('treino_id').value = '';
            // Definir o action para adicionar
            document.getElementById('trainingForm').action = 'processar_treino.php';
        }

        function closeModal() {
            document.getElementById('trainingModal').style.display = 'none';
        }

        function openEditModal(treino) {
            document.getElementById('trainingModal').style.display = 'flex';
            document.getElementById('modalTitle').innerText = 'Editar Treino';
            
            document.getElementById('treino_id').value = treino.id;
            document.getElementById('modalidade_id').value = treino.modalidade_id;
            document.getElementById('data').value = treino.data;
            document.getElementById('hora_inicio').value = treino.hora_inicio;
            document.getElementById('hora_fim').value = treino.hora_fim;
            document.getElementById('local').value = treino.local;
            document.getElementById('treinador').value = treino.treinador;
            document.getElementById('descricao').value = treino.descricao;

            // Definir o action para editar
            document.getElementById('trainingForm').action = 'processar_treino.php?action=edit';
        }

        function filterTreinos() {
            const modalidade = document.getElementById('modalidade').value;
            const data = document.getElementById('data').value;
            const treinador = document.getElementById('treinador').value;

            let url = 'gerir_treinos.php?';
            if (modalidade) url += `modalidade_id=${modalidade}&`;
            if (data) url += `data=${data}&`;
            if (treinador) url += `treinador=${encodeURIComponent(treinador)}&`;
            
            window.location.href = url.slice(0, -1); // Remove o último '&'
        }

        function clearFilters() {
            window.location.href = 'gerir_treinos.php';
        }
    </script>
</body>
</html> 