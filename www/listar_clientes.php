<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = 'localhost'; $db = 'prodocorcamentos'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE usuario_id = ? ORDER BY nome ASC");
    $stmt->execute([$usuario_id]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'sucesso', 'clientes' => $clientes]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
