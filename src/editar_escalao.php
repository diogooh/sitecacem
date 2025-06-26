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
$modalidade_id = $conn->real_escape_string($_POST['modalidade_id']);
$nome = $conn->real_escape_string($_POST['nome']);
$idade_min = $conn->real_escape_string($_POST['idade_min']);
$idade_max = $conn->real_escape_string($_POST['idade_max']);

$stmt = $conn->prepare("UPDATE escaloes SET modalidade_id = ?, nome = ?, idade_min = ?, idade_max = ? WHERE id = ?");
$stmt->bind_param("isiii", $modalidade_id, $nome, $idade_min, $idade_max, $id);

if ($stmt->execute()) {
    header("Location: admin_modalidades.php?success=1");
} else {
    header("Location: admin_modalidades.php?error=1");
}
?>