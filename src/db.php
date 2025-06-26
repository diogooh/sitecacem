<?php
$conn = new mysqli("localhost", "root", "", "sitecacem");
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>