<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$nome  = trim($_POST['nome']      ?? '');
$email = trim($_POST['email']     ?? '');
$senha =      $_POST['senha']     ?? '';
$zap   = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');

// Validações
if(strlen($nome) < 3) {
    echo json_encode(["status" => "erro", "mensagem" => "Nome deve ter pelo menos 3 caracteres."]);
    exit;
}
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "erro", "mensagem" => "E-mail inválido."]);
    exit;
}
if(strlen($senha) < 6) {
    echo json_encode(["status" => "erro", "mensagem" => "A senha deve ter pelo menos 6 caracteres."]);
    exit;
}
if(!empty($zap) && !preg_match('/^\d{10,11}$/', $zap)) {
    echo json_encode(["status" => "erro", "mensagem" => "WhatsApp inválido. Use DDD + número (10 ou 11 dígitos)."]);
    exit;
}

$senha_hash  = password_hash($senha, PASSWORD_DEFAULT);
$data_expira = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

try {
    $sql = "INSERT INTO usuarios (nome_completo, email, senha, whatsapp, data_expiracao)
            VALUES (:nome, :email, :senha, :zap, :expira)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $senha_hash,
        ':zap' => $zap,
        ':expira' => $data_expira
    ]);

    // Novo usuário vira dono da própria empresa (empresa_id = seu próprio id)
    $novo_id = (int)$conn->lastInsertId();
    $conn->prepare("UPDATE usuarios SET empresa_id = ? WHERE id = ?")->execute([$novo_id, $novo_id]);

    echo json_encode(["status" => "sucesso"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => "E-mail já cadastrado!"]);
}
?>