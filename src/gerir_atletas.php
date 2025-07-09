<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// Processar edição do atleta
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['editar_atleta_id'])
) {
    // Adicionar verificação mais completa dos campos necessários aqui, como antes:
    if (
        isset($_POST['editar_nome'], $_POST['editar_email'], $_POST['editar_telefone'],
              $_POST['editar_data_nascimento'], $_POST['editar_modalidade_id'], $_POST['editar_escalao_id'])
    ) {
        $id = (int)$_POST['editar_atleta_id'];
        $nome = trim($_POST['editar_nome']);
        $email = trim($_POST['editar_email']);
        $telefone = trim($_POST['editar_telefone']);
        $data_nascimento = $_POST['editar_data_nascimento'];
        $cip = trim($_POST['editar_cip']);
        $modalidade_id = (int)$_POST['editar_modalidade_id'];
        $escalao_id = (int)$_POST['editar_escalao_id'];

        // Processar upload da foto, se houver
        $foto_perfil = null;
        if (isset($_FILES['editar_foto']) && $_FILES['editar_foto']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['editar_foto']['name'], PATHINFO_EXTENSION);
            $foto_nome = 'perfil_' . $id . '_' . time() . '.' . $ext;
            $destino = '../uploads/' . $foto_nome;
            if (!is_dir('../uploads')) { mkdir('../uploads', 0777, true); }
            if (move_uploaded_file($_FILES['editar_foto']['tmp_name'], $destino)) {
                $foto_perfil = $destino;
            }
        }

        // Atualizar o atleta
        if ($foto_perfil) {
            $stmt = $conn->prepare("UPDATE users SET nome=?, email=?, telefone=?, data_nascimento=?, cip=?, modalidade_id=?, escalao_id=?, foto_perfil=? WHERE id=?");
            $stmt->bind_param("sssssiisi", $nome, $email, $telefone, $data_nascimento, $cip, $modalidade_id, $escalao_id, $foto_perfil, $id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET nome=?, email=?, telefone=?, data_nascimento=?, cip=?, modalidade_id=?, escalao_id=? WHERE id=?");
            $stmt->bind_param("sssssiis", $nome, $email, $telefone, $data_nascimento, $cip, $modalidade_id, $escalao_id, $id);
        }

        if ($stmt->execute()) {
            $_SESSION['alert'] = ['message' => 'Atleta atualizado com sucesso!', 'type' => 'success'];
            // DEBUG: Mostrar conteúdo da sessão antes de redirecionar
            // echo '<pre>'; print_r($_SESSION); echo '</pre>'; exit();
            header("Location: gerir_atletas.php");
            exit();
        } else {
            $_SESSION['alert'] = ['message' => 'Erro ao atualizar atleta!', 'type', 'danger'];
            // DEBUG: Mostrar conteúdo da sessão antes de redirecionar
            // echo '<pre>'; print_r($_SESSION); echo '</pre>'; exit();
            header("Location: gerir_atletas.php");
            exit();
        }
    } else {
         // DEBUG: Mostrar que falta campos
         // echo "<script>alert('Faltam campos no formulário de edição!');</script>"; exit();
         $_SESSION['alert'] = ['message' => 'Erro: Campos do formulário de edição incompletos!', 'type' => 'danger'];
         header("Location: gerir_atletas.php");
         exit();
    }
}

// Processar atribuição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atleta_id'], $_POST['modalidade_id'], $_POST['escalao_id'])) {
    $aid = (int)$_POST['atleta_id'];
    $mid = (int)$_POST['modalidade_id'];
    $eid = (int)$_POST['escalao_id'];
    $stmt = $conn->prepare("UPDATE users SET modalidade_id = ?, escalao_id = ? WHERE id = ?");
    $stmt->bind_param("iii", $mid, $eid, $aid);
    if ($stmt->execute()) {
        $_SESSION['alert'] = ['message' => 'Atribuição atualizada com sucesso!', 'type' => 'success'];
        // DEBUG: Mostrar conteúdo da sessão antes de redirecionar
        // echo '<pre>'; print_r($_SESSION); echo '</pre>'; exit();
        header("Location: gerir_atletas.php");
        exit();
    } else {
        $_SESSION['alert'] = ['message' => 'Erro ao atualizar atleta!', 'type', 'danger'];
        // DEBUG: Mostrar conteúdo da sessão antes de redirecionar
        // echo '<pre>'; print_r($_SESSION); echo '</pre>'; exit();
        header("Location: gerir_atletas.php");
        exit();
    }
}

