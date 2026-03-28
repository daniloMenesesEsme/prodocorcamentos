<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = 'localhost'; $db = 'prodocorcamentos'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $id         = intval($_POST['id'] ?? 0);

    if (!$usuario_id || !$id) { echo json_encode(['status'=>'erro','mensagem'=>'Dados inválidos.']); exit; }

    $stmt = $pdo->prepare("DELETE FROM clientes WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$id, $usuario_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'erro', 'mensagem' => 'Cliente não encontrado.']);
    } else {
        echo json_encode(['status' => 'sucesso']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
