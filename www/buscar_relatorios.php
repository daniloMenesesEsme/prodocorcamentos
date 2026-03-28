<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $mes        = intval($_POST['mes'] ?? date('n'));
    $ano        = intval($_POST['ano'] ?? date('Y'));

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }
    if ($mes < 1 || $mes > 12) $mes = date('n');
    if ($ano < 2020 || $ano > 2100) $ano = date('Y');

    // Orçamentos do período
    $stmt = $conn->prepare("
        SELECT id, cliente, itens, total, tipo, criado_em
        FROM orcamentos
        WHERE usuario_id = ? AND MONTH(criado_em) = ? AND YEAR(criado_em) = ?
        ORDER BY criado_em DESC
    ");
    $stmt->execute([$usuario_id, $mes, $ano]);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decodifica JSON dos itens
    foreach ($orcamentos as &$o) {
        $o['itens'] = json_decode($o['itens'], true) ?? [];
    }

    // Totais do período
    $total_orc     = count($orcamentos);
    $receita_total = array_sum(array_column($orcamentos, 'total'));

    // Top clientes do período
    $stmt = $conn->prepare("
        SELECT cliente, COUNT(*) as qtd, COALESCE(SUM(total), 0) as total
        FROM orcamentos
        WHERE usuario_id = ? AND MONTH(criado_em) = ? AND YEAR(criado_em) = ?
        GROUP BY cliente ORDER BY total DESC LIMIT 5
    ");
    $stmt->execute([$usuario_id, $mes, $ano]);
    $top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Por tipo (WhatsApp vs PDF)
    $stmt = $conn->prepare("
        SELECT tipo, COUNT(*) as qtd, COALESCE(SUM(total), 0) as total
        FROM orcamentos
        WHERE usuario_id = ? AND MONTH(criado_em) = ? AND YEAR(criado_em) = ?
        GROUP BY tipo
    ");
    $stmt->execute([$usuario_id, $mes, $ano]);
    $por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Despesas do período
    $despesas_total = 0;
    $despesas_lista = [];
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ? AND MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?");
        $stmt->execute([$usuario_id, $mes, $ano]);
        $despesas_total = (float)$stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT descricao, valor, categoria FROM despesas WHERE usuario_id = ? AND MONTH(data_despesa) = ? AND YEAR(data_despesa) = ? ORDER BY data_despesa DESC");
        $stmt->execute([$usuario_id, $mes, $ano]);
        $despesas_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'status'         => 'sucesso',
        'mes'            => $mes,
        'ano'            => $ano,
        'total_orc'      => $total_orc,
        'receita_total'  => (float)$receita_total,
        'despesas_total' => $despesas_total,
        'saldo'          => $receita_total - $despesas_total,
        'orcamentos'     => $orcamentos,
        'top_clientes'   => $top_clientes,
        'por_tipo'       => $por_tipo,
        'despesas_lista' => $despesas_lista
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