// DEBUG: Mostrar conteúdo da sessão ao carregar a página
// echo '<pre>Sessão ao carregar:</pre><pre>'; print_r($_SESSION); echo '</pre>';

// Buscar informações do staff
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// DEBUG: Mostrar informações do staff
// echo "<pre>Staff Info:"; print_r($staff); echo "</pre>";

// Buscar todas as modalidades para mapear id => nome
$modalidades_map = [];
$modalidades_all = $conn->query("SELECT id, nome FROM modalidades");
if ($modalidades_all) {
    while ($m = $modalidades_all->fetch_assoc()) {
        $modalidades_map[$m['id']] = $m['nome'];
    }
}

// Buscar todos os escaloes para mapear por modalidade
$escaloes_map = [];
$escaloes_all = $conn->query("SELECT id, nome FROM escaloes WHERE ativo = 1");
if ($escaloes_all) {
    while ($e = $escaloes_all->fetch_assoc()) {
        $escaloes_map[$e['id']] = $e['nome'];
    }
}

// Buscar modalidades para filtro
$modalidades = $conn->query("SELECT * FROM modalidades WHERE ativo = 1");

// Filtros
$filtro_nome = isset($_GET['nome']) ? trim($_GET['nome']) : '';
$filtro_modalidade = isset($_GET['modalidade_id']) ? $_GET['modalidade_id'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';

$where = ["tipo = 'atleta'"];
$params = [];
$types = '';

// Se o usuário logado for um treinador, filtre os atletas pelo seu escalão
if ($staff['tipo'] === 'treinador') {
    if (!empty($staff['escalao_id'])) {
        $where[] = 'escalao_id = ?';
        $params[] = $staff['escalao_id'];
        $types .= 'i';
    }
} else { // Para administradores e dirigentes, permitir filtro de escalão via GET
    if (isset($_GET['escalao_id']) && $_GET['escalao_id'] !== '') {
        $where[] = 'escalao_id = ?';
        $params[] = (int)$_GET['escalao_id'];
        $types .= 'i';
    }
}

if ($filtro_nome !== '') {
    $where[] = 'nome LIKE ?';
    $params[] = '%' . $filtro_nome . '%';
    $types .= 's';
}
if ($filtro_modalidade !== '') {
    $where[] = 'modalidade_id = ?';
    $params[] = $filtro_modalidade;
    $types .= 'i';
}
if ($filtro_estado !== '') {
    $where[] = 'status = ?';
    $params[] = $filtro_estado;
    $types .= 's';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$query = "SELECT u.*, COALESCE(u.foto_perfil, '../img/default-avatar.png') as foto, m.nome AS modalidade_nome, e.nome AS escalao_nome FROM users u LEFT JOIN modalidades m ON u.modalidade_id = m.id LEFT JOIN escaloes e ON u.escalao_id = e.id $where_sql ORDER BY nome";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$atletas = $stmt->get_result();

// DEBUG: Mostrar query e parâmetros
// echo "<pre>Query: " . htmlspecialchars($query) . "</pre>";
// echo "<pre>Params: "; print_r($params); echo "</pre>";
// echo "<pre>Types: " . htmlspecialchars($types) . "</pre>";
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerir Atletas - ACC</title>
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
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 2.1em;
            color: var(--text-dark);
            font-weight: 700;
            margin: 0;
        }

        .filtro-atletas {
            background: var(--white);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .filtro-atletas .form-group {
            margin-bottom: 0;
        }

        .filtro-atletas label {
            font-weight: 500;
            color: var(--text-dark);
            margin-bottom: 8px;
            display: block;
        }

        .filtro-atletas input,
        .filtro-atletas select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95em;
            transition: var(--transition);
        }

        .filtro-atletas input:focus,
        .filtro-atletas select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.1);
            outline: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .atletas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .atleta-card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
        }

        .atleta-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(26,35,126,0.15);
        }

        .atleta-header {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 1px solid #eee;
        }

        .atleta-foto {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }

        .atleta-info {
            flex: 1;
        }

        .atleta-nome {
            font-size: 1.2em;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0 0 5px;
        }

        .atleta-cip {
            font-size: 0.9em;
            color: var(--text-light);
            margin: 0;
        }

        .atleta-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }

        .status-ativo {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-inativo {
            background: #ffebee;
            color: #c62828;
        }

        .atleta-content {
            padding: 20px;
        }

        .atleta-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #eee;
        }

        .atleta-info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .info-label {
            color: var(--text-light);
            font-size: 0.9em;
        }

        .info-value {
            color: var(--text-dark);
            font-weight: 500;
        }

        .atleta-actions {
            padding: 15px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-atribuir {
            background: var(--success);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-atribuir:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-editar {
            background: var(--primary-light);
            color: var(--white);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-editar:hover {
            background: var(--primary-color);
            transform: translateY(-2px);
        }

        .staff-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .staff-header img {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 3px solid var(--white);
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }

        .staff-header h3 {
            font-size: 1.3em;
            margin-bottom: 2px;
            color: var(--white);
        }

        .staff-header p {
            font-size: 1em;
            color: rgba(255,255,255,0.8);
        }

        .staff-menu {
            flex-grow: 1;
        }

        .staff-menu .menu-item {
            display: flex;
            align-items: center;
            padding: 13px 18px;
            color: var(--white);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 7px;
            font-weight: 500;
            font-size: 1.05em;
            transition: var(--transition);
        }

        .staff-menu .menu-item:hover,
        .staff-menu .menu-item.active {
            background: rgba(255,255,255,0.18);
            transform: translateX(7px) scale(1.03);
        }

        .staff-menu .menu-item i {
            margin-right: 12px;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .dashboard-sidebar.staff-sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }

            .dashboard-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .atletas-grid {
                grid-template-columns: 1fr;
            }

            .filtro-atletas {
                grid-template-columns: 1fr;
            }
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
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: var(--white);
            padding: 35px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content h2 {
            margin-top: 0;
            margin-bottom: 25px;
            color: var(--primary-color);
            font-size: 1.5em;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95em;
            transition: var(--transition);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary-light);
            outline: none;
            box-shadow: 0 0 0 3px rgba(25,118,210,0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 30px;
        }

        .foto-upload-box {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: var(--border-radius);
        }

        .foto-perfil-edit {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--white);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .foto-upload-box input[type="file"] {
            padding: 10px;
            border: 1px dashed #ccc;
            border-radius: 8px;
            width: 100%;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .dashboard-sidebar.staff-sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }

            .dashboard-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            .atletas-grid {
                grid-template-columns: 1fr;
            }

            .filtro-atletas {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                padding: 25px;
            }

            .foto-upload-box {
                flex-direction: column;
                text-align: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions button,
            .form-actions a {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }

            .atleta-header {
                flex-direction: column;
                text-align: center;
            }

            .atleta-actions {
                flex-direction: column;
                gap: 10px;
            }

            .atleta-actions button,
            .atleta-actions a {
                width: 100%;
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
                    $foto = !empty($staff['foto_perfil']) ? $staff['foto_perfil'] : '';
                    $foto_path = __DIR__ . '/../' . ltrim($foto, '/');
                    if ($foto && file_exists($foto_path)) {
                        echo str_replace(['../uploads/', 'uploads/'], '/sitecacem/uploads/', $foto);
                    } else {
                        echo '/sitecacem/img/default-avatar.png';
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
                <a href="gerir_atletas.php" class="menu-item active">
                    <i class="fas fa-users"></i> Gerir Atletas
                </a>
                <a href="gerir_treinos.php" class="menu-item">
                    <i class="fas fa-running"></i> Gerir Treinos
                </a>
                <a href="gerir_jogos.php" class="menu-item">
                    <i class="fas fa-futbol"></i> Gerir Jogos
                </a>
                <a href="mensagens_staff.php" class="menu-item">
                    <i class="fas fa-envelope"></i> Mensagens
                    <?php
                        // Fetch unread messages count for the badge
                        $stmt_messages = $conn->prepare("SELECT COUNT(*) as count FROM mensagens WHERE destinatario_id = ? AND lida = 0");
                        if ($stmt_messages) {
                            $stmt_messages->bind_param("i", $_SESSION['user_id']);
                            $stmt_messages->execute();
                            $mensagens_nao_lidas = $stmt_messages->get_result()->fetch_assoc();
                            if ($mensagens_nao_lidas['count'] > 0) {
                                echo '<span class="badge">' . $mensagens_nao_lidas['count'] . '</span>';
                            }
                            $stmt_messages->close();
                        }
                    ?>
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
            <div class="page-header">
                <h1>Gerir Atletas</h1>
                <button class="btn-primary" onclick="openModal()">
                    <i class="fas fa-plus"></i> Novo Atleta
                </button>
            </div>

            <form method="get" class="filtro-atletas">
                <div class="form-group">
                    <label for="filtro_nome">Nome</label>
                    <input type="text" name="nome" id="filtro_nome" placeholder="Buscar por nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                </div>
                <div class="form-group">
                    <label for="filtro_modalidade">Modalidade</label>
                    <select name="modalidade_id" id="filtro_modalidade">
                        <option value="">Todas</option>
                        <?php while ($modalidade = $modalidades->fetch_assoc()): ?>
                            <option value="<?php echo $modalidade['id']; ?>" <?php if($filtro_modalidade == $modalidade['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($modalidade['nome']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filtro_estado">Estado</label>
                    <select name="estado" id="filtro_estado">
                        <option value="">Todos</option>
                        <option value="ativo" <?php if($filtro_estado == 'ativo') echo 'selected'; ?>>Ativo</option>
                        <option value="inativo" <?php if($filtro_estado == 'inativo') echo 'selected'; ?>>Inativo</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="gerir_atletas.php" class="btn-secondary">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>

            <div class="atletas-grid">
                <?php while ($atleta = $atletas->fetch_assoc()): ?>
                    <div class="atleta-card">
                        <div class="atleta-header">
                            <?php 
                                $image_path = htmlspecialchars($atleta['foto']);
                                if (strpos($image_path, '../') !== 0 && strpos($image_path, 'img/default-avatar.png') === false) {
                                    $image_path = '../' . $image_path;
                                }
                            ?>
                            <img class="atleta-foto" src="<?php echo $image_path; ?>" alt="Atleta">
                            <div class="atleta-info">
                                <h3 class="atleta-nome"><?php echo htmlspecialchars($atleta['nome']); ?></h3>
                                <p class="atleta-cip">CIPA: <?php echo htmlspecialchars($atleta['cip'] ?? 'N/A'); ?></p>
                            </div>
                            <span class="atleta-status status-<?php echo $atleta['status']; ?>">
                                <?php echo ucfirst($atleta['status']); ?>
                            </span>
                        </div>
                        <div class="atleta-content">
                            <div class="atleta-info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?php echo htmlspecialchars($atleta['email']); ?></span>
                            </div>
                            <div class="atleta-info-row">
                                <span class="info-label">Telemóvel:</span>
                                <span class="info-value"><?php echo htmlspecialchars($atleta['telefone']); ?></span>
                            </div>
                            <div class="atleta-info-row">
                                <span class="info-label">Modalidade:</span>
                                <span class="info-value"><?= htmlspecialchars($atleta['modalidade_nome'] ?? '-') ?></span>
                            </div>
                            <div class="atleta-info-row">
                                <span class="info-label">Escalão:</span>
                                <span class="info-value"><?= htmlspecialchars($atleta['escalao_nome'] ?? '-') ?></span>
                            </div>
                            <div class="atleta-info-row">
                                <span class="info-label">Data Nasc.:</span>
                                <span class="info-value"><?= htmlspecialchars($atleta['data_nascimento']) ?></span>
                            </div>
                        </div>
                        <div class="atleta-actions">
                            <button type="button" class="btn-atribuir" onclick="openModalAtribuir(event, <?php echo $atleta['id']; ?>, '<?php echo isset($atleta['modalidade_id']) ? $atleta['modalidade_id'] : ''; ?>', '<?php echo isset($atleta['escalao_id']) ? $atleta['escalao_id'] : ''; ?>')">
                                <i class="fas fa-user-plus"></i> Atribuir
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Novo Atleta Modal -->
            <div id="novoAtletaModal" class="modal">
                <div class="modal-content">
                    <span class="close-button" onclick="closeModal()">&times;</span>
                    <h2>Novo Atleta</h2>
                    <form action="gerir_atletas.php" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="telefone">Telemóvel</label>
                            <input type="tel" id="telefone" name="telefone" pattern="[0-9]{9}" maxlength="9" required>
                        </div>
                        <div class="form-group">
                            <label for="data_nascimento">Data de Nascimento</label>
                            <input type="date" id="data_nascimento" name="data_nascimento" required>
                        </div>
                        <div class="form-group">
                            <label for="genero">Género</label>
                            <select id="genero" name="genero">
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nif">NIF</label>
                            <input type="text" id="nif" name="nif" maxlength="9" pattern="[0-9]{9}" title="Por favor, insira um NIF válido (9 dígitos)">
                        </div>
                        <div class="form-group">
                            <label for="morada">Morada</label>
                            <textarea id="morada" name="morada" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="cip">CIPA (Número de Identificação da FPA)</label>
                            <input type="text" id="cip" name="cip">
                        </div>
                        <div class="form-group">
                            <label for="modalidade_id">Modalidade</label>
                            <select id="modalidade_id" name="modalidade_id" required>
                                <option value="">Selecione a Modalidade</option>
                                <?php 
                                    $modalidades_op = $conn->query("SELECT id, nome FROM modalidades WHERE ativo = 1");
                                    while($mod = $modalidades_op->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $mod['id']; ?>"><?php echo htmlspecialchars($mod['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="escalao_id">Escalão</label>
                            <select id="escalao_id" name="escalao_id" required>
                                <option value="">Selecione o Escalão</option>
                                <?php 
                                    $escaloes_op = $conn->query("SELECT id, nome FROM escaloes WHERE ativo = 1");
                                    while($esc = $escaloes_op->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $esc['id']; ?>"><?php echo htmlspecialchars($esc['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="posicao">Posição</label>
                            <input type="text" id="posicao" name="posicao">
                        </div>
                        <div class="form-group">
                            <label for="numero">Número de Atleta</label>
                            <input type="number" id="numero" name="numero">
                        </div>
                        <div class="form-group">
                            <label for="pe_dominante">Pé Dominante</label>
                            <input type="text" id="pe_dominante" name="pe_dominante">
                        </div>
                        <div class="form-group">
                            <label for="foto_perfil">Foto de Perfil</label>
                            <input type="file" id="foto_perfil" name="foto_perfil" accept="image/*">
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Adicionar Atleta</button>
                            <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Atribuir Modal -->
            <div id="atribuirModal" class="modal">
                <div class="modal-content">
                    <span class="close-button" onclick="closeModalAtribuir()">&times;</span>
                    <h2>Atribuir Modalidade/Escalão</h2>
                    <form action="gerir_atletas.php" method="post">
                        <input type="hidden" name="atleta_id" id="atribuir_atleta_id">
                        <div class="form-group">
                            <label for="atribuir_modalidade_id">Modalidade</label>
                            <select id="atribuir_modalidade_id" name="modalidade_id" required>
                                <option value="">Selecione a Modalidade</option>
                                <?php 
                                    $modalidades_atribuir = $conn->query("SELECT id, nome FROM modalidades WHERE ativo = 1");
                                    while($mod = $modalidades_atribuir->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $mod['id']; ?>"><?php echo htmlspecialchars($mod['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="atribuir_escalao_id">Escalão</label>
                            <select id="atribuir_escalao_id" name="escalao_id" required>
                                <option value="">Selecione o Escalão</option>
                                <?php 
                                    $escaloes_atribuir = $conn->query("SELECT id, nome FROM escaloes WHERE ativo = 1");
                                    while($esc = $escaloes_atribuir->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $esc['id']; ?>"><?php echo htmlspecialchars($esc['nome']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-primary">Guardar Atribuição</button>
                            <button type="button" class="btn-secondary" onclick="closeModalAtribuir()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <script>
                // Adicionar Modal
                function openModal() {
                    document.getElementById('novoAtletaModal').style.display = 'flex';
                }

                function closeModal() {
                    document.getElementById('novoAtletaModal').style.display = 'none';
                }

                window.onclick = function(event) {
                    if (event.target == document.getElementById('novoAtletaModal')) {
                        closeModal();
                    }
                }

                // Atribuir Modal
                function openModalAtribuir(event, atletaId, modalidadeId, escalaoId) {
                    event.stopPropagation(); // Prevent card click from interfering
                    document.getElementById('atribuir_atleta_id').value = atletaId;
                    document.getElementById('atribuir_modalidade_id').value = modalidadeId;
                    document.getElementById('atribuir_escalao_id').value = escalaoId;
                    document.getElementById('atribuirModal').style.display = 'flex';
                }

                function closeModalAtribuir() {
                    document.getElementById('atribuirModal').style.display = 'none';
                }

                window.onclick = function(event) {
                    if (event.target == document.getElementById('atribuirModal')) {
                        closeModalAtribuir();
                    }
                }
            </script>
        </div>
    </div>

    <?php
    // --- Adicionar este bloco para buscar e mostrar o alerta da sessão ---
    if (isset($_SESSION['alert'])) {
        $alert_message = $_SESSION['alert']['message'];
        $alert_type = $_SESSION['alert']['type'];
        // Usar JS para mostrar o alerta depois que a página carregar
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showAlert(\'" . addslashes($alert_message) . "\', \'" . addslashes($alert_type) . "\'); });</script>";
        unset($_SESSION['alert']); // Limpar a sessão para não mostrar novamente
    }
    // --- Fim do bloco do alerta da sessão ---
    ?>
</body>
</html> 