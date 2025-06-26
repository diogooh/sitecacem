<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $modalidade_id = $_POST['modalidade_id'];
    $data = $_POST['data'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fim = $_POST['hora_fim'];
    $local = $_POST['local'];
    $treinador = $_POST['treinador'];
    $descricao = $_POST['descricao'];

    // Upload do anexo
    $anexo_nome = null;
    if (isset($_FILES['anexo']) && $_FILES['anexo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['anexo']['name'], PATHINFO_EXTENSION);
        $anexo_nome = uniqid('anexo_') . '.' . $ext;
        $destino = __DIR__ . '/uploads/' . $anexo_nome;
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0777, true);
        }
        if (!move_uploaded_file($_FILES['anexo']['tmp_name'], $destino)) {
            $_SESSION['erro'] = "Erro ao fazer upload do anexo.";
            header("Location: gerir_treinos.php");
            exit();
        }
    }

    // Validar dados
    if (empty($modalidade_id) || empty($data) || empty($hora_inicio) || empty($hora_fim) || empty($local) || empty($treinador)) {
        $_SESSION['erro'] = "Todos os campos obrigatórios devem ser preenchidos.";
        header("Location: gerir_treinos.php");
        exit();
    }

    // Verificar se é edição ou novo treino
    if (isset($_POST['id'])) {
        // Edição
        $id = $_POST['id'];
        // Buscar anexo antigo se não for enviado novo
        if (!$anexo_nome) {
            $res = $conn->query("SELECT anexo FROM treinos WHERE id = " . (int)$id);
            $row = $res ? $res->fetch_assoc() : null;
            $anexo_nome = $row ? $row['anexo'] : null;
        }
        $stmt = $conn->prepare("UPDATE treinos SET modalidade_id = ?, data = ?, hora_inicio = ?, hora_fim = ?, local = ?, treinador = ?, descricao = ?, anexo = ? WHERE id = ?");
        $stmt->bind_param("isssssssi", $modalidade_id, $data, $hora_inicio, $hora_fim, $local, $treinador, $descricao, $anexo_nome, $id);
    } else {
        // Novo treino
        $stmt = $conn->prepare("INSERT INTO treinos (modalidade_id, data, hora_inicio, hora_fim, local, treinador, descricao, anexo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $modalidade_id, $data, $hora_inicio, $hora_fim, $local, $treinador, $descricao, $anexo_nome);
    }

    if ($stmt->execute()) {
        $_SESSION['sucesso'] = "Treino " . (isset($_POST['id']) ? "atualizado" : "adicionado") . " com sucesso!";
    } else {
        $_SESSION['erro'] = "Erro ao " . (isset($_POST['id']) ? "atualizar" : "adicionar") . " treino: " . $conn->error;
    }

    header("Location: gerir_treinos.php");
    exit();
} else {
    header("Location: gerir_treinos.php");
    exit();
}
?> 