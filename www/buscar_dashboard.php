<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$host = 'localhost'; $db = 'prodocorcamentos'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    // Totais gerais
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as receita FROM orcamentos WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $geral = $stmt->fetch(PDO::FETCH_ASSOC);

    // Este mês
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as receita FROM orcamentos WHERE usuario_id = ? AND MONTH(criado_em) = MONTH(NOW()) AND YEAR(criado_em) = YEAR(NOW())");
    $stmt->execute([$usuario_id]);
    $mes = $stmt->fetch(PDO::FETCH_ASSOC);

    // Últimos 6 meses
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(criado_em, '%m/%Y') as mes, COUNT(*) as qtd, COALESCE(SUM(total), 0) as receita FROM orcamentos WHERE usuario_id = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(criado_em, '%Y%m') ORDER BY DATE_FORMAT(criado_em, '%Y%m') ASC");
    $stmt->execute([$usuario_id]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 clientes por valor
    $stmt = $pdo->prepare("SELECT cliente, COUNT(*) as qtd, COALESCE(SUM(total), 0) as total FROM orcamentos WHERE usuario_id = ? GROUP BY cliente ORDER BY total DESC LIMIT 5");
    $stmt->execute([$usuario_id]);
    $topClientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Despesas do mês (se tabela existir)
    $despesas_mes = 0;
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE usuario_id = ? AND MONTH(data_despesa) = MONTH(NOW()) AND YEAR(data_despesa) = YEAR(NOW())");
        $stmt->execute([$usuario_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $despesas_mes = $row['total'];
    } catch(Exception $e) {}

    echo json_encode([
        'status'          => 'sucesso',
        'total_geral'     => (int)$geral['total'],
        'receita_total'   => (float)$geral['receita'],
        'total_mes'       => (int)$mes['total'],
        'receita_mes'     => (float)$mes['receita'],
        'despesas_mes'    => (float)$despesas_mes,
        'historico_meses' => $historico,
        'top_clientes'    => $topClientes
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
