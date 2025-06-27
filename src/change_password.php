<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $user_id = $_SESSION['user_id'];

    // Verificar se as senhas novas coincidem
    if ($new_password !== $confirm_password) {
        header("Location: perfil_atleta.php?error=" . urlencode("As palavras-passe não coincidem"));
        exit();
    }

    // Verificar se a nova senha tem pelo menos 8 caracteres
    if (strlen($new_password) < 8) {
        header("Location: perfil_atleta.php?error=" . urlencode("A nova palavra-passe deve ter pelo menos 8 caracteres"));
        exit();
    }

    // Buscar a senha atual do usuário
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    if (!$stmt) {
        header("Location: perfil_atleta.php?error=" . urlencode("Erro na preparação da consulta: " . $conn->error));
        exit();
    }

    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        header("Location: perfil_atleta.php?error=" . urlencode("Erro ao executar a consulta: " . $stmt->error));
        exit();
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        header("Location: perfil_atleta.php?error=" . urlencode("Utilizador não encontrado"));
        exit();
    }

    // Verificar se a senha atual está correta
    if (!password_verify($current_password, $user['password_hash'])) {
        header("Location: perfil_atleta.php?error=" . urlencode("Palavra-passe atual incorreta"));
        exit();
    }

    // Atualizar a senha
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    if (!$stmt) {
        header("Location: perfil_atleta.php?error=" . urlencode("Erro na preparação da atualização: " . $conn->error));
        exit();
    }

    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt->bind_param("si", $hashed_password, $user_id);

    if (!$stmt->execute()) {
        header("Location: perfil_atleta.php?error=" . urlencode("Erro ao atualizar a palavra-passe: " . $stmt->error));
        exit();
    }

    $stmt->close();
    header("Location: perfil_atleta.php?success=palavra_passe_atualizada");
    exit();
}

// Se chegou aqui, redireciona para a página de perfil
header("Location: perfil_atleta.php");
exit();
?> 