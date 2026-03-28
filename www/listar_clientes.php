<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    $stmt = $conn->prepare("SELECT * FROM clientes WHERE usuario_id = ? ORDER BY nome ASC");
    $stmt->execute([$usuario_id]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'sucesso', 'clientes' => $clientes]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
