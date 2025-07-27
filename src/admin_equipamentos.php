<?php
session_start();
require 'db.php';

// Processar alterações de status de equipamentos
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['toggle_status'])) {
    $atleta_id = (int) $_POST['atleta_id'];
    $campo = $_POST['campo'];
    $novo_status = $_POST['novo_status'];
    
    // Verificar se já existe registo para este atleta
    $stmt_check = $conn->prepare("SELECT id FROM equipamentos WHERE atleta_id = ?");
    $stmt_check->bind_param("i", $atleta_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        // Atualizar registo existente
        $stmt = $conn->prepare("UPDATE equipamentos SET {$campo}_status = ? WHERE atleta_id = ?");
        $stmt->bind_param("si", $novo_status, $atleta_id);
        $ok = $stmt->execute();
        $stmt->close();
    } else {
        // Criar novo registo
        $stmt = $conn->prepare("INSERT INTO equipamentos (atleta_id, {$campo}_status) VALUES (?, ?)");
        $stmt->bind_param("is", $atleta_id, $novo_status);
        $ok = $stmt->execute();
        $stmt->close();
    }
    $stmt_check->close();
    
    if ($ok) {
        header("Location: admin_equipamentos.php?sucesso=1");
    } else {
        header("Location: admin_equipamentos.php?erro=1");
    }
    exit();
}

// Processar guardar equipamentos e número
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['guardar_equipamento'])) {
    $atleta_id = (int) $_POST['atleta_id'];
    $numero = (int) $_POST['numero'];
    $equip_jogo = $_POST['equip_jogo'];
    $alt_a = $_POST['alt_a'];
    $alt_b = $_POST['alt_b'];
    $fato_treino = $_POST['fato_treino'];
    $mala = $_POST['mala'];
    
    // Verificar se o número já existe para outro atleta
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE numero = ? AND id != ? AND tipo = 'atleta'");
    $stmt_check->bind_param("ii", $numero, $atleta_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $erro_numero = "O número $numero já está atribuído a outro atleta!";
    } else {
        // Atualizar número do atleta
        $stmt_update_numero = $conn->prepare("UPDATE users SET numero = ? WHERE id = ?");
        $stmt_update_numero->bind_param("ii", $numero, $atleta_id);
        $stmt_update_numero->execute();
        $stmt_update_numero->close();
        
        // Atualizar ou inserir equipamentos
        $stmt_check_equip = $conn->prepare("SELECT id FROM equipamentos WHERE atleta_id = ?");
        $stmt_check_equip->bind_param("i", $atleta_id);
        $stmt_check_equip->execute();
        $stmt_check_equip->store_result();
        
        if ($stmt_check_equip->num_rows > 0) {
            // Atualizar equipamentos existentes
            $stmt_update = $conn->prepare("UPDATE equipamentos SET equip_jogo = ?, alt_a = ?, alt_b = ?, fato_treino = ?, mala = ? WHERE atleta_id = ?");
            $stmt_update->bind_param("sssssi", $equip_jogo, $alt_a, $alt_b, $fato_treino, $mala, $atleta_id);
            $ok = $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Inserir novos equipamentos
            $stmt_insert = $conn->prepare("INSERT INTO equipamentos (atleta_id, equip_jogo, alt_a, alt_b, fato_treino, mala) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("isssss", $atleta_id, $equip_jogo, $alt_a, $alt_b, $fato_treino, $mala);
            $ok = $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check_equip->close();
        if ($ok) {
            header("Location: admin_equipamentos.php?sucesso=1");
        } else {
            header("Location: admin_equipamentos.php?erro=1");
        }
        exit();
    }
    $stmt_check->close();
}

// Processar atualização de status do pedido de equipamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_pedido_id'])) {
    $pedido_id = (int)$_POST['atualizar_pedido_id'];
    $novo_status = $_POST['novo_status'];
    $stmt = $conn->prepare('UPDATE pedidos_equipamentos SET status = ? WHERE id = ?');
    $stmt->bind_param('si', $novo_status, $pedido_id);
    $stmt->execute();
    $stmt->close();

    // Se aprovado ou entregue, atualizar equipamentos
    if ($novo_status === 'aprovado' || $novo_status === 'entregue') {
        $pedido = $conn->query("SELECT * FROM pedidos_equipamentos WHERE id = $pedido_id")->fetch_assoc();
        $atleta_id = $pedido['atleta_id'];
        $tipo = $pedido['tipo_equipamento'];
        $numero = $pedido['numero'];
        $tamanho = $pedido['tamanho'];
        // Garante que existe registro de equipamentos para o atleta
        $check = $conn->query("SELECT id FROM equipamentos WHERE atleta_id = $atleta_id");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO equipamentos (atleta_id) VALUES ($atleta_id)");
        }
        // Atualiza conforme o tipo
        if ($tipo === 'equip_jogo') {
            $conn->query("UPDATE equipamentos SET equip_jogo_status = 'entregue', equip_jogo = '" . $conn->real_escape_string($tamanho) . "' WHERE atleta_id = $atleta_id");
            if ($numero) {
                $conn->query("UPDATE users SET numero = '" . $conn->real_escape_string($numero) . "' WHERE id = $atleta_id");
            }
        } elseif ($tipo === 'alt_a') {
            $conn->query("UPDATE equipamentos SET alt_a_status = 'entregue', alt_a = '" . $conn->real_escape_string($tamanho) . "' WHERE atleta_id = $atleta_id");
        } elseif ($tipo === 'alt_b') {
            $conn->query("UPDATE equipamentos SET alt_b_status = 'entregue', alt_b = '" . $conn->real_escape_string($tamanho) . "' WHERE atleta_id = $atleta_id");
        } elseif ($tipo === 'fato_treino') {
            $conn->query("UPDATE equipamentos SET fato_treino_status = 'entregue', fato_treino = '" . $conn->real_escape_string($tamanho) . "' WHERE atleta_id = $atleta_id");
        } elseif ($tipo === 'mala') {
            $conn->query("UPDATE equipamentos SET mala_status = 'entregue' WHERE atleta_id = $atleta_id");
        }
    }
    header('Location: admin_equipamentos.php?sucesso=1');
    exit();
}

// Filtros
$filtro_nome = isset($_GET['nome']) ? $_GET['nome'] : '';
$filtro_escalao = isset($_GET['escalao']) ? $_GET['escalao'] : '';
$filtro_numero = isset($_GET['numero']) ? $_GET['numero'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';

// Construir query com filtros
$where_conditions = ["u.tipo = 'atleta'"];
$params = [];
$types = "";

if (!empty($filtro_nome)) {
    $where_conditions[] = "u.nome LIKE ?";
    $params[] = "%$filtro_nome%";
    $types .= "s";
}

if (!empty($filtro_escalao)) {
    $where_conditions[] = "e.nome = ?";
    $params[] = $filtro_escalao;
    $types .= "s";
}

if (!empty($filtro_numero)) {
    $where_conditions[] = "u.numero = ?";
    $params[] = $filtro_numero;
    $types .= "i";
}

if (!empty($filtro_status)) {
    if ($filtro_status == 'pendente') {
        $where_conditions[] = "(eq.equip_jogo_status IS NULL OR eq.equip_jogo_status = 'pendente' OR eq.alt_a_status IS NULL OR eq.alt_a_status = 'pendente' OR eq.alt_b_status IS NULL OR eq.alt_b_status = 'pendente' OR eq.fato_treino_status IS NULL OR eq.fato_treino_status = 'pendente' OR eq.mala_status IS NULL OR eq.mala_status = 'pendente')";
    } else {
        $where_conditions[] = "(eq.equip_jogo_status = ? OR eq.alt_a_status = ? OR eq.alt_b_status = ? OR eq.fato_treino_status = ? OR eq.mala_status = ?)";
        $params[] = $filtro_status;
        $params[] = $filtro_status;
        $params[] = $filtro_status;
        $params[] = $filtro_status;
        $params[] = $filtro_status;
        $types .= "sssss";
    }
}

$where_clause = implode(" AND ", $where_conditions);

$sql = "SELECT u.id, u.nome, e.nome AS escalao, u.numero, 
        COALESCE(eq.equip_jogo, '') AS equip_jogo, 
        COALESCE(eq.alt_a, '') AS alt_a, 
        COALESCE(eq.alt_b, '') AS alt_b, 
        COALESCE(eq.fato_treino, '') AS fato_treino, 
        COALESCE(eq.mala, '') AS mala,
        COALESCE(eq.equip_jogo_status, 'pendente') AS equip_jogo_status, 
        COALESCE(eq.alt_a_status, 'pendente') AS alt_a_status, 
        COALESCE(eq.alt_b_status, 'pendente') AS alt_b_status, 
        COALESCE(eq.fato_treino_status, 'pendente') AS fato_treino_status, 
        COALESCE(eq.mala_status, 'pendente') AS mala_status
        FROM users u
        LEFT JOIN escaloes e ON u.escalao_id = e.id
        LEFT JOIN equipamentos eq ON u.id = eq.atleta_id
        WHERE $where_clause ORDER BY e.nome, u.nome";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$equipamentos = [];
while ($row = $result->fetch_assoc()) {
    $equipamentos[] = $row;
}

// Buscar escalões para o filtro
$escaloes = [];
$sql_escaloes = "SELECT DISTINCT nome FROM escaloes ORDER BY nome";
$result_escaloes = $conn->query($sql_escaloes);
while ($escalao = $result_escaloes->fetch_assoc()) {
    $escaloes[] = $escalao['nome'];
}

// Buscar pedidos de equipamentos dos atletas
$sql_pedidos = "SELECT p.*, u.nome AS atleta_nome FROM pedidos_equipamentos p JOIN users u ON p.atleta_id = u.id ORDER BY p.data_pedido DESC";
$result_pedidos = $conn->query($sql_pedidos);
$pedidos = [];
while ($row = $result_pedidos->fetch_assoc()) {
    $pedidos[] = $row;
}

// Estatísticas
$total_entregues = 0;
$total_pendentes = 0;
foreach ($equipamentos as $eq) {
    $campos_status = ['equip_jogo_status', 'alt_a_status', 'alt_b_status', 'fato_treino_status', 'mala_status'];
    foreach ($campos_status as $campo) {
        if ($eq[$campo] == 'entregue') {
            $total_entregues++;
        } else {
            $total_pendentes++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Equipamentos - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Dashboard Layout */
        .dashboard-layout {
            display: block;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 100%);
            color: white;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
        }
        
        .staff-header h3 {
            font-size: 1.3em;
            margin-bottom: 2px;
        }
        
        .staff-header p {
            font-size: 1em;
            color: #cfd8dc;
        }
        
        .staff-menu {
            flex-grow: 1;
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
        
        .staff-menu .menu-item:hover, 
        .staff-menu .menu-item.active {
            background: rgba(255,255,255,0.18);
            color: #fff;
            transform: translateX(7px) scale(1.03);
        }
        
        .staff-menu .menu-item i {
            margin-right: 12px;
            font-size: 1.1em;
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
        
        /* Main Content */
        .dashboard-content {
            margin-left: 280px;
            max-width: calc(100vw - 280px);
            padding: 40px 30px 30px 30px;
            display: block;
            width: 100%;
        }
        
        .section-header {
            margin-bottom: 2rem;
        }
        
        .section-header h1 {
            font-size: 2rem;
            color: #1a237e;
            margin: 0;
            font-weight: 600;
        }
        
        /* Filtros e Estatísticas */
        .filtros-container {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }
        
        .filtros-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .filtro-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .filtro-item label {
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }
        
        .filtro-item input, .filtro-item select {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
        }
        
        .btn-filtrar {
            background: #1976d2;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-limpar {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-exportar {
            background: #4CAF50; /* Verde */
            color: #fff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-exportar:hover {
            background: #388e3c; /* Verde mais escuro */
            transform: translateX(7px) scale(1.03);
        }
        
        .btn-exportar i {
            font-size: 1.1em;
        }
        
        .estatisticas {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-numero {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a237e;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        
        /* Equipment Table */
        .equip-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 2rem 0;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            overflow: hidden;
        }
        
        .equip-table th, .equip-table td {
            padding: 1rem 0.8rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            font-size: 0.9rem;
        }
        
        .equip-table th {
            background: #1a237e;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        .equip-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Status Icons */
        .status-icon {
            cursor: pointer;
            font-size: 1.2rem;
            transition: transform 0.2s;
        }
        
        .status-icon:hover {
            transform: scale(1.2);
        }
        
        .status-entregue {
            color: #28a745;
        }
        
        .status-pendente {
            color: #ffc107;
        }
        
        .equip-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .equip-texto {
            font-size: 0.85rem;
        }
        .alerta {
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 1.1em;
            margin-bottom: 18px;
            text-align: center;
            font-weight: 500;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .alerta-sucesso {
            background: #e8f5e9;
            color: #388e3c;
            border: 1px solid #a5d6a7;
        }
        .alerta-erro {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        .pedidos-scroll {
            width: 100%;
            overflow-x: auto;
            box-sizing: border-box;
        }
        .equip-table {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
        }
        .dashboard-card {
            max-width: 100%;
            margin-left: 0;
            box-sizing: border-box;
        }
        .pedidos-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            box-sizing: border-box;
        }
        .dashboard-card.pedidos-card {
            max-width: 1300px;
            margin-left: 350px;
            margin-right: 24px;
            margin-top: 80px;
            width: calc(100% - 374px);
        }
        .pedidos-scroll {
            overflow-x: auto;
            width: 100%;
        }
        .equip-table {
            width: 100%;
            min-width: 600px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <h3>Administração</h3>
                <p>Gestão de Equipamentos</p>
            </div>
            
            <div class="staff-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_escaloes.php" class="menu-item">
                    <i class="fas fa-users"></i> Escalões
                </a>
                <a href="admin-pendentes.php" class="menu-item">
                    <i class="fas fa-clock"></i> Pedidos Pendentes
                </a>
                <a href="admin_financas.php" class="menu-item">
                    <i class="fas fa-dollar-sign"></i> Finanças
                </a>
                <a href="admin_equipamentos.php" class="menu-item active">
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
        <div class="dashboard-content" style="display:block; width:100%;">
            <?php if (isset($_GET['sucesso'])): ?>
                <div class="alerta alerta-sucesso" id="alerta-msg">Alteração guardada com sucesso!</div>
            <?php elseif (isset($_GET['erro'])): ?>
                <div class="alerta alerta-erro" id="alerta-msg">Ocorreu um erro ao guardar. Tente novamente.</div>
            <?php endif; ?>
            <div class="section-header">
                <h1>Gestão de Equipamentos</h1>
            </div>
            
            <!-- Filtros e Estatísticas -->
            <div class="filtros-container">
                <form method="get" class="filtros-row">
                    <div class="filtro-item">
                        <label>Nome do Atleta:</label>
                        <input type="text" name="nome" value="<?= htmlspecialchars($filtro_nome) ?>" placeholder="Pesquisar por nome...">
                    </div>
                    <div class="filtro-item">
                        <label>Escalão:</label>
                        <select name="escalao">
                            <option value="">Todos os escalões</option>
                            <?php foreach ($escaloes as $esc): ?>
                                <option value="<?= htmlspecialchars($esc) ?>" <?= $filtro_escalao == $esc ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($esc) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filtro-item">
                        <label>Número:</label>
                        <input type="number" name="numero" value="<?= htmlspecialchars($filtro_numero) ?>" placeholder="Número...">
                    </div>
                    <div class="filtro-item">
                        <label>Status:</label>
                        <select name="status">
                            <option value="">Todos</option>
                            <option value="entregue" <?= $filtro_status == 'entregue' ? 'selected' : '' ?>>Entregue</option>
                            <option value="pendente" <?= $filtro_status == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        </select>
                    </div>
                    <div class="filtro-item" style="align-self: end;">
                        <button type="submit" class="btn-filtrar">Filtrar</button>
                        <button type="button" class="btn-limpar" onclick="limparFiltros()">Limpar</button>
                        <button type="button" class="btn-exportar" onclick="exportarEquipamentos()">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                    </div>
                </form>
                
                <div class="estatisticas">
                    <div class="stat-item">
                        <div class="stat-numero"><?= count($equipamentos) ?></div>
                        <div class="stat-label">Total de Atletas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-numero" style="color: #28a745;"><?= $total_entregues ?></div>
                        <div class="stat-label">Equipamentos Entregues</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-numero" style="color: #ffc107;"><?= $total_pendentes ?></div>
                        <div class="stat-label">Equipamentos Pendentes</div>
                    </div>
                </div>
            </div>
            
            <table class="equip-table">
                <thead>
                    <tr>
                        <th>Nome do Atleta</th>
                        <th>Escalão</th>
                        <th>Número</th>
                        <th>Equip. Jogo</th>
                        <th>Alt. A</th>
                        <th>Alt. B</th>
                        <th>Fatos Treino</th>
                        <th>Malas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipamentos as $eq): ?>
                    <tr>
                        <td><?= htmlspecialchars($eq['nome']) ?></td>
                        <td><?= htmlspecialchars($eq['escalao']) ?></td>
                        <td>
                            <span class="numero-atual"><?= htmlspecialchars($eq['numero']) ?></span>
                            <button type="button" class="btn-edit-equip" style="background:none; border:none; cursor:pointer; color:#1976d2; font-size:1.1em; vertical-align:middle; margin-left:6px;">
                                <i class="fas fa-pen"></i>
                            </button>
                        </td>
                        <td>
                            <div class="equip-info">
                                <span class="equip-texto"><?= htmlspecialchars($eq['equip_jogo']) ?></span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="atleta_id" value="<?= $eq['id'] ?>">
                                    <input type="hidden" name="campo" value="equip_jogo">
                                    <input type="hidden" name="novo_status" value="<?= $eq['equip_jogo_status'] == 'entregue' ? 'pendente' : 'entregue' ?>">
                                    <button type="submit" name="toggle_status" class="status-icon <?= $eq['equip_jogo_status'] == 'entregue' ? 'status-entregue' : 'status-pendente' ?>">
                                        <i class="fas fa-<?= $eq['equip_jogo_status'] == 'entregue' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div class="equip-info">
                                <span class="equip-texto"><?= htmlspecialchars($eq['alt_a']) ?></span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="atleta_id" value="<?= $eq['id'] ?>">
                                    <input type="hidden" name="campo" value="alt_a">
                                    <input type="hidden" name="novo_status" value="<?= $eq['alt_a_status'] == 'entregue' ? 'pendente' : 'entregue' ?>">
                                    <button type="submit" name="toggle_status" class="status-icon <?= $eq['alt_a_status'] == 'entregue' ? 'status-entregue' : 'status-pendente' ?>">
                                        <i class="fas fa-<?= $eq['alt_a_status'] == 'entregue' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div class="equip-info">
                                <span class="equip-texto"><?= htmlspecialchars($eq['alt_b']) ?></span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="atleta_id" value="<?= $eq['id'] ?>">
                                    <input type="hidden" name="campo" value="alt_b">
                                    <input type="hidden" name="novo_status" value="<?= $eq['alt_b_status'] == 'entregue' ? 'pendente' : 'entregue' ?>">
                                    <button type="submit" name="toggle_status" class="status-icon <?= $eq['alt_b_status'] == 'entregue' ? 'status-entregue' : 'status-pendente' ?>">
                                        <i class="fas fa-<?= $eq['alt_b_status'] == 'entregue' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div class="equip-info">
                                <span class="equip-texto"><?= htmlspecialchars($eq['fato_treino']) ?></span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="atleta_id" value="<?= $eq['id'] ?>">
                                    <input type="hidden" name="campo" value="fato_treino">
                                    <input type="hidden" name="novo_status" value="<?= $eq['fato_treino_status'] == 'entregue' ? 'pendente' : 'entregue' ?>">
                                    <button type="submit" name="toggle_status" class="status-icon <?= $eq['fato_treino_status'] == 'entregue' ? 'status-entregue' : 'status-pendente' ?>">
                                        <i class="fas fa-<?= $eq['fato_treino_status'] == 'entregue' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                        <td>
                            <div class="equip-info">
                                <span class="equip-texto"><?= htmlspecialchars($eq['mala']) ?></span>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="atleta_id" value="<?= $eq['id'] ?>">
                                    <input type="hidden" name="campo" value="mala">
                                    <input type="hidden" name="novo_status" value="<?= $eq['mala_status'] == 'entregue' ? 'pendente' : 'entregue' ?>">
                                    <button type="submit" name="toggle_status" class="status-icon <?= $eq['mala_status'] == 'entregue' ? 'status-entregue' : 'status-pendente' ?>">
                                        <i class="fas fa-<?= $eq['mala_status'] == 'entregue' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="form-edit-equip-row" style="display:none; background:#f8f9fa;">
                        <td colspan="8">
                            <form method="post" class="form-edit-equipamento" style="display:flex; gap:1rem; align-items:center; flex-wrap:wrap; padding:1rem;">
                                <input type="hidden" name="atleta_id" value="<?= $eq['id'] ?>">
                                <label>Número: <input type="number" name="numero" value="<?= htmlspecialchars($eq['numero']) ?>" min="1" style="width:60px;"></label>
                                <label>Equip. Jogo: <input type="text" name="equip_jogo" value="<?= htmlspecialchars($eq['equip_jogo']) ?>" style="width:100px;"></label>
                                <label>Alt. A: <input type="text" name="alt_a" value="<?= htmlspecialchars($eq['alt_a']) ?>" style="width:100px;"></label>
                                <label>Alt. B: <input type="text" name="alt_b" value="<?= htmlspecialchars($eq['alt_b']) ?>" style="width:100px;"></label>
                                <label>Fatos Treino: <input type="text" name="fato_treino" value="<?= htmlspecialchars($eq['fato_treino']) ?>" style="width:100px;"></label>
                                <label>Mala: <input type="text" name="mala" value="<?= htmlspecialchars($eq['mala']) ?>" style="width:100px;"></label>
                                <button type="submit" name="guardar_equipamento" class="btn-save-equip" style="background:#1976d2; color:#fff; border:none; border-radius:4px; padding:6px 18px; cursor:pointer; font-size:1em;">Guardar</button>
                                <button type="button" class="btn-cancel-equip" style="background:#eee; color:#333; border:none; border-radius:4px; padding:6px 18px; cursor:pointer; font-size:1em;">Cancelar</button>
                                <?php if (isset($erro_numero)): ?>
                                    <span class="equip-erro" style="color:#dc3545; margin-left:10px;"><?= htmlspecialchars($erro_numero) ?></span>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <!-- Secção de Pedidos de Equipamentos dos Atletas -->
        <div class="dashboard-card pedidos-card">
            <h2 class="card-header">Pedidos de Equipamentos dos Atletas</h2>
            <div class="pedidos-scroll">
                <table class="equip-table">
                    <thead>
                        <tr>
                            <th>Atleta</th>
                            <th>Tipo</th>
                            <th>Número</th>
                            <th>Tamanho</th>
                            <th>Observações</th>
                            <th>Status</th>
                            <th>Data do Pedido</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pedidos) > 0): ?>
                            <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['atleta_nome']) ?></td>
                                <td><?= htmlspecialchars($p['tipo_equipamento']) ?></td>
                                <td><?= htmlspecialchars($p['numero']) ?></td>
                                <td><?= htmlspecialchars($p['tamanho']) ?></td>
                                <td><?= htmlspecialchars($p['observacoes']) ?></td>
                                <td><?= htmlspecialchars($p['status']) ?></td>
                                <td><?= htmlspecialchars($p['data_pedido']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="atualizar_pedido_id" value="<?= $p['id'] ?>">
                                        <select name="novo_status" required>
                                            <option value="pendente" <?= $p['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                            <option value="aprovado" <?= $p['status'] == 'aprovado' ? 'selected' : '' ?>>Aprovado</option>
                                            <option value="entregue" <?= $p['status'] == 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                            <option value="recusado" <?= $p['status'] == 'recusado' ? 'selected' : '' ?>>Recusado</option>
                                        </select>
                                        <button type="submit" class="btn-primary" style="padding:4px 12px; font-size:0.95em; margin-left:4px;">Atualizar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8">Nenhum pedido realizado ainda.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
document.querySelectorAll('.btn-edit-equip').forEach(function(btn, idx) {
    btn.addEventListener('click', function() {
        // Esconde todos os outros forms
        document.querySelectorAll('.form-edit-equip-row').forEach(function(row) { row.style.display = 'none'; });
        // Mostra o form da linha clicada
        btn.closest('tr').nextElementSibling.style.display = '';
    });
});
document.querySelectorAll('.btn-cancel-equip').forEach(function(btn) {
    btn.addEventListener('click', function() {
        btn.closest('.form-edit-equip-row').style.display = 'none';
    });
});
</script>
<script>
window.addEventListener('DOMContentLoaded', function() {
    var alerta = document.getElementById('alerta-msg');
    if (alerta) {
        setTimeout(function() {
            alerta.style.transition = 'opacity 0.4s';
            alerta.style.opacity = '0';
            setTimeout(function() { alerta.remove(); }, 400);
        }, 2000);
    }
});

function limparFiltros() {
    window.location.href = 'admin_equipamentos.php';
}

function exportarEquipamentos() {
    // Pegar os filtros atuais
    var nome = document.querySelector('input[name="nome"]').value;
    var escalao = document.querySelector('select[name="escalao"]').value;
    var numero = document.querySelector('input[name="numero"]').value;
    var status = document.querySelector('select[name="status"]').value;
    
    // Construir URL com filtros
    var url = 'exportar_equipamentos.php?';
    if (nome) url += 'nome=' + encodeURIComponent(nome) + '&';
    if (escalao) url += 'escalao=' + encodeURIComponent(escalao) + '&';
    if (numero) url += 'numero=' + encodeURIComponent(numero) + '&';
    if (status) url += 'status=' + encodeURIComponent(status) + '&';
    
    // Abrir nova janela para download
    window.open(url, '_blank');
}
</script>
</body>
</html> 