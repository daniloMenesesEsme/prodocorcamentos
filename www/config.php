<?php
// Configurações do seu banco de dados local (Laragon)
$host = "localhost";
$db_name = "db_prodoc";
$username = "root"; // Padrão do Laragon
$password = "";     // Padrão do Laragon (vazio)

try {
    // Tentando conectar usando o PDO (é o jeito mais seguro e moderno)
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    
    // Configura para mostrar erros caso algo dê errado
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Se chegar aqui, a conexão foi um sucesso!
} catch(PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>