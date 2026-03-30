<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = $_POST['usuario_id'] ?? '';
$cliente    = $_POST['cliente']    ?? '';
$itens      = $_POST['itens']      ?? '[]';
$total      = $_POST['total']      ?? 0;
$tipo       = $_POST['tipo']       ?? 'whatsapp';
$obs        = $_POST['obs']        ?? '';

if(!$usuario_id || !is_numeric($usuario_id) || !$cliente) {
    echo json_encode(["status" => "erro", "mensagem" => "Dados inválidos."]);
    exit;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO orcamentos (usuario_id, cliente, itens, total, tipo, obs, status)
         VALUES (:uid, :cliente, :itens, :total, :tipo, :obs, 'enviado')"
    );
    $stmt->execute([
        ':uid'     => $usuario_id,
        ':cliente' => $cliente,
        ':itens'   => $itens,
        ':total'   => $total,
        ':tipo'    => $tipo,
        ':obs'     => $obs
    ]);

    echo json_encode(["status" => "sucesso", "id" => (int)$conn->lastInsertId()]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
