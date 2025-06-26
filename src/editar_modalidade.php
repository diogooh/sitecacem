<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['tipo'] != 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit();
}

$id = $conn->real_escape_string($_POST['id']);
$nome = $conn->real_escape_string($_POST['nome']);
$descricao = $conn->real_escape_string($_POST['descricao']);

$stmt = $conn->prepare("UPDATE modalidades SET nome = ?, descricao = ? WHERE id = ?");
$stmt->bind_param("ssi", $nome, $descricao, $id);

if ($stmt->execute()) {
    header("Location: admin_modalidades.php?success=1");
} else {
    header("Location: admin_modalidades.php?error=1");
}
?>