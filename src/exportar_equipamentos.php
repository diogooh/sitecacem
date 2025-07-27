<?php
session_start();
require 'db.php';

// Verificar se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] !== 'admin') {
    header("Location: login.php");
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

$sql = "SELECT u.nome, e.nome AS escalao, u.numero, 
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

// Gerar HTML para Excel
$filename = 'equipamentos_' . date('Y-m-d_H-i-s') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// HTML com formatação Excel
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1976d2; color: white; font-weight: bold; }
        .status-entregue { color: #388e3c; font-weight: bold; }
        .status-pendente { color: #f57c00; font-weight: bold; }
        .header-row { background-color: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <table>
        <tr class="header-row">
            <th colspan="13" style="text-align: center; font-size: 16px; padding: 15px;">
                RELATÓRIO DE EQUIPAMENTOS - ACC
            </th>
        </tr>
        <tr class="header-row">
            <th colspan="13" style="text-align: center; padding: 10px;">
                Data: <?= date('d/m/Y H:i') ?>
            </th>
        </tr>
        <tr>
            <th>Nome do Atleta</th>
            <th>Escalão</th>
            <th>Número</th>
            <th>Equip. Jogo</th>
            <th>Status</th>
            <th>Alt. A</th>
            <th>Status</th>
            <th>Alt. B</th>
            <th>Status</th>
            <th>Fatos Treino</th>
            <th>Status</th>
            <th>Mala</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['nome']) ?></td>
            <td><?= htmlspecialchars($row['escalao']) ?></td>
            <td><?= htmlspecialchars($row['numero']) ?></td>
            <td><?= htmlspecialchars($row['equip_jogo']) ?></td>
            <td class="status-<?= $row['equip_jogo_status'] ?>"><?= ucfirst($row['equip_jogo_status']) ?></td>
            <td><?= htmlspecialchars($row['alt_a']) ?></td>
            <td class="status-<?= $row['alt_a_status'] ?>"><?= ucfirst($row['alt_a_status']) ?></td>
            <td><?= htmlspecialchars($row['alt_b']) ?></td>
            <td class="status-<?= $row['alt_b_status'] ?>"><?= ucfirst($row['alt_b_status']) ?></td>
            <td><?= htmlspecialchars($row['fato_treino']) ?></td>
            <td class="status-<?= $row['fato_treino_status'] ?>"><?= ucfirst($row['fato_treino_status']) ?></td>
            <td><?= htmlspecialchars($row['mala']) ?></td>
            <td class="status-<?= $row['mala_status'] ?>"><?= ucfirst($row['mala_status']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?> 