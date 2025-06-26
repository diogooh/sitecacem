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

$id = $conn->real_escape_string($_POST['associacao_id']);
$conn->query("DELETE FROM modalidade_treinador WHERE id = $id");

header("Location: admin_dashboard.php");
?>