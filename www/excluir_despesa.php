<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $id         = intval($_POST['id'] ?? 0);

    if (!$usuario_id || !$id) { echo json_encode(['status'=>'erro','mensagem'=>'Dados inválidos.']); exit; }

    $stmt = $conn->prepare("DELETE FROM despesas WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Registro não encontrado.']);
    } else {
        echo json_encode(['status' => 'sucesso']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
