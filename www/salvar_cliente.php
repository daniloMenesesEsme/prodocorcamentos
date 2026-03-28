<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = 'localhost'; $db = 'prodocorcamentos'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $id         = intval($_POST['id'] ?? 0);
    $nome       = trim($_POST['nome'] ?? '');
    $whatsapp   = trim($_POST['whatsapp'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $cpf_cnpj   = trim($_POST['cpf_cnpj'] ?? '');
    $cidade     = trim($_POST['cidade'] ?? '');
    $observacoes = trim($_POST['observacoes'] ?? '');

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'Sessão inválida.']); exit; }
    if (strlen($nome) < 2) { echo json_encode(['status'=>'erro','mensagem'=>'Nome deve ter pelo menos 2 caracteres.']); exit; }

    if ($id > 0) {
        // Atualizar — verifica propriedade
        $stmt = $pdo->prepare("UPDATE clientes SET nome=?, whatsapp=?, email=?, cpf_cnpj=?, cidade=?, observacoes=? WHERE id=? AND usuario_id=?");
        $stmt->execute([$nome, $whatsapp, $email, $cpf_cnpj, $cidade, $observacoes, $id, $usuario_id]);
    } else {
        // Inserir
        $stmt = $pdo->prepare("INSERT INTO clientes (usuario_id, nome, whatsapp, email, cpf_cnpj, cidade, observacoes) VALUES (?,?,?,?,?,?,?)");
        $stmt->execute([$usuario_id, $nome, $whatsapp, $email, $cpf_cnpj, $cidade, $observacoes]);
    }

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
