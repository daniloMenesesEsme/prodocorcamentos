<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id    = intval($_POST['usuario_id'] ?? 0);
    $produto_id    = intval($_POST['produto_id'] ?? 0);
    $estoque_minimo = floatval($_POST['estoque_minimo'] ?? 0);

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'Sessão inválida.']); exit; }
    if (!$produto_id) { echo json_encode(['status'=>'erro','mensagem'=>'Produto inválido.']); exit; }
    if ($estoque_minimo < 0) { echo json_encode(['status'=>'erro','mensagem'=>'Estoque mínimo não pode ser negativo.']); exit; }

    $stmt = $conn->prepare("UPDATE produtos_servicos SET estoque_minimo = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$estoque_minimo, $produto_id, $usuario_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status'=>'erro','mensagem'=>'Produto não encontrado.']);
    } else {
        echo json_encode(['status' => 'sucesso']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
