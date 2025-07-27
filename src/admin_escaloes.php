<?php
session_start();
require 'db.php';

// Buscar todos os escalões com suas modalidades
$escaloes_result = $conn->query("
    SELECT e.*, m.nome AS modalidade_nome 
    FROM escaloes e 
    JOIN modalidades m ON e.modalidade_id = m.id 
    ORDER BY m.nome, FIELD(e.nome, 'Bambis', 'Minis', 'Infantis', 'Iniciados', 'Juvenis', 'Juniores', 'Seniores')
");

// Adicionar verificação de erro para a query de escalões
if (!$escaloes_result) {
    die("Erro na consulta de escalões: " . $conn->error);
}

// Buscar todos os atletas aprovados com suas imagens e escalão associado
$atletas_result = $conn->query("
    SELECT u.*, e.nome AS escalao_nome, m.nome AS modalidade_nome,
           COALESCE(u.foto_perfil, 'default-avatar.png') as foto
    FROM users u
    LEFT JOIN escaloes e ON u.escalao_id = e.id
    LEFT JOIN modalidades m ON e.modalidade_id = m.id
    WHERE u.tipo = 'atleta' AND u.status = 'aprovado'
    ORDER BY u.nome
");

// Adicionar verificação de erro para a query de atletas
if (!$atletas_result) {
    die("Erro na consulta de atletas: " . $conn->error);
}

// Buscar todos os treinadores com escalão associado
$treinadores_result = $conn->query("
    SELECT u.*, e.nome AS escalao_nome
    FROM users u
    JOIN escaloes e ON u.escalao_id = e.id
    WHERE u.tipo = 'treinador' AND u.status = 'aprovado'
    ORDER BY u.nome
");

// Adicionar verificação de erro para a query de treinadores
if (!$treinadores_result) {
    die("Erro na consulta de treinadores: " . $conn->error);
}

// Organizar dados por escalão
$escaloes_data = [];
while ($escalao = $escaloes_result->fetch_assoc()) {
    $escaloes_data[$escalao['id']] = $escalao;
    $escaloes_data[$escalao['id']]['atletas'] = [];
    $escaloes_data[$escalao['id']]['treinadores'] = [];
}

// Adicionar atletas aos seus respetivos escalões
// Resetar o ponteiro do resultado dos atletas antes de iterar
$atletas_result->data_seek(0);
while ($atleta = $atletas_result->fetch_assoc()) {
    if ($atleta['escalao_id'] && isset($escaloes_data[$atleta['escalao_id']])) {
        $atleta_com_foto_path = $atleta;
        // Ajustar o caminho da foto
        if (isset($atleta_com_foto_path['foto']) && !empty($atleta_com_foto_path['foto'])) {
            $foto_caminho_db = $atleta_com_foto_path['foto'];
            
            // Ajustar o caminho da foto para a localização consolidada em ../uploads/
            if ($foto_caminho_db === 'default-avatar.png') {
                // Caminho para o avatar default dentro de src/
                $atleta_com_foto_path['foto'] = 'src/default-avatar.png'; // Caminho relativo correto
            } else {
                // Para qualquer caminho na base de dados que não seja o default, assumir que o ficheiro está agora em ../uploads/
                $nome_ficheiro = basename($foto_caminho_db); // Extrai apenas o nome do ficheiro
                $atleta_com_foto_path['foto'] = '../uploads/' . $nome_ficheiro; // Constrói o caminho relativo a src/ para a nova localização
            }
        }
        $escaloes_data[$atleta['escalao_id']]['atletas'][] = $atleta_com_foto_path;
    }
}

// Adicionar treinadores aos seus respetivos escalões
// Resetar o ponteiro do resultado dos treinadores antes de iterar
$treinadores_result->data_seek(0);
while ($treinador = $treinadores_result->fetch_assoc()) {
    if ($treinador['escalao_id'] && isset($escaloes_data[$treinador['escalao_id']])) {
        $escaloes_data[$treinador['escalao_id']]['treinadores'][] = $treinador;
    }
}

// Processar formulários
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['adicionar_atleta_escalao'])) {
        $atleta_id = (int) $_POST['atleta_id'];
        $escalao_id = (int) $_POST['escalao_id'];
        // Buscar o id da modalidade 'Andebol'
        $result_andebol = $conn->query("SELECT id FROM modalidades WHERE nome = 'Andebol' LIMIT 1");
        $andebol_id = 1;
        if ($result_andebol && $row = $result_andebol->fetch_assoc()) {
            $andebol_id = (int)$row['id'];
        }
        $conn->query("UPDATE users SET escalao_id = $escalao_id, modalidade_id = $andebol_id WHERE id = $atleta_id");
    }

    if (isset($_POST['remover_atleta_escalao'])) {
        $atleta_id = (int) $_POST['atleta_id'];
        $conn->query("UPDATE users SET escalao_id = NULL WHERE id = $atleta_id");
    }
    
    // Redirecionar para evitar reenvio do formulário
    header("Location: admin_escaloes.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Escalões - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f6fb;
        }
        .dashboard-layout {
            display: flex;
        }
        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 100%);
            color: white;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .staff-header img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid #fff;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .staff-header h3 {
            font-size: 1.3em;
            margin-bottom: 2px;
        }
        .staff-header p {
            font-size: 1em;
            color: #cfd8dc;
        }
        .staff-menu .menu-item {
            display: flex;
            align-items: center;
            padding: 13px 18px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 7px;
            font-weight: 500;
            font-size: 1.05em;
            transition: all 0.2s;
        }
        .staff-menu .menu-item:hover, .staff-menu .menu-item.active {
            background: rgba(255,255,255,0.18);
            color: #fff;
            transform: translateX(7px) scale(1.03);
        }
        .staff-menu .menu-item i {
            margin-right: 12px;
            font-size: 1.1em;
        }
        .dashboard-content {
            flex-grow: 1; /* Permite que o conteúdo principal ocupe o espaço restante */
            padding: 40px 30px 30px 30px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .escalao-card {
            background: white;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .escalao-header {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }
        .escalao-header h3 {
            margin: 0;
            color: #1a237e;
            font-size: 1.2em;
        }
        .escalao-content {
            padding: 20px;
            display: none;
            /* Adicionado para animação */
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
        }
        .escalao-content.active {
            display: block; /* Mantido para fallback, mas max-height controla a visibilidade */
            max-height: 1000px; /* Valor grande para cobrir o conteúdo */
            padding: 20px;
        }
        .atleta-list {
            margin-top: 15px;
        }
        .atleta-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            border: 1px solid #eee;
            box-sizing: border-box; /* Garantir que padding e border não afetam o tamanho */
        }
        .atleta-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        .atleta-foto {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #1976d2;
        }
        .atleta-detalhes {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .atleta-info strong {
            color: #1a237e;
            font-size: 1em;
        }
        .atleta-info span {
            color: #666;
            font-size: 0.9em;
        }
        .treinadores-info {
            font-size: 0.95em;
            color: #1a237e;
            margin-top: 10px;
            font-weight: 500;
        }
        .treinadores-info i {
            margin-right: 8px;
            color: #1976d2;
        }
        .btn-add {
            background: #1976d2;
            color: white;
            padding: 10px 22px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            font-size: 1em;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-add:hover {
            background: #1565c0;
            transform: scale(1.04);
        }
        .btn-remove {
            background: #f44336;
            color: white;
            padding: 8px 18px;
            border-radius: 5px;
            border: none;
            font-weight: 500;
            font-size: 1em;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .btn-remove:hover {
            background: #d32f2f;
            transform: scale(1.05);
        }
        .form-group {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee; /* Separador visual */
        }
        .form-group:last-child {
             border-bottom: none;
             padding-bottom: 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 1.05em;
        }
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            background-color: #fff;
        }
        .toggle-icon {
            transition: transform 0.3s;
        }
        .escalao-header.active .toggle-icon {
            transform: rotate(180deg);
        }
        .logout-button {
            margin-top: auto;
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .btn-logout {
            display: flex;
            align-items: center;
            padding: 12px 18px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 1.05em;
            background: rgba(244, 67, 54, 0.9);
            transition: all 0.2s;
            width: 100%;
            border: none;
            cursor: pointer;
        }
        .btn-logout:hover {
            background: #f44336;
            transform: translateX(7px) scale(1.03);
        }
        .btn-logout i {
            margin-right: 12px;
            font-size: 1.1em;
        }
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #666;
            background-color: #fefefe;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px dashed #ddd;
        }
        .empty-state i {
            font-size: 3em;
            color: #ccc;
            margin-bottom: 15px;
        }
         .empty-state p {
            margin: 0;
            font-size: 1.1em;
         }
         /* Estilo para atletas sem escalão no select */
         .atleta-disponivel-info {
            font-size: 0.9em;
            color: #555;
         }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <h3>Administração</h3>
                <p>Painel de Controle</p>
            </div>
            
            <div class="staff-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_escaloes.php" class="menu-item active">
                    <i class="fas fa-users"></i> Escalões
                </a>
                <a href="admin-pendentes.php" class="menu-item">
                    <i class="fas fa-clock"></i> Pedidos Pendentes
                </a>
                <a href="admin_financas.php" class="menu-item">
                    <i class="fas fa-dollar-sign"></i> Finanças
                </a>
                <a href="admin_equipamentos.php" class="menu-item">
                    <i class="fas fa-tshirt"></i> Equipamentos
                </a>
            </div>
            <div class="logout-button">
                <form method="post" action="logout.php">
                    <button type="submit" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Terminar Sessão
                    </button>
                </form>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="dashboard-content">
            <div class="section-header">
                <h1>Gestão de Escalões e Atletas</h1>
            </div>

            <!-- Lista de Escalões com Atletas -->
            <?php if (!empty($escaloes_data)): ?>
                <?php foreach ($escaloes_data as $escalao): ?>
                <div class="escalao-card">
                    <div class="escalao-header">
                        <div>
                            <h3><?= htmlspecialchars($escalao['modalidade_nome']) ?> - <?= htmlspecialchars($escalao['nome']) ?> (<?= $escalao['idade_min'] ?>–<?= $escalao['idade_max'] ?> anos)</h3>
                            <?php if (!empty($escalao['treinadores'])): ?>
                                <div class="treinadores-info">
                                    <i class="fas fa-user-tie"></i> Treinadores:
                                    <?php
                                    $treinador_nomes = [];
                                    foreach ($escalao['treinadores'] as $treinador) {
                                        $treinador_nomes[] = htmlspecialchars($treinador['nome']) . ' (' . htmlspecialchars($treinador['cip']) . ')';
                                    }
                                    echo implode(', ', $treinador_nomes);
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="escalao-content">
                        <!-- Formulário para Adicionar Atleta -->
                        <form method="post" class="form-group">
                            <input type="hidden" name="escalao_id" value="<?= $escalao['id'] ?>" />
                            <div class="form-group">
                                <label for="atleta_id_<?= $escalao['id'] ?>">Adicionar Atleta:</label>
                                <select name="atleta_id" id="atleta_id_<?= $escalao['id'] ?>" required>
                                    <option value="">Selecione um atleta</option>
                                    <?php
                                    $atletas_disponiveis = [];
                                    // Resetar o ponteiro do resultado dos atletas e filtrar os sem escalão
                                    $atletas_result->data_seek(0);
                                    while ($atleta = $atletas_result->fetch_assoc()) {
                                        if (empty($atleta['escalao_id'])) {
                                            $atletas_disponiveis[] = $atleta;
                                        }
                                    }
                                    
                                    if (!empty($atletas_disponiveis)):
                                        foreach ($atletas_disponiveis as $atleta):
                                    ?>
                                        <option value="<?= $atleta['id'] ?>">
                                            <?= htmlspecialchars($atleta['nome']) ?> <span class="atleta-disponivel-info">(CIP: <?= htmlspecialchars($atleta['cip']) ?>)</span>
                                        </option>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <option value="" disabled>Nenhum atleta disponível</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <?php if (!empty($atletas_disponiveis)): ?>
                                <button type="submit" name="adicionar_atleta_escalao" class="btn-add">
                                    <i class="fas fa-user-plus"></i> Adicionar Atleta
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-add" disabled style="opacity: 0.6; cursor: not-allowed;">
                                    <i class="fas fa-user-plus"></i> Adicionar Atleta
                                </button>
                            <?php endif; ?>
                        </form>

                        <!-- Lista de Atletas no Escalão -->
                        <div class="atleta-list">
                            <label>Atletas no Escalão:</label>
                            <?php if (!empty($escalao['atletas'])): ?>
                                <?php foreach ($escalao['atletas'] as $atleta): ?>
                                    <div class="atleta-item">
                                        <div class="atleta-info">
                                            <?php
                                            $foto_path = htmlspecialchars($atleta['foto']);
                                            ?>
                                            <img src="<?= $foto_path ?>" alt="Foto do atleta" class="atleta-foto">
                                            <div class="atleta-detalhes">
                                                <strong><?= htmlspecialchars($atleta['nome']) ?></strong>
                                                <span>CIP: <?= htmlspecialchars($atleta['cip']) ?></span>
                                            </div>
                                        </div>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="atleta_id" value="<?= $atleta['id'] ?>" />
                                            <button type="submit" name="remover_atleta_escalao" class="btn-remove">
                                                <i class="fas fa-user-minus"></i> Remover
                                            </button>
                                        </form>
                                        <!-- Botão Ver Perfil -->
                                        <a href="admin_atleta_perfil.php?id=<?= $atleta['id'] ?>" class="btn-add" style="background-color: #007bff; /* Cor azul */">
                                            <i class="fas fa-user"></i> Ver Perfil
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <p>Nenhum atleta neste escalão</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                 <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <p>Nenhum escalão encontrado.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para alternar o estado do card
        function toggleEscalao(header) {
            const content = header.nextElementSibling;
            const isActive = header.classList.contains('active');
            
            // Fecha todos os outros cards
            document.querySelectorAll('.escalao-header.active').forEach(activeHeader => {
                if (activeHeader !== header) {
                    activeHeader.classList.remove('active');
                    activeHeader.nextElementSibling.classList.remove('active');
                     activeHeader.nextElementSibling.style.maxHeight = null;
                }
            });
            
            // Alterna o estado do card atual
            header.classList.toggle('active');
            content.classList.toggle('active');
             if (content.classList.contains('active')) {
                content.style.maxHeight = content.scrollHeight + "px";
            } else {
                content.style.maxHeight = null;
            }
        }

        // Adiciona o evento de clique a todos os headers
        document.querySelectorAll('.escalao-card .escalao-header').forEach(header => {
            header.addEventListener('click', () => toggleEscalao(header));
        });

        // Adiciona confirmação antes de remover atleta
        document.querySelectorAll('form button[name="remover_atleta_escalao"]').forEach(button => {
             button.closest('form').addEventListener('submit', function(e) {
                if (!confirm('Tem certeza que deseja remover este atleta do escalão?')) {
                    e.preventDefault();
                }
            });
        });

        // Melhora a experiência do select de atletas
        document.querySelectorAll('select[name="atleta_id"]').forEach(select => {
            select.addEventListener('change', function() {
                const addButton = this.closest('form').querySelector('button[type="submit"]');
                if (this.value && addButton) {
                    addButton.focus();
                }
            });
        });
    });
    </script>
</body>
</html> 