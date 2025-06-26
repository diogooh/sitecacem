<?php
session_start();
require 'db.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('ID inválido.');
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare('SELECT * FROM documentos_medicos WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
    http_response_code(404);
    exit('Documento não encontrado.');
}

// Só o admin ou o próprio atleta pode baixar
if ($_SESSION['tipo'] !== 'admin' && $_SESSION['user_id'] != $doc['atleta_id']) {
    http_response_code(403);
    exit('Acesso negado.');
}

$caminho = __DIR__ . '/../uploads/medicos/' . $doc['nome_arquivo'];
if (!file_exists($caminho)) {
    http_response_code(404);
    exit('Ficheiro não encontrado.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['nome_arquivo']) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($caminho));
readfile($caminho);
exit(); 