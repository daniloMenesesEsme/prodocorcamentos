<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id  = intval($_POST['usuario_id'] ?? 0);
    $orcamento_id = intval($_POST['orcamento_id'] ?? 0);
    $cliente     = trim($_POST['cliente'] ?? '');
    $itens_json  = trim($_POST['itens'] ?? '[]');
    $total       = floatval($_POST['total'] ?? 0);
    $motivo      = trim($_POST['motivo'] ?? '');
    $editor_nome = trim($_POST['editor_nome'] ?? 'Usuário');

    if (!$usuario_id)   { echo json_encode(['status'=>'erro','mensagem'=>'Sessão inválida.']); exit; }
    if (!$orcamento_id) { echo json_encode(['status'=>'erro','mensagem'=>'Orçamento inválido.']); exit; }
    if (strlen($cliente) < 2) { echo json_encode(['status'=>'erro','mensagem'=>'Nome do cliente obrigatório.']); exit; }
    if (strlen($motivo) < 5)  { echo json_encode(['status'=>'erro','mensagem'=>'Informe o motivo da edição (mínimo 5 caracteres).']); exit; }

    // Verifica propriedade
    $stmt = $conn->prepare("SELECT id, historico_edicoes FROM orcamentos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$orcamento_id, $usuario_id]);
    $orc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$orc) { echo json_encode(['status'=>'erro','mensagem'=>'Orçamento não encontrado.']); exit; }

    // Monta registro de auditoria
    $historico = json_decode($orc['historico_edicoes'] ?? '[]', true) ?: [];
    $historico[] = [
        'por'      => $editor_nome,
        'em'       => date('d/m/Y H:i'),
        'motivo'   => $motivo
    ];

    // Atualiza orçamento
    $stmt = $conn->prepare("
        UPDATE orcamentos
        SET cliente = ?, itens = ?, total = ?,
            editado_em = NOW(), editado_por = ?, historico_edicoes = ?
        WHERE id = ? AND usuario_id = ?
    ");
    $stmt->execute([
        $cliente,
        $itens_json,
        $total,
        $editor_nome,
        json_encode($historico, JSON_UNESCAPED_UNICODE),
        $orcamento_id,
        $usuario_id
    ]);

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
