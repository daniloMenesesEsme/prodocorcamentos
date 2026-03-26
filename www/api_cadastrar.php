<?php
header('Content-Type: application/json');
require_once 'config.php';

$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$senha = password_hash($_POST['senha'] ?? '', PASSWORD_DEFAULT);
$zap = $_POST['whatsapp'] ?? '';

// Calcula 7 dias grátis
$data_expira = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

try {
    $sql = "INSERT INTO usuarios (nome_completo, email, senha, whatsapp, data_expiracao) 
            VALUES (:nome, :email, :senha, :zap, :expira)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senha,
        ':zap' => $zap,
        ':expira' => $data_expira
    ]);

    echo json_encode(["status" => "sucesso"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => "E-mail já cadastrado!"]);
}
?>