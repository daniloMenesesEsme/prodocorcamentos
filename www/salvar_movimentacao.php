<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id  = intval($_POST['usuario_id'] ?? 0);
    $produto_id  = intval($_POST['produto_id'] ?? 0);
    $tipo        = trim($_POST['tipo'] ?? ''); // entrada | saida | ajuste
    $quantidade  = floatval($_POST['quantidade'] ?? 0);
    $observacao  = trim($_POST['observacao'] ?? '');

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'Sessão inválida.']); exit; }
    if (!$produto_id) { echo json_encode(['status'=>'erro','mensagem'=>'Produto inválido.']); exit; }
    if (!in_array($tipo, ['entrada','saida','ajuste'])) { echo json_encode(['status'=>'erro','mensagem'=>'Tipo inválido.']); exit; }
    if ($quantidade <= 0) { echo json_encode(['status'=>'erro','mensagem'=>'Quantidade deve ser maior que zero.']); exit; }

    // Verifica se o produto pertence ao usuário
    $stmt = $conn->prepare("SELECT id, nome, estoque_atual FROM produtos_servicos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$produto_id, $usuario_id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) { echo json_encode(['status'=>'erro','mensagem'=>'Produto não encontrado.']); exit; }

    // Calcula novo estoque
    $estoque_atual = floatval($produto['estoque_atual']);
    if ($tipo === 'entrada') {
        $novo_estoque = $estoque_atual + $quantidade;
    } elseif ($tipo === 'saida') {
        if ($quantidade > $estoque_atual) {
            echo json_encode(['status'=>'erro','mensagem'=>'Quantidade de saída maior que o estoque disponível ('.$estoque_atual.').']);
            exit;
        }
        $novo_estoque = $estoque_atual - $quantidade;
    } else { // ajuste
        $novo_estoque = $quantidade; // ajuste define o valor absoluto
    }

    // Atualiza estoque no produto
    $stmt = $conn->prepare("UPDATE produtos_servicos SET estoque_atual = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$novo_estoque, $produto_id, $usuario_id]);

    // Registra movimentação
    $stmt = $conn->prepare("INSERT INTO movimentacoes_estoque (usuario_id, produto_id, tipo, quantidade, estoque_anterior, estoque_resultante, observacao) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$usuario_id, $produto_id, $tipo, $quantidade, $estoque_atual, $novo_estoque, $observacao]);

    echo json_encode(['status' => 'sucesso', 'novo_estoque' => $novo_estoque]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
