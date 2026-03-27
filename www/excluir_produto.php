<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$id         = $_POST['id']         ?? '';
$usuario_id = $_POST['usuario_id'] ?? '';

if(!$id || !is_numeric($id) || !$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
    exit;
}

try {
    // Verifica se o produto pertence ao usuário antes de excluir
    $stmt = $conn->prepare(
        "DELETE FROM produtos_servicos WHERE id = :id AND usuario_id = :uid"
    );
    $stmt->execute([':id' => $id, ':uid' => $usuario_id]);

    if($stmt->rowCount() > 0) {
        echo json_encode(["status" => "sucesso"]);
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "Item não encontrado."]);
    }

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
