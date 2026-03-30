<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id   = intval($_POST['usuario_id']   ?? 0);
    $orcamento_id = intval($_POST['orcamento_id'] ?? 0);
    $status       = trim($_POST['status']         ?? '');

    $permitidos = ['enviado', 'aprovado', 'recusado', 'expirado'];
    if (!$usuario_id || !$orcamento_id || !in_array($status, $permitidos)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE orcamentos SET status = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$status, $orcamento_id, $usuario_id]);

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
