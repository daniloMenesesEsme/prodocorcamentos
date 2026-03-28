<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id  = intval($_POST['usuario_id'] ?? 0);
    $id          = intval($_POST['id'] ?? 0);
    $nome        = trim($_POST['nome'] ?? '');
    $whatsapp    = trim($_POST['whatsapp'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $cpf_cnpj    = trim($_POST['cpf_cnpj'] ?? '');
    $cep         = trim($_POST['cep'] ?? '');
    $endereco    = trim($_POST['endereco'] ?? '');
    $numero      = trim($_POST['numero'] ?? '');
    $cidade      = trim($_POST['cidade'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'Sessão inválida.']); exit; }
    if (strlen($nome) < 2) { echo json_encode(['status'=>'erro','mensagem'=>'Nome deve ter pelo menos 2 caracteres.']); exit; }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE clientes SET nome=?, whatsapp=?, email=?, cpf_cnpj=?, cep=?, endereco=?, numero=?, cidade=?, observacoes=? WHERE id=? AND usuario_id=?");
        $stmt->execute([$nome, $whatsapp, $email, $cpf_cnpj, $cep, $endereco, $numero, $cidade, $observacoes, $id, $usuario_id]);
        echo json_encode(['status' => 'sucesso', 'id' => $id]);
    } else {
        $stmt = $conn->prepare("INSERT INTO clientes (usuario_id, nome, whatsapp, email, cpf_cnpj, cep, endereco, numero, cidade, observacoes) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$usuario_id, $nome, $whatsapp, $email, $cpf_cnpj, $cep, $endereco, $numero, $cidade, $observacoes]);
        echo json_encode(['status' => 'sucesso', 'id' => (int)$conn->lastInsertId()]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
