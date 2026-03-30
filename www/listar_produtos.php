<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = intval($_POST['usuario_id'] ?? 0);

if(!$usuario_id) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido."]);
    exit;
}

try {
    // Busca empresa_id do usuário para compartilhar catálogo com toda a equipe
    $stmtU = $conn->prepare("SELECT empresa_id FROM usuarios WHERE id = ?");
    $stmtU->execute([$usuario_id]);
    $row = $stmtU->fetch(PDO::FETCH_ASSOC);
    $empresa_id = $row ? (int)$row['empresa_id'] : $usuario_id;

    // Busca todos os IDs de membros da empresa
    $stmtM = $conn->prepare("SELECT id FROM usuarios WHERE empresa_id = ?");
    $stmtM->execute([$empresa_id]);
    $ids = array_column($stmtM->fetchAll(PDO::FETCH_ASSOC), 'id');
    if (empty($ids)) $ids = [$usuario_id];

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $conn->prepare(
        "SELECT id, nome, descricao, preco, unidade, categoria, usuario_id
         FROM produtos_servicos
         WHERE usuario_id IN ($placeholders)
         ORDER BY categoria, nome"
    );
    $stmt->execute($ids);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "sucesso", "produtos" => $produtos]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
