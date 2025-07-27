<?php
session_start();
require 'db.php';

// Buscar estatísticas para os gráficos
$total_atletas = $conn->query("SELECT COUNT(*) as total FROM users WHERE tipo = 'atleta' AND status = 'aprovado'")->fetch_assoc()['total'];
$mensalidades_pagas = $conn->query("SELECT COUNT(*) as total FROM mensalidades WHERE status = 'Paga'")->fetch_assoc()['total'];
$mensalidades_pendentes = $conn->query("SELECT COUNT(*) as total FROM mensalidades WHERE status = 'Pendente'")->fetch_assoc()['total'];

// Buscar dados para o gráfico
$ano_filtro = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
$dados_grafico = $conn->query("
    SELECT 
        mes,
        COUNT(CASE WHEN status = 'Paga' THEN 1 END) as pagas,
        COUNT(CASE WHEN status = 'Pendente' THEN 1 END) as pendentes,
        SUM(CASE WHEN status = 'Paga' THEN valor ELSE 0 END) as valor_pago,
        SUM(CASE WHEN status = 'Pendente' THEN valor ELSE 0 END) as valor_pendente
    FROM mensalidades 
    WHERE ano = $ano_filtro
    GROUP BY mes
    ORDER BY mes
");

$dados_pagas = array_fill(0, 12, 0);
$dados_pendentes = array_fill(0, 12, 0);
$valores_pagos = array_fill(0, 12, 0);
$valores_pendentes = array_fill(0, 12, 0);

// Resetar o ponteiro do resultado novamente para o loop principal
if ($dados_grafico && $dados_grafico->num_rows > 0) {
    $dados_grafico->data_seek(0);
}

while ($dados_grafico && $row = $dados_grafico->fetch_assoc()) {
    $dados_pagas[$row['mes'] - 1] = (int)$row['pagas'];
    $dados_pendentes[$row['mes'] - 1] = (int)$row['pendentes'];
    $valores_pagos[$row['mes'] - 1] = (float)$row['valor_pago'];
    $valores_pendentes[$row['mes'] - 1] = (float)$row['valor_pendente'];
}

// Verificação dos dados na tabela mensalidades
$verificar_dados = $conn->query("
    SELECT 
        COUNT(*) as total_mensalidades,
        COUNT(CASE WHEN status = 'Paga' THEN 1 END) as total_pagas,
        COUNT(CASE WHEN status = 'Pendente' THEN 1 END) as total_pendentes,
        MIN(CONCAT(ano, '-', LPAD(mes, 2, '0'))) as primeiro_periodo,
        MAX(CONCAT(ano, '-', LPAD(mes, 2, '0'))) as ultimo_periodo
    FROM mensalidades
");

$dados_verificacao = $verificar_dados->fetch_assoc();

// Buscar atletas e suas mensalidades
$atletas = $conn->query("
    SELECT u.id, u.nome, u.cip, e.nome as escalao_nome, m.nome as modalidade_nome,
           COALESCE(u.foto_perfil, 'default-avatar.png') as foto,
           GROUP_CONCAT(DISTINCT CONCAT(mens.mes, '/', mens.ano, ':', mens.status) ORDER BY mens.ano DESC, mens.mes DESC) as mensalidades
    FROM users u
    LEFT JOIN escaloes e ON u.escalao_id = e.id
    LEFT JOIN modalidades m ON e.modalidade_id = m.id
    LEFT JOIN mensalidades mens ON u.id = mens.atleta_id
    WHERE u.tipo = 'atleta' AND u.status = 'aprovado'
    GROUP BY u.id
    ORDER BY u.nome
");

// Processar formulários
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['atribuir_mensalidade'])) {
        $atleta_id = (int)$_POST['atleta_id'];
        $mes = (int)$_POST['mes'];
        $ano = (int)$_POST['ano'];
        $valor = (float)$_POST['valor'];
        
        $conn->query("INSERT INTO mensalidades (atleta_id, mes, ano, valor, status) 
                     VALUES ($atleta_id, $mes, $ano, $valor, 'Pendente')");
    }
    
    if (isset($_POST['atualizar_status'])) {
        $mensalidade_id = (int)$_POST['mensalidade_id'];
        $novo_status = $_POST['novo_status'];
        $data_pagamento = $novo_status === 'Paga' ? date('Y-m-d') : null;
        
        $conn->query("UPDATE mensalidades SET status = '$novo_status', data_pagamento = " . 
                    ($data_pagamento ? "'$data_pagamento'" : "NULL") . 
                    " WHERE id = $mensalidade_id");
    }
    
    header("Location: admin_financas.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Financeira - ACC</title>
    <link rel="stylesheet" href="dashboard_nav.css">
    <link rel="stylesheet" href="dashboard_atleta.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 15px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #1a237e;
            margin: 0 0 10px 0;
            font-size: 1.2em;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #1976d2;
            margin: 10px 0;
        }
        
        .chart-container {
            background: white;
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
            margin-bottom: 30px;
            max-width: 800px;
            width: 800px;
            height: auto;
            margin-left: auto;
            margin-right: auto;
        }
        
        .financial-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(26,35,126,0.10);
        }
        
        .financial-table th,
        .financial-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .financial-table th {
            background-color: #1a237e;
            color: white;
            font-weight: 600;
            font-size: 1em;
        }
        
        .financial-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .financial-table tbody tr:hover {
            background-color: #e9ecef;
            transition: background-color 0.2s ease;
        }
        
        .financial-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: 600;
            margin: 3px;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        
        .status-pendente {
            background-color: #ffe0b2;
            color: #f57c00;
        }
        
        .status-paga {
            background-color: #c8e6c9;
            color: #388e3c;
        }
        
        .btn-action {
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #2196f3;
            color: white;
        }
        
        .btn-edit:hover {
            background: #1976d2;
        }
        
        .btn-add {
            background: #4caf50;
            color: white;
        }
        
        .btn-add:hover {
            background: #388e3c;
        }
        
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
            background: white;
            width: 90%;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(26,35,126,0.20);
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        /* Estilos adicionais para os formulários nos modais */
        .modal-content .form-group input[type="text"],
        .modal-content .form-group input[type="email"],
        .modal-content .form-group input[type="number"],
        .modal-content .form-group select {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 8px; /* Bordas mais suaves */
            box-sizing: border-box; /* Inclui padding e borda no tamanho total */
            font-size: 1em;
            transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
        }

        .modal-content .form-group input[type="text"]:focus,
        .modal-content .form-group input[type="email"]:focus,
        .modal-content .form-group input[type="number"]:focus,
        .modal-content .form-group select:focus {
            border-color: #1976d2; /* Cor azul ao focar */
            box-shadow: 0 0 8px rgba(25, 118, 210, 0.2); /* Sombra suave ao focar */
            outline: none; /* Remover o outline padrão do navegador */
        }

        .modal-content .form-group label {
             display: block;
             margin-bottom: 6px;
             color: #1a237e; /* Cor mais escura para os labels */
             font-weight: 600;
             font-size: 0.95em;
        }

        .modal-content .btn-action {
             margin-top: 15px; /* Espaçamento acima dos botões */
        }

        /* Estilos para a foto e info do atleta na tabela financeira (copia de admin_escaloes.php) */
        .atleta-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        .atleta-foto {
            width: 40px; /* Ajustar tamanho se necessário para a tabela */
            height: 40px; /* Ajustar tamanho se necessário para a tabela */
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
            font-size: 0.95em; /* Ajustar tamanho da fonte */
        }
        .atleta-info span {
            color: #666;
            font-size: 0.85em; /* Ajustar tamanho da fonte */
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
                <a href="admin_escaloes.php" class="menu-item">
                    <i class="fas fa-users"></i> Escalões
                </a>
                <a href="admin-pendentes.php" class="menu-item">
                    <i class="fas fa-clock"></i> Pedidos Pendentes
                </a>
                <a href="admin_financas.php" class="menu-item active">
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
                <h1>Gestão Financeira</h1>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total de Atletas</h3>
                    <div class="number"><?= $total_atletas ?></div>
                </div>
                <div class="stat-card">
                    <h3>Mensalidades Pagas</h3>
                    <div class="number"><?= $mensalidades_pagas ?></div>
                </div>
                <div class="stat-card">
                    <h3>Mensalidades Pendentes</h3>
                    <div class="number"><?= $mensalidades_pendentes ?></div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="chart-container">
                <form method="get" id="filtroAnoForm">
                    <label for="filtroAno">Selecionar Ano:</label>
                    <select name="ano" id="filtroAno" onchange="document.getElementById('filtroAnoForm').submit();">
                        <?php
                        $anos_disponiveis = $conn->query("SELECT DISTINCT ano FROM mensalidades ORDER BY ano DESC");
                        $ano_selecionado = isset($_GET['ano']) ? (int)$_GET['ano'] : date('Y');
                        while($ano_row = $anos_disponiveis->fetch_assoc()) {
                            $ano = $ano_row['ano'];
                            $selected = ($ano == $ano_selecionado) ? 'selected' : '';
                            echo "<option value=\"$ano\" $selected>$ano</option>";
                        }
                        ?>
                    </select>
                </form>
                <canvas id="mensalidadesChart"></canvas>
            </div>

            <!-- Tabela de Atletas e Mensalidades -->
            <table class="financial-table">
                <thead>
                    <tr>
                        <th>Atleta</th>
                        <th>Escalão</th>
                        <th>Modalidade</th>
                        <th>Mensalidades</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($atleta = $atletas->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="atleta-info">
                                    <?php
                                        $foto_path = htmlspecialchars($atleta['foto']);
                                        // Ajustar o caminho da foto com base na localização consolidada em ../uploads/
                                        if ($atleta['foto'] !== 'default-avatar.png') {
                                             $nome_ficheiro = basename($atleta['foto']); // Extrai apenas o nome do ficheiro
                                            $foto_path = '../uploads/' . $nome_ficheiro; // Constrói o caminho relativo a src/ para a nova localização
                                        } else {
                                              // Caminho para o avatar default dentro de src/ (assumindo)
                                            $foto_path = 'src/default-avatar.png'; // Ajustar se default-avatar não estiver em src/
                                        }
                                    ?>
                                    <img src="<?= $foto_path ?>" alt="Foto do atleta" class="atleta-foto">
                                    <div class="atleta-detalhes">
                                        <strong><?= htmlspecialchars($atleta['nome']) ?></strong>
                                        <span>CIP: <?= htmlspecialchars($atleta['cip']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($atleta['escalao_nome']) ?></td>
                            <td><?= htmlspecialchars($atleta['modalidade_nome']) ?></td>
                            <td>
                                <?php
                                if ($atleta['mensalidades']) {
                                    // Buscar mensalidades individualmente para ter o ID
                                    $stmt_mensalidades_atleta = $conn->prepare("SELECT id, mes, ano, status FROM mensalidades WHERE atleta_id = ? ORDER BY ano DESC, mes DESC");
                                    $stmt_mensalidades_atleta->bind_param("i", $atleta['id']);
                                    $stmt_mensalidades_atleta->execute();
                                    $mensalidades_individuais = $stmt_mensalidades_atleta->get_result();

                                    while ($mensalidade_individual = $mensalidades_individuais->fetch_assoc()) {
                                        $mes_ano = sprintf('%02d', $mensalidade_individual['mes']) . '/' . $mensalidade_individual['ano'];
                                        $status = $mensalidade_individual['status'];
                                        $mensalidade_id = $mensalidade_individual['id'];
                                        $status_class = strtolower($status) === 'paga' ? 'status-paga' : 'status-pendente';
                                        // Adiciona onclick para abrir o modal com o ID e status
                                        echo "<span class='status-badge $status_class' onclick='abrirModalEditarMensalidade(" . $mensalidade_id . ", " . json_encode($status) . ")'>$mes_ano: $status</span> ";
                                    }
                                    $stmt_mensalidades_atleta->close();
                                } else {
                                    echo "<span class='status-badge status-pendente'>Sem mensalidades</span>";
                                }
                                ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button class="btn-action btn-add" onclick="abrirModalAtribuir(<?= $atleta['id'] ?>)">
                                        <i class="fas fa-plus"></i> Atribuir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para Atribuir Mensalidade -->
    <div id="modalAtribuir" class="modal">
        <div class="modal-content">
            <h2>Atribuir Mensalidade</h2>
            <form method="post">
                <input type="hidden" name="atleta_id" id="atleta_id_atribuir">
                <div class="form-group">
                    <label>Mês:</label>
                    <select name="mes" required>
                        <?php
                            $meses = [
                                1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                            ];
                            foreach ($meses as $num => $nome) {
                                echo "<option value=\"$num\">$nome</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Ano:</label>
                    <select name="ano" required>
                        <?php 
                        $ano_atual = date('Y');
                        for($i = $ano_atual - 1; $i <= $ano_atual + 1; $i++): 
                        ?>
                            <option value="<?= $i ?>"><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Valor:</label>
                    <input type="number" name="valor" step="0.01" required>
                </div>
                <button type="submit" name="atribuir_mensalidade" class="btn-action btn-add">
                    <i class="fas fa-plus"></i> Atribuir Mensalidade
                </button>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Mensalidade -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h2>Editar Mensalidade</h2>
            <form method="post">
                <input type="hidden" name="mensalidade_id" id="mensalidade_id_editar">
                <div class="form-group">
                    <label>Estado:</label>
                    <select name="novo_status" required>
                        <option value="Pendente">Pendente</option>
                        <option value="Paga">Paga</option>
                    </select>
                </div>
                <button type="submit" name="atualizar_status" class="btn-action btn-edit">
                    <i class="fas fa-save"></i> Atualizar Status
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <script>
    // Configuração do gráfico
    const ctx = document.getElementById('mensalidadesChart').getContext('2d');
    
    // Registar o plugin datalabels
    Chart.register(ChartDataLabels);

    // Dados para o gráfico
    const dadosPagas = <?= json_encode($dados_pagas) ?>;
    const dadosPendentes = <?= json_encode($dados_pendentes) ?>;
    
    // Converter os dados para garantir que são números
    const dadosPagasNumerico = dadosPagas.map(Number);
    const dadosPendentesNumerico = dadosPendentes.map(Number);

    const anoSelecionado = <?= $ano_filtro ?>;

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 
                    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            datasets: [{
                label: 'Mensalidades Pagas',
                data: dadosPagasNumerico,
                backgroundColor: 'rgba(76, 175, 80, 0.2)',
                borderColor: '#4caf50',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }, {
                label: 'Mensalidades Pendentes',
                data: dadosPendentesNumerico,
                backgroundColor: 'rgba(255, 152, 0, 0.2)',
                borderColor: '#ff9800',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        plugins: [ChartDataLabels],
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Mensalidades por Mês - ' + anoSelecionado,
                    font: {
                        size: 18,
                        weight: 'bold'
                    },
                    padding: 20
                },
                legend: {
                    position: 'top',
                    labels: {
                        padding: 20,
                        font: {
                            size: 14
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y + ' mensalidade(s)';
                            return label;
                        }
                    }
                },
                datalabels: {
                    display: true,
                    color: 'white', // Cor do texto dos valores
                    anchor: 'center', // Posição do valor (centro da barra)
                    align: 'center', // Alinhamento do valor (centro da barra)
                    formatter: function(value, context) {
                        // Exibe o valor apenas se for maior que 0
                        return value > 0 ? value : '';
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 12
                        }
                    },
                    title: {
                        display: true,
                        text: 'Número de Mensalidades',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Funções para os modais
    function abrirModalAtribuir(atletaId) {
        document.getElementById('atleta_id_atribuir').value = atletaId;
        document.getElementById('modalAtribuir').style.display = 'block';
    }

    // Nova função para abrir o modal de edição de mensalidade
    function abrirModalEditarMensalidade(mensalidadeId, statusAtual) {
        document.getElementById('mensalidade_id_editar').value = mensalidadeId;
        document.querySelector('#modalEditar select[name="novo_status"]').value = statusAtual;
        document.getElementById('modalEditar').style.display = 'block';
    }

    // Fechar modais quando clicar fora
    window.onclick = function(event) {
        if (event.target.className === 'modal') {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>
</html> 