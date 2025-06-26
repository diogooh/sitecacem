<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o utilizador está autenticado e é staff
if (!isset($_SESSION['user_id']) || !isset($_SESSION['tipo']) || ($_SESSION['tipo'] !== 'treinador' && $_SESSION['tipo'] !== 'dirigente')) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

require_once 'db.php';

header('Content-Type: application/json');

$jogo_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$jogo_id) {
    echo json_encode(['error' => 'ID do jogo não fornecido']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM jogos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $jogo_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$jogo = $result->fetch_assoc();
$stmt->close();

if ($jogo) {
    echo json_encode($jogo);
} else {
    echo json_encode(['error' => 'Jogo não encontrado ou sem permissão']);
}

$conn->close();
?> 