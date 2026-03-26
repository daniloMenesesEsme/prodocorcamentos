<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

// Pegando os dados que o JavaScript vai enviar
$usuario_id = $_POST['usuario_id'] ?? '';
$empresa = $_POST['empresa'] ?? '';
$zap = $_POST['zap'] ?? '';

if (!$usuario_id) {
    echo json_encode(["status" => "erro", "mensagem" => "Usuário não identificado no sistema."]);
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