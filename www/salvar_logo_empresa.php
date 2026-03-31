<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = intval($_POST['usuario_id'] ?? 0);
$logo       = trim($_POST['logo'] ?? '');

if (!$usuario_id) {
    echo json_encode(['status'=>'erro','mensagem'=>'ID inválido.']); exit;
}

// Limite ~700KB base64 ≈ 500KB imagem real
if (strlen($logo) > 700000) {
    echo json_encode(['status'=>'erro','mensagem'=>'Imagem muito grande. Reduza o tamanho e tente novamente.']); exit;
}

// Valida formato se não for vazio
if ($logo && !preg_match('/^data:image\/(jpeg|jpg|png|gif|webp);base64,/', $logo)) {
    echo json_encode(['status'=>'erro','mensagem'=>'Formato de imagem inválido.']); exit;
}

try {
    // Verifica se já existe registro na tabela empresas
    $check = $conn->prepare("SELECT id FROM empresas WHERE usuario_id = ?");
    $check->execute([$usuario_id]);

    if ($check->rowCount() > 0) {
        $conn->prepare("UPDATE empresas SET logo_empresa = ? WHERE usuario_id = ?")
             ->execute([$logo ?: null, $usuario_id]);
    } else {
        $conn->prepare("INSERT INTO empresas (usuario_id, nome_fantasia, logo_empresa) VALUES (?, '', ?)")
             ->execute([$usuario_id, $logo ?: null]);
    }

    echo json_encode(['status'=>'sucesso']);
} catch (Exception $e) {
    echo json_encode(['status'=>'erro','mensagem'=>$e->getMessage()]);
}
