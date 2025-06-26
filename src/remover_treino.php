<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['tipo'] != 'treinador' && $_SESSION['tipo'] != 'dirigente')) {
    header("Location: login.php");
    exit();
}

require 'db.php';

if (!isset($_GET['id'])) {
    $_SESSION['erro'] = "ID do treino não fornecido.";
    header("Location: gerir_treinos.php");
    exit();
}

$id = $_GET['id'];

// Verificar se o treino existe
$stmt = $conn->prepare("SELECT id FROM treinos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['erro'] = "Treino não encontrado.";
    header("Location: gerir_treinos.php");
    exit();
}

// Remover o treino
$stmt = $conn->prepare("DELETE FROM treinos WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['sucesso'] = "Treino removido com sucesso!";
} else {
    $_SESSION['erro'] = "Erro ao remover treino: " . $conn->error;
}

header("Location: gerir_treinos.php");
exit();
?> 