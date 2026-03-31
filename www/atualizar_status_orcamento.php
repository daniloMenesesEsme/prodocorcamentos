<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

// Feature flag: desligar quando NFe/NFC-e for implementada
$baixa_estoque_automatica = true;

try {
    $usuario_id   = intval($_POST['usuario_id']   ?? 0);
    $orcamento_id = intval($_POST['orcamento_id'] ?? 0);
    $status       = trim($_POST['status']         ?? '');

    $permitidos = ['enviado', 'aprovado', 'recusado', 'expirado'];
    if (!$usuario_id || !$orcamento_id || !in_array($status, $permitidos)) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Dados inválidos.']);
        exit;
    }

    // Busca status atual para saber se está mudando PARA aprovado
    $stmtAtual = $conn->prepare("SELECT status, itens FROM orcamentos WHERE id = ? AND usuario_id = ?");
    $stmtAtual->execute([$orcamento_id, $usuario_id]);
    $orcamento = $stmtAtual->fetch(PDO::FETCH_ASSOC);

    if (!$orcamento) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Orçamento não encontrado.']);
        exit;
    }

    // Atualiza status
    $stmt = $conn->prepare("UPDATE orcamentos SET status = ? WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$status, $orcamento_id, $usuario_id]);

    // Baixa automática de estoque ao aprovar (somente se ainda não estava aprovado)
    $itens_baixados = 0;
    if ($baixa_estoque_automatica && $status === 'aprovado' && $orcamento['status'] !== 'aprovado') {
        $itens = json_decode($orcamento['itens'], true) ?? [];
        foreach ($itens as $item) {
            $produto_id = intval($item['produto_id'] ?? 0);
            $categoria  = $item['categoria'] ?? '';
            $qtd        = floatval($item['qtd'] ?? 1);

            if ($produto_id > 0 && $categoria === 'produto' && $qtd > 0) {
                // Deduz do estoque e registra movimentação
                $conn->prepare(
                    "UPDATE produtos_servicos SET estoque_atual = GREATEST(0, estoque_atual - ?) WHERE id = ?"
                )->execute([$qtd, $produto_id]);

                $conn->prepare(
                    "INSERT INTO movimentacoes_estoque (produto_id, usuario_id, tipo, quantidade, observacao)
                     VALUES (?, ?, 'saida', ?, ?)"
                )->execute([$produto_id, $usuario_id, $qtd, "Saída automática — Orçamento #{$orcamento_id} aprovado"]);

                $itens_baixados++;
            }
        }
    }

    echo json_encode([
        'status'         => 'sucesso',
        'itens_baixados' => $itens_baixados
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
