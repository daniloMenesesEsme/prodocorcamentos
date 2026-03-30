<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    // Verifica se o usuário é dono ou gerente
    $stmt = $conn->prepare("SELECT empresa_id, papel FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !in_array($user['papel'], ['dono','gerente'])) {
        echo json_encode(['status'=>'erro','mensagem'=>'Sem permissão para gerar convites.']);
        exit;
    }

    $empresa_id = $user['empresa_id'];

    // Invalida convites pendentes anteriores gerados por esse usuário
    $stmt = $conn->prepare("UPDATE convites SET status = 'expirado' WHERE criado_por = ? AND status = 'pendente'");
    $stmt->execute([$usuario_id]);

    // Gera código único de 6 dígitos
    do {
        $codigo = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("SELECT id FROM convites WHERE codigo = ?");
        $stmt->execute([$codigo]);
    } while ($stmt->fetch());

    $expira_em = (new DateTime())->modify('+48 hours')->format('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        "INSERT INTO convites (empresa_id, codigo, criado_por, expira_em) VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$empresa_id, $codigo, $usuario_id, $expira_em]);

    echo json_encode([
        'status'    => 'sucesso',
        'codigo'    => $codigo,
        'expira_em' => $expira_em
    ]);
} catch (Exception $e) {
    echo json_encode(['status'=>'erro','mensagem'=>$e->getMessage()]);
}
