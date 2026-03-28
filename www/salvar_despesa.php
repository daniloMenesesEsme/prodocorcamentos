<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = 'localhost'; $db = 'prodocorcamentos'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id   = intval($_POST['usuario_id'] ?? 0);
    $id           = intval($_POST['id'] ?? 0);
    $descricao    = trim($_POST['descricao'] ?? '');
    $valor        = floatval($_POST['valor'] ?? 0);
    $categoria    = trim($_POST['categoria'] ?? 'outros');
    $data_despesa = trim($_POST['data_despesa'] ?? date('Y-m-d'));

    $cats_validas = ['materiais','servicos','transporte','alimentacao','outros'];
    if (!in_array($categoria, $cats_validas)) $categoria = 'outros';

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'Sessão inválida.']); exit; }
    if (strlen($descricao) < 2) { echo json_encode(['status'=>'erro','mensagem'=>'Descrição obrigatória.']); exit; }
    if ($valor <= 0) { echo json_encode(['status'=>'erro','mensagem'=>'Valor deve ser maior que zero.']); exit; }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE despesas SET descricao=?, valor=?, categoria=?, data_despesa=? WHERE id=? AND usuario_id=?");
        $stmt->execute([$descricao, $valor, $categoria, $data_despesa, $id, $usuario_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO despesas (usuario_id, descricao, valor, categoria, data_despesa) VALUES (?,?,?,?,?)");
        $stmt->execute([$usuario_id, $descricao, $valor, $categoria, $data_despesa]);
    }

    echo json_encode(['status' => 'sucesso']);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
