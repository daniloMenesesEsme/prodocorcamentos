<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    // Produtos com dados de estoque
    $stmt = $conn->prepare("
        SELECT id, nome, categoria, unidade, preco, estoque_atual, estoque_minimo,
               CASE WHEN estoque_minimo > 0 AND estoque_atual <= estoque_minimo THEN 1 ELSE 0 END as alerta
        FROM produtos_servicos
        WHERE usuario_id = ?
        ORDER BY alerta DESC, categoria ASC, nome ASC
    ");
    $stmt->execute([$usuario_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Últimas 30 movimentações
    $stmt = $conn->prepare("
        SELECT m.id, m.tipo, m.quantidade, m.estoque_anterior, m.estoque_resultante, m.observacao, m.criado_em,
               p.nome as produto_nome, p.unidade
        FROM movimentacoes_estoque m
        INNER JOIN produtos_servicos p ON p.id = m.produto_id
        WHERE m.usuario_id = ?
        ORDER BY m.criado_em DESC
        LIMIT 30
    ");
    $stmt->execute([$usuario_id]);
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contagem de alertas
    $alertas = array_filter($produtos, fn($p) => $p['alerta'] == 1);

    echo json_encode([
        'status'        => 'sucesso',
        'produtos'      => $produtos,
        'movimentacoes' => $movimentacoes,
        'qtd_alertas'   => count($alertas)
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
