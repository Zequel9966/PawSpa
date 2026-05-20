<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'pawspa_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("❌ Error conexión BD: " . $conn->connect_error);
} else {
    echo "✅ Conexión a BD exitosa!";
}
$conn->close();
?>