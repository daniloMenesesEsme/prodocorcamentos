<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = $_POST['usuario_id'] ?? '';

if(!$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido."]);
    exit;
}

try {
    $stmt = $conn->prepare(
        "SELECT id, nome, descricao, preco, unidade, categoria
         FROM produtos_servicos
         WHERE usuario_id = :uid
         ORDER BY categoria, nome"
    );
    $stmt->execute([':uid' => $usuario_id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => "sucesso", "produtos" => $produtos]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
