<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$id         = $_POST['id']         ?? '';
$usuario_id = $_POST['usuario_id'] ?? '';
$nome       = trim($_POST['nome']       ?? '');
$descricao  = trim($_POST['descricao']  ?? '');
$preco      = $_POST['preco']      ?? 0;
$unidade    = trim($_POST['unidade']    ?? 'un');
$categoria  = $_POST['categoria']  ?? 'servico';

if(!$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "Usuário não identificado."]);
    exit;
}
if(strlen($nome) < 2) {
    echo json_encode(["status" => "erro", "mensagem" => "Nome deve ter pelo menos 2 caracteres."]);
    exit;
}
if(!is_numeric($preco) || $preco < 0) {
    echo json_encode(["status" => "erro", "mensagem" => "Preço inválido."]);
    exit;
}
if(!in_array($categoria, ['servico', 'produto'])) {
    $categoria = 'servico';
}

try {
    if(!empty($id) && is_numeric($id)) {
        // Atualiza — verifica se pertence ao usuário
        $stmt = $conn->prepare(
            "UPDATE produtos_servicos SET nome=:nome, descricao=:desc, preco=:preco,
             unidade=:unidade, categoria=:cat
             WHERE id=:id AND usuario_id=:uid"
        );
        $stmt->execute([
            ':nome'    => $nome,
            ':desc'    => $descricao,
            ':preco'   => $preco,
            ':unidade' => $unidade,
            ':cat'     => $categoria,
            ':id'      => $id,
            ':uid'     => $usuario_id
        ]);
    } else {
        // Insere novo
        $stmt = $conn->prepare(
            "INSERT INTO produtos_servicos (usuario_id, nome, descricao, preco, unidade, categoria)
             VALUES (:uid, :nome, :desc, :preco, :unidade, :cat)"
        );
        $stmt->execute([
            ':uid'     => $usuario_id,
            ':nome'    => $nome,
            ':desc'    => $descricao,
            ':preco'   => $preco,
            ':unidade' => $unidade,
            ':cat'     => $categoria
        ]);
    }

    echo json_encode(["status" => "sucesso"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
