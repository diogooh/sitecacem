<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'admin') {
    header('Location: login.php');
    exit();
}

require 'db.php';

// Permitir tanto POST como GET
$user_id = 0;
if (isset($_POST['id'])) {
    $user_id = intval($_POST['id']);
} elseif (isset($_GET['id'])) {
    $user_id = intval($_GET['id']);
}

if ($user_id > 0) {
    // Não permitir apagar o próprio admin logado
    if ($user_id == $_SESSION['user_id']) {
        $msg = urlencode('Não pode apagar o seu próprio utilizador!');
        header("Location: admin_dashboard.php?msg=$msg&type=error");
        exit();
    }
    
    // Apagar utilizador
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    if ($stmt->execute()) {
        $msg = urlencode('Utilizador apagado com sucesso!');
        $type = 'success';
    } else {
        $msg = urlencode('Erro ao apagar utilizador.');
        $type = 'error';
    }
    $stmt->close();
} else {
    $msg = urlencode('ID de utilizador inválido.');
    $type = 'error';
}
$conn->close();
header("Location: admin_dashboard.php?msg=$msg&type=$type");
exit(); 