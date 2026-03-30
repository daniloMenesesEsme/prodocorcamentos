<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $empresa_id = intval($_POST['empresa_id'] ?? 0);

    if (!$usuario_id || !$empresa_id) {
        echo json_encode(['status'=>'erro','mensagem'=>'Dados inválidos.']); exit;
    }

    // Verifica se o solicitante é dono ou gerente da empresa
    $stmt = $conn->prepare("SELECT papel FROM usuarios WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$usuario_id, $empresa_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !in_array($user['papel'], ['dono','gerente'])) {
        echo json_encode(['status'=>'erro','mensagem'=>'Sem permissão.']); exit;
    }

    $stmt = $conn->prepare(
        "SELECT id, nome_completo, papel FROM usuarios
         WHERE empresa_id = ? ORDER BY papel ASC, nome_completo ASC"
    );
    $stmt->execute([$empresa_id]);
    $membros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'sucesso', 'membros' => $membros]);
} catch (Exception $e) {
    echo json_encode(['status'=>'erro','mensagem'=>$e->getMessage()]);
}
