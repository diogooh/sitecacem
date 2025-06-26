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

$user_id = $_SESSION['user_id'];

// Processar exclusão de jogo
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $jogo_id = $_GET['id'];
    
    // Verificar se o jogo pertence ao utilizador (staff)
    $stmt = $conn->prepare("SELECT id FROM jogos WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $jogo_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        // Excluir o jogo
        $stmt_delete = $conn->prepare("DELETE FROM jogos WHERE id = ?");
        $stmt_delete->bind_param("i", $jogo_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        $_SESSION['success_message'] = "Jogo excluído com sucesso!";
    } else {
        $_SESSION['error_message'] = "Jogo não encontrado ou você não tem permissão para excluí-lo.";
    }
    $stmt->close();
    
    header('Location: gerir_jogos.php');
    exit();
}

// Processar criação/edição de jogo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jogo_id = $_POST['jogo_id'] ?? null;
    $titulo = $_POST['titulo'];
    $escalao_id = $_POST['escalao_id'];
    $data_jogo = $_POST['data_jogo'];
    $local = $_POST['local'];
    $adversario = $_POST['adversario'];
    $pontuacao_acc = $_POST['pontuacao_acc'] ?? null;
    $pontuacao_adversario = $_POST['pontuacao_adversario'] ?? null;
    $descricao = $_POST['descricao'] ?? '';
    
    // Validar dados
    if (empty($titulo) || empty($escalao_id) || empty($data_jogo) || empty($local) || empty($adversario)) {
        $_SESSION['error_message'] = "Todos os campos obrigatórios devem ser preenchidos.";
        header('Location: gerir_jogos.php');
        exit();
    }
    
    // Verificar se o escalão pertence ao utilizador (staff)
    $stmt = $conn->prepare("SELECT id FROM escaloes WHERE id = ? AND staff_id = ?");
    $stmt->bind_param("ii", $escalao_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        $_SESSION['error_message'] = "Escalão inválido ou você não tem permissão para usá-lo.";
        $stmt->close();
        header('Location: gerir_jogos.php');
        exit();
    }
    $stmt->close();
    
    try {
        if ($jogo_id) {
            // Editar jogo existente
            $stmt = $conn->prepare("
                UPDATE jogos 
                SET titulo = ?, escalao_id = ?, data_jogo = ?, local = ?, adversario = ?, descricao = ?, pontuacao_acc = ?, pontuacao_adversario = ?
                WHERE id = ? AND user_id = ?
            ");

            if (!$stmt) {
                error_log("Erro ao preparar UPDATE: " . $conn->error);
                $_SESSION['error_message'] = "Erro interno ao preparar a atualização do jogo. Por favor, tente novamente.";
                header('Location: gerir_jogos.php');
                exit();
            }

            // Certifique-se que os IDs e pontuações são inteiros
            $escalao_id = (int)$escalao_id;
            $pontuacao_acc = $pontuacao_acc !== null ? (int)$pontuacao_acc : null;
            $pontuacao_adversario = $pontuacao_adversario !== null ? (int)$pontuacao_adversario : null;
            $jogo_id = (int)$jogo_id;
            $user_id = (int)$user_id;

            // Corrected bind_param for UPDATE (s: string, i: integer)
            $stmt->bind_param("sissssiiii", $titulo, $escalao_id, $data_jogo, $local, $adversario, $descricao, $pontuacao_acc, $pontuacao_adversario, $jogo_id, $user_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $_SESSION['success_message'] = "Jogo atualizado com sucesso!";
            } else {
                $_SESSION['error_message'] = "Jogo não encontrado ou você não tem permissão para editá-lo.";
            }
            $stmt->close();
        } else {
            // Criar novo jogo
            $stmt = $conn->prepare("
                INSERT INTO jogos (titulo, escalao_id, data_jogo, local, adversario, descricao, pontuacao_acc, pontuacao_adversario, user_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado')
            ");

            if (!$stmt) {
                error_log("Erro ao preparar INSERT: " . $conn->error);
                $_SESSION['error_message'] = "Erro interno ao preparar o jogo. Por favor, tente novamente.";
                header('Location: gerir_jogos.php');
                exit();
            }
            
            // Certifique-se que o escalao_id e pontuações são inteiros
            $escalao_id = (int)$escalao_id;
            $pontuacao_acc = $pontuacao_acc !== null ? (int)$pontuacao_acc : null;
            $pontuacao_adversario = $pontuacao_adversario !== null ? (int)$pontuacao_adversario : null;
            $user_id = (int)$user_id;

            // Debugging: Log the variables before bind_param
            error_log("Debug: Variables for INSERT bind_param: " . var_export([$titulo, $escalao_id, $data_jogo, $local, $adversario, $descricao, $pontuacao_acc, $pontuacao_adversario, $user_id], true));

            $stmt->bind_param("sissssiii", $titulo, $escalao_id, $data_jogo, $local, $adversario, $descricao, $pontuacao_acc, $pontuacao_adversario, $user_id);
            $stmt->execute();
            $stmt->close();
            
            $_SESSION['success_message'] = "Jogo criado com sucesso!";
        }
    } catch (mysqli_sql_exception $e) {
        $_SESSION['error_message'] = "Erro ao processar o jogo: " . $e->getMessage();
        error_log("Erro ao processar jogo: " . $e->getMessage());
    }
    
    header('Location: gerir_jogos.php');
    exit();
}

// Se chegou aqui, redirecionar para a página de jogos
header('Location: gerir_jogos.php');
exit(); 