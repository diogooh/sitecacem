<?php
session_start();
require 'db.php';

// Obter o ID do atleta da URL
$atleta_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$atleta = null;
$mensalidades = [];

if ($atleta_id > 0) {
    // Buscar informações básicas do atleta
    $stmt = $conn->prepare("SELECT u.*, e.nome AS escalao_nome, m.nome AS modalidade_nome, COALESCE(u.foto_perfil, '../img/default-avatar.png') as foto FROM users u LEFT JOIN escaloes e ON u.escalao_id = e.id LEFT JOIN modalidades m ON e.modalidade_id = m.id WHERE u.id = ? AND u.tipo = 'atleta'");
    $stmt->bind_param("i", $atleta_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $atleta = $result->fetch_assoc();
    $stmt->close();

    // Buscar mensalidades do atleta (Assumindo tabela 'mensalidades')
    if ($atleta) { // Apenas buscar mensalidades se o atleta existir
        $stmt_mensalidades = $conn->prepare("SELECT * FROM mensalidades WHERE atleta_id = ? ORDER BY ano DESC, mes DESC");
        $stmt_mensalidades->bind_param("i", $atleta_id);
        $stmt_mensalidades->execute();
        $mensalidades_result = $stmt_mensalidades->get_result();
        while ($mensalidade = $mensalidades_result->fetch_assoc()) {
            $mensalidades[] = $mensalidade;
        }
        $stmt_mensalidades->close();
    }
}

// Se o atleta não for encontrado, redirecionar de volta ou mostrar erro
if (!$atleta) {
    // Redirecionar de volta para a página de escalões ou dashboard
    header("Location: admin_escaloes.php"); // Ou para admin_dashboard.php
    exit();
}

// Processar atualização de status de mensalidade
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['atualizar_status_mensalidade'])) {
    $mensalidade_id = (int) $_POST['mensalidade_id'];
    $novo_status = $conn->real_escape_string($_POST['novo_status']);
    $atleta_id_redir = (int) $_POST['atleta_id']; // Para redirecionar de volta ao atleta correto

    // Buscar a mensalidade para garantir que existe e pertence ao atleta
    $stmt_check = $conn->prepare("SELECT id FROM mensalidades WHERE id = ? AND atleta_id = ?");
    $stmt_check->bind_param("ii", $mensalidade_id, $atleta_id_redir);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Atualizar o status
        $stmt_update = $conn->prepare("UPDATE mensalidades SET status = ?, data_pagamento = ? WHERE id = ?");
        
        $data_pagamento = null; // Por padrão, define data_pagamento como NULL
        if (strtolower($novo_status) == 'paga') {
            $data_pagamento = date('Y-m-d'); // Define a data atual se o status for 'Paga'
        }
        
        $stmt_update->bind_param("ssi", $novo_status, $data_pagamento, $mensalidade_id);
        $stmt_update->execute();
        $stmt_update->close();
    }
    $stmt_check->close();

    // Redirecionar de volta para a página do atleta
    header("Location: admin_atleta_perfil.php?id=" . $atleta_id_redir);
    exit();
}

// Processar remoção de mensalidade
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['remover_mensalidade'])) {
    $mensalidade_id = (int) $_POST['mensalidade_id'];
    $atleta_id_redir = (int) $_POST['atleta_id']; // Para redirecionar de volta ao atleta correto

    // Buscar a mensalidade para garantir que existe e pertence ao atleta
    $stmt_check = $conn->prepare("SELECT id FROM mensalidades WHERE id = ? AND atleta_id = ?");
    $stmt_check->bind_param("ii", $mensalidade_id, $atleta_id_redir);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Remover a mensalidade
        $stmt_delete = $conn->prepare("DELETE FROM mensalidades WHERE id = ?");
        $stmt_delete->bind_param("i", $mensalidade_id);
        $stmt_delete->execute();
        $stmt_delete->close();
    }
    $stmt_check->close();

    // Redirecionar de volta para a página do atleta
    header("Location: admin_atleta_perfil.php?id=" . $atleta_id_redir);
    exit();
}

// Processar atribuição de novas mensalidades
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['atribuir_mensalidades'])) {
    $atleta_id_post = (int) $_POST['atleta_id'];
    $ano = (int) $_POST['ano'];
    $meses = isset($_POST['meses']) ? (array) $_POST['meses'] : [];
    $valor = (float) $_POST['valor'];

    if ($atleta_id_post > 0 && $ano > 0 && !empty($meses) && $valor > 0) {
        // Preparar a inserção
        $stmt_insert = $conn->prepare("INSERT INTO mensalidades (atleta_id, mes, ano, valor, status) VALUES (?, ?, ?, ?, ?)");

        foreach ($meses as $mes) {
            $mes_int = (int) $mes;
            // Verificar se a mensalidade já existe para evitar duplicados
            $stmt_check = $conn->prepare("SELECT id FROM mensalidades WHERE atleta_id = ? AND mes = ? AND ano = ?");
            $stmt_check->bind_param("iii", $atleta_id_post, $mes_int, $ano);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows == 0) {
                // Inserir apenas se não existir
                $status_inicial = 'Pendente';
                $stmt_insert->bind_param("iiids", $atleta_id_post, $mes_int, $ano, $valor, $status_inicial);
                $stmt_insert->execute();
            }
            $stmt_check->close();
        }
        $stmt_insert->close();
    }

    // Redirecionar de volta para a página do atleta
    header("Location: admin_atleta_perfil.php?id=" . $atleta_id_post);
    exit();
}

// Upload de documento médico
if (isset($_POST['upload_doc'])) {
    $tipo = $_POST['tipo'];
    $validade = !empty($_POST['validade']) ? $_POST['validade'] : null;
    $apolice = !empty($_POST['apolice']) ? $_POST['apolice'] : null;
    $atleta_id = $_GET['id'];
    if (isset($_FILES['ficheiro']) && $_FILES['ficheiro']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['ficheiro']['name'], PATHINFO_EXTENSION);
        $nome_arquivo = uniqid('doc_', true) . '.' . $ext;
        $destino = __DIR__ . '/../uploads/medicos/' . $nome_arquivo;
        if (!is_dir(__DIR__ . '/../uploads/medicos/')) {
            mkdir(__DIR__ . '/../uploads/medicos/', 0777, true);
        }
        if (move_uploaded_file($_FILES['ficheiro']['tmp_name'], $destino)) {
            $stmt = $conn->prepare('INSERT INTO documentos_medicos (atleta_id, tipo, nome_arquivo, validade, apolice) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('issss', $atleta_id, $tipo, $nome_arquivo, $validade, $apolice);
            $stmt->execute();
            $stmt->close();
            $msg = 'Documento carregado com sucesso!';
        } else {
            $msg = 'Erro ao mover o ficheiro.';
        }
    } else {
        $msg = 'Selecione um ficheiro válido.';
    }
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Atleta - <?= htmlspecialchars($atleta['nome']) ?> - ACC</title>
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

        /* Sidebar Styles - Mantendo igual ao admin_dashboard */
        .dashboard-sidebar.staff-sidebar {
            background: linear-gradient(135deg, #1a237e 0%, #1976d2 100%);
            color: white;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        /* Main Content Styles */
        .dashboard-content {
            flex-grow: 1;
            padding: 40px 30px 30px 30px;
        }

        .section-header {
            margin-bottom: 2rem;
        }

        .section-header h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }

        /* Profile Card Styles */
        .atleta-profile-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-light);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .profile-info {
            flex-grow: 1;
        }

        .profile-info h2 {
            font-size: 2rem;
            color: var(--primary-color);
            margin: 0 0 1rem 0;
        }

        .profile-info p {
            margin: 0.5rem 0;
            color: var(--text-light);
            font-size: 1.1rem;
        }

        .profile-info p strong {
            color: var(--text-dark);
            margin-right: 0.5rem;
        }

        /* Profile Details Grid */
        .profile-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .detail-item {
            background: rgba(26,35,126,0.03);
            padding: 1rem;
            border-radius: var(--border-radius);
        }

        .detail-item strong {
            display: block;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            letter-spacing: 0.5px;
        }

        .detail-item span {
            color: var(--text-dark);
            font-size: 1.1rem;
        }

        /* Sports Info Form */
        .profile-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .profile-section h2 {
            color: var(--primary-color);
            margin: 0 0 1.5rem 0;
            font-size: 1.5rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-item label {
            color: var(--text-light);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .info-item input,
        .info-item select {
            padding: 0.8rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .info-item input:focus,
        .info-item select:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
        }

        /* Buttons */
        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-add {
            background: var(--success);
            color: var(--white);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-add:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .btn-remove {
            background: var(--danger);
            color: var(--white);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-remove:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        /* Table Styles */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 1.5rem 0;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .data-table th,
        .data-table td {
            padding: 1rem 1.5rem;
            text-align: left;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .data-table th {
            background: var(--primary-color);
            color: var(--white);
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .data-table tbody tr:hover {
            background: rgba(26,35,126,0.02);
        }

        .data-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Styles */
        .status-pendente {
            color: var(--danger);
            font-weight: 500;
            padding: 0.3rem 0.8rem;
            background: rgba(220,53,69,0.1);
            border-radius: 20px;
            display: inline-block;
        }

        .status-paga {
            color: var(--success);
            font-weight: 500;
            padding: 0.3rem 0.8rem;
            background: rgba(40,167,69,0.1);
            border-radius: 20px;
            display: inline-block;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-layout {
                flex-direction: column;
            }
            .dashboard-sidebar.staff-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .dashboard-content {
                margin-left: 0;
            }
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            .profile-photo {
                width: 120px;
                height: 120px;
            }
        }

        .upload-doc-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px #0001;
            padding: 2rem 2rem 1.5rem 2rem;
            margin-bottom: 2rem;
            max-width: 500px;
        }
        .upload-doc-section h3 {
            margin-top: 0;
            color: #1a237e;
            font-size: 1.3rem;
            margin-bottom: 1.2rem;
        }
        .upload-doc-form {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
        }
        .upload-doc-form label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.3rem;
        }
        .upload-doc-form input[type='text'],
        .upload-doc-form input[type='date'],
        .upload-doc-form select,
        .upload-doc-form input[type='file'] {
            padding: 0.7rem;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-size: 1rem;
            background: #f8f9fa;
            margin-bottom: 0.2rem;
        }
        .upload-doc-form input[type='file'] {
            background: #fff;
            padding: 0.5rem;
        }
        .upload-doc-form button {
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 7px;
            padding: 0.9rem 0;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s;
        }
        .upload-doc-form button:hover {
            background: #1251a3;
        }
        .upload-doc-success {
            color: #28a745;
            margin-bottom: 1rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar staff-sidebar">
            <div class="staff-header">
                <h3>Administração</h3>
                <p>Perfil do Atleta</p>
            </div>
            
            <div class="staff-menu">
                <a href="admin_dashboard.php" class="menu-item">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin_modalidades.php" class="menu-item">
                    <i class="fas fa-dumbbell"></i> Modalidades
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
                <h1>Perfil do Atleta</h1>
            </div>

            <?php if ($atleta): ?>
                <div class="atleta-profile-card">
                    <div class="profile-header">
                        <div class="profile-photo-admin">
                            <?php 
                            $image_path = htmlspecialchars($atleta['foto']);
                            // Check if the path does not already start with ../ and is not the default image path
                            if (strpos($image_path, '../') !== 0 && strpos($image_path, 'img/default-avatar.png') === false) {
                                $image_path = '../' . $image_path;
                            }
                            ?>
                            <img src="<?php echo $image_path; ?>" alt="Foto de Perfil do Atleta">
                        </div>
                        <div class="profile-info">
                            <h2><?= htmlspecialchars($atleta['nome']) ?></h2>
                            <p><strong>CIPA:</strong> <?= htmlspecialchars($atleta['cip']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($atleta['email']) ?></p>
                            <p><strong>Telefone:</strong> <?= htmlspecialchars($atleta['telefone']) ?: 'N/A' ?></p>
                            <p><strong>Data de Nascimento:</strong> <?= htmlspecialchars($atleta['data_nascimento']) ?: 'N/A' ?></p>
                            <p><strong>Género:</strong> <?= isset($atleta['genero']) ? htmlspecialchars($atleta['genero']) : 'N/A' ?></p>
                        </div>
                    </div>

                    <div class="profile-details">
                         <div class="detail-item">
                             <strong>Escalão/Equipa:</strong>
                             <span><?= htmlspecialchars($atleta['modalidade_nome']) ?> - <?= htmlspecialchars($atleta['escalao_nome']) ?></span>
                         </div>
                        <div class="detail-item">
                            <strong>Data de Registo:</strong>
                            <span><?= htmlspecialchars($atleta['data_registo']) ?></span>
                        </div>
                        <!-- Adicionar mais detalhes aqui -->
                    </div>

                    <!-- Secção de Informações Desportivas (Editável para Admin) -->
                    <div class="profile-section">
                        <h2>Informações Desportivas</h2>
                        <form id="sports-info-form" action="perfil_atleta_save.php" method="POST">
                             <input type="hidden" name="user_id" value="<?= $atleta['id'] ?>">
                             <!-- Add a hidden field to indicate this is an admin update -->
                             <input type="hidden" name="admin_update" value="true">

                            <div class="info-grid">
                                <div class="info-item">
                                    <label for="escalao">Escalão</label>
                                     <!-- This should likely be a select box tied to available escaloes -->
                                    <input type="text" id="escalao" name="escalao" value="<?= htmlspecialchars($atleta['escalao_nome'] ?? '') ?>">
                                </div>
                                <div class="info-item">
                                    <label for="posicao">Posição</label>
                                     <!-- This should likely be a select box with predefined positions -->
                                    <input type="text" id="posicao" name="posicao" value="<?= htmlspecialchars($atleta['posicao'] ?? '') ?>">
                                </div>
                                <div class="info-item">
                                    <label for="numero">Número</label>
                                     <input type="number" id="numero" name="numero" value="<?= htmlspecialchars($atleta['numero'] ?? '') ?>">
                                </div>
                                <div class="info-item">
                                    <label for="lateralidade">Lateralidade</label>
                                     <!-- This should likely be a select box with options -->
                                    <input type="text" id="lateralidade" name="lateralidade" value="<?= htmlspecialchars($atleta['pe_dominante'] ?? '') ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn-primary" name="save_sports_info"><i class="fas fa-save"></i> Guardar Alterações Desportivas</button>
                        </form>
                    </div>

                    <!-- Secções para Mensalidades, Pagamentos, Equipamentos, etc. -->

                    <!-- Secção para Atribuir Novas Mensalidades -->
                    <h4>Atribuir Novas Mensalidades</h4>
                    <div class="atleta-profile-card" style="padding: 20px; margin-bottom: 20px;">
                         <form method="post">
                              <input type="hidden" name="atleta_id" value="<?= $atleta['id'] ?>">
                             <div class="form-group">
                                 <label for="ano_mensalidade">Ano:</label>
                                 <select name="ano" id="ano_mensalidade" required>
                                     <?php
                                         $current_year = date('Y');
                                         for ($i = $current_year; $i <= $current_year + 2; $i++) {
                                             echo '<option value="' . $i . '">' . $i . '</option>';
                                         }
                                     ?>
                                 </select>
                             </div>

                             <div class="form-group">
                                 <label>Meses:</label>
                                 <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 10px;">
                                     <?php
                                         $meses = [
                                             1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                                             5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                                             9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                                         ];
                                         foreach ($meses as $num => $nome) {
                                             echo '<label style="display: flex; align-items: center;"><input type="checkbox" name="meses[]" value="' . $num . '"> ' . $nome . '</label>';
                                         }
                                     ?>
                                 </div>
                             </div>

                             <div class="form-group">
                                  <label for="valor_mensalidade">Valor:</label>
                                  <input type="number" name="valor" id="valor_mensalidade" step="0.01" required>
                             </div>

                              <button type="submit" name="atribuir_mensalidades" class="btn-add"><i class="fas fa-plus"></i> Atribuir Mensalidades</button>
                         </form>
                    </div>

                    <h4>Mensalidades</h4>
                    <?php if (!empty($mensalidades)): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Mês/Ano</th>
                                    <th>Valor</th>
                                    <th>Status</th>
                                    <th>Data Pagamento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensalidades as $mensalidade): ?>
                                <tr>
                                    <td><?= sprintf('%02d', $mensalidade['mes']) ?>/<?= $mensalidade['ano'] ?></td>
                                    <td><?= htmlspecialchars($mensalidade['valor']) ?> €</td>
                                    <td>
                                        <?php
                                            $status = strtolower(htmlspecialchars($mensalidade['status']));
                                            $status_class = '';
                                            if ($status == 'pendente') {
                                                $status_class = 'status-pendente';
                                            } else if ($status == 'paga') {
                                                $status_class = 'status-paga';
                                            }
                                        ?>
                                        <span class="<?= $status_class ?>"><?= $status ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($mensalidade['data_pagamento']) ?: 'N/A' ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <?php
                                                $status = strtolower(htmlspecialchars($mensalidade['status']));
                                                $mensalidade_id = $mensalidade['id'];
                                                $atleta_id_current = $atleta['id'];
                                            ?>
                                            <?php if ($status == 'pendente'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="mensalidade_id" value="<?= $mensalidade_id ?>">
                                                    <input type="hidden" name="novo_status" value="Paga">
                                                    <input type="hidden" name="atleta_id" value="<?= $atleta_id_current ?>"> <!-- Manter o atleta_id para redirecionamento -->
                                                    <button type="submit" name="atualizar_status_mensalidade" class="btn-add btn-small" style="background-color: #28a745;"><i class="fas fa-check"></i> Paga</button>
                                                </form>
                                            <?php elseif ($status == 'paga'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="mensalidade_id" value="<?= $mensalidade_id ?>">
                                                    <input type="hidden" name="novo_status" value="Pendente">
                                                    <input type="hidden" name="atleta_id" value="<?= $atleta_id_current ?>"> <!-- Manter o atleta_id para redirecionamento -->
                                                    <button type="submit" name="atualizar_status_mensalidade" class="btn-remove btn-small"><i class="fas fa-times"></i> Pendente</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Nenhuma mensalidade encontrada para este atleta.</p>
                    <?php endif; ?>

                    <h4>Pagamentos</h4>
                    <p>Histórico de pagamentos virá aqui.</p>

                    <h4>Equipamentos</h4>
                    <p>Informação sobre equipamentos atribuídos virá aqui.</p>

                    <!-- Adicionar outras secções conforme necessário -->

                </div>
            <?php else: ?>
                <p>Atleta não encontrado.</p>
            <?php endif; ?>

            <!-- Formulário de upload de documento médico -->
            <div class="upload-doc-section">
                <h3>Adicionar Documento Médico</h3>
                <?php if (isset($msg)) echo '<div class="upload-doc-success">' . $msg . '</div>'; ?>
                <form method="post" enctype="multipart/form-data" class="upload-doc-form">
                    <label>Tipo:</label>
                    <select name="tipo" required>
                        <option value="exame">Exame Médico</option>
                        <option value="seguro">Seguro Desportivo</option>
                    </select>
                    <label>Validade:</label>
                    <input type="date" name="validade">
                    <label>Apólice (se seguro):</label>
                    <input type="text" name="apolice">
                    <label>Ficheiro:</label>
                    <input type="file" name="ficheiro" required>
                    <button type="submit" name="upload_doc">Carregar Documento</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 