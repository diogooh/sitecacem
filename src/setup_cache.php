<?php
$cacheDir = __DIR__ . '/../cache';

if (!file_exists($cacheDir)) {
    if (mkdir($cacheDir, 0777, true)) {
        echo "Pasta cache criada com sucesso!\n";
    } else {
        echo "Erro ao criar pasta cache!\n";
    }
} else {
    echo "Pasta cache já existe!\n";
}

// Verificar permissões
if (is_writable($cacheDir)) {
    echo "Pasta cache tem permissões de escrita!\n";
} else {
    echo "Aviso: Pasta cache não tem permissões de escrita!\n";
    chmod($cacheDir, 0777);
    echo "Tentando ajustar permissões...\n";
    if (is_writable($cacheDir)) {
        echo "Permissões ajustadas com sucesso!\n";
    } else {
        echo "Não foi possível ajustar as permissões!\n";
    }
} 