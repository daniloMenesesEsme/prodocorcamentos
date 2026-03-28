<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    $stmt = $conn->prepare("SELECT COALESCE(SUM(total), 0) as receita FROM orcamentos WHERE usuario_id = ? AND MONTH(criado_em) = MONTH(NOW()) AND YEAR(criado_em) = YEAR(NOW())");
    $stmt->execute([$usuario_id]);
    $receita_mes = (float)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ? AND MONTH(data_despesa) = MONTH(NOW()) AND YEAR(data_despesa) = YEAR(NOW())");
    $stmt->execute([$usuario_id]);
    $despesas_mes = (float)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT * FROM despesas WHERE usuario_id = ? ORDER BY data_despesa DESC, criado_em DESC LIMIT 50");
    $stmt->execute([$usuario_id]);
    $despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'       => 'sucesso',
        'receita_mes'  => $receita_mes,
        'despesas_mes' => $despesas_mes,
        'saldo_mes'    => $receita_mes - $despesas_mes,
        'despesas'     => $despesas
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
