<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = $_POST['usuario_id'] ?? '';
$empresa    = trim($_POST['empresa'] ?? '');
$zap        = preg_replace('/\D/', '', $_POST['zap'] ?? '');

if(!$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "Usuário não identificado no sistema."]);
    exit;
}
if(empty($empresa)) {
    echo json_encode(["status" => "erro", "mensagem" => "O nome da empresa não pode estar vazio."]);
    exit;
}
if(!empty($zap) && !preg_match('/^\d{10,11}$/', $zap)) {
    echo json_encode(["status" => "erro", "mensagem" => "WhatsApp inválido. Use DDD + número (10 ou 11 dígitos)."]);
    exit;
}

try {
    // 1. Primeiro, verificamos se esse usuário já tem alguma configuração salva
    $check = $conn->prepare("SELECT id FROM configuracoes WHERE usuario_id = :uid");
    $check->execute([':uid' => $usuario_id]);
    
    if ($check->rowCount() > 0) {
        // 2. Se já existe, nós apenas ATUALIZAMOS (Update)
        $sql = "UPDATE configuracoes SET nome_empresa = :nome, whatsapp_empresa = :zap WHERE usuario_id = :uid";
    } else {
        // 3. Se é a primeira vez, nós INSERIMOS (Insert)
        $sql = "INSERT INTO configuracoes (usuario_id, nome_empresa, whatsapp_empresa) VALUES (:uid, :nome, :zap)";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':uid'  => $usuario_id,
        ':nome' => $empresa,
        ':zap'  => $zap
    ]);

    echo json_encode(["status" => "sucesso"]);

} catch(PDOException $e) {
    // Se der qualquer erro no MySQL, ele avisa aqui
    echo json_encode(["status" => "erro", "mensagem" => "Erro no banco: " . $e->getMessage()]);
}
?>