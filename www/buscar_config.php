<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = $_POST['usuario_id'] ?? '';

if(!$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido."]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT nome_empresa, whatsapp_empresa FROM configuracoes WHERE usuario_id = :uid");
    $stmt->execute([':uid' => $usuario_id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if($config) {
        echo json_encode([
            "status" => "sucesso",
            "empresa" => $config['nome_empresa'],
            "zap"     => $config['whatsapp_empresa']
        ]);
    } else {
        echo json_encode(["status" => "vazio"]);
    }

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
