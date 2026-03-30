<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $codigo     = trim($_POST['codigo']      ?? '');

    if (!$usuario_id || !$codigo) {
        echo json_encode(['status'=>'erro','mensagem'=>'Dados inválidos.']); exit;
    }

    // Busca convite válido
    $stmt = $conn->prepare(
        "SELECT id, empresa_id FROM convites
         WHERE codigo = ? AND status = 'pendente' AND expira_em > NOW()"
    );
    $stmt->execute([$codigo]);
    $convite = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$convite) {
        echo json_encode(['status'=>'erro','mensagem'=>'Código inválido ou expirado.']); exit;
    }

    // Verifica se o usuário já pertence a uma empresa com outros membros
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM usuarios WHERE empresa_id = (SELECT empresa_id FROM usuarios WHERE id = ?) AND id != ?"
    );
    $stmt->execute([$usuario_id, $usuario_id]);
    $membros = (int)$stmt->fetchColumn();

    if ($membros > 0) {
        echo json_encode(['status'=>'erro','mensagem'=>'Você já pertence a uma empresa.']); exit;
    }

    // Vincula o usuário à empresa do convite como colaborador
    $stmt = $conn->prepare(
        "UPDATE usuarios SET empresa_id = ?, papel = 'colaborador' WHERE id = ?"
    );
    $stmt->execute([$convite['empresa_id'], $usuario_id]);

    // Marca convite como usado
    $stmt = $conn->prepare(
        "UPDATE convites SET status = 'usado', usado_por = ? WHERE id = ?"
    );
    $stmt->execute([$usuario_id, $convite['id']]);

    echo json_encode([
        'status'     => 'sucesso',
        'empresa_id' => $convite['empresa_id'],
        'papel'      => 'colaborador'
    ]);
} catch (Exception $e) {
    echo json_encode(['status'=>'erro','mensagem'=>$e->getMessage()]);
}
