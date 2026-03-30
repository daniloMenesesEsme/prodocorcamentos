<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    // Detecta se a coluna status existe
    $cols = $conn->query("SHOW COLUMNS FROM orcamentos LIKE 'status'")->fetchAll();
    $temStatus = count($cols) > 0;

    $wAprov  = $temStatus ? "AND status = 'aprovado'"                  : "";
    $wPend   = $temStatus ? "AND (status = 'enviado' OR status IS NULL)" : "";
    $wRecus  = $temStatus ? "AND status = 'recusado'"                  : "";
    $wMes    = "AND MONTH(criado_em) = MONTH(NOW()) AND YEAR(criado_em) = YEAR(NOW())";

    // Totais do mês
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id = ? $wMes");
    $stmt->execute([$usuario_id]);
    $total_mes = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE usuario_id = ? $wMes $wAprov");
    $stmt->execute([$usuario_id]);
    [$aprov_qtd, $aprov_receita] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE usuario_id = ? $wMes $wPend");
    $stmt->execute([$usuario_id]);
    [$pend_qtd, $pend_valor] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id = ? $wMes $wRecus");
    $stmt->execute([$usuario_id]);
    $recus_qtd = (int)$stmt->fetchColumn();

    // Despesas do mês
    $despesas_mes = 0;
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor),0) FROM despesas WHERE usuario_id = ? AND MONTH(data_despesa) = MONTH(NOW()) AND YEAR(data_despesa) = YEAR(NOW())");
        $stmt->execute([$usuario_id]);
        $despesas_mes = (float)$stmt->fetchColumn();
    } catch(Exception $e) {}

    // Totais gerais
    $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
    $total_geral = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM orcamentos WHERE usuario_id = ? $wAprov");
    $stmt->execute([$usuario_id]);
    $receita_total = (float)$stmt->fetchColumn();

    // Histórico 6 meses (só aprovados = receita real)
    $stmt = $conn->prepare(
        "SELECT DATE_FORMAT(criado_em,'%m/%Y') as mes, YEAR(criado_em) as ano, MONTH(criado_em) as num_mes,
                COUNT(*) as qtd, COALESCE(SUM(total),0) as receita
         FROM orcamentos WHERE usuario_id = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 6 MONTH) $wAprov
         GROUP BY YEAR(criado_em), MONTH(criado_em), DATE_FORMAT(criado_em,'%m/%Y')
         ORDER BY YEAR(criado_em) ASC, MONTH(criado_em) ASC"
    );
    $stmt->execute([$usuario_id]);
    $historico_meses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top clientes (só aprovados)
    $stmt = $conn->prepare(
        "SELECT cliente, COUNT(*) as qtd, COALESCE(SUM(total),0) as total
         FROM orcamentos WHERE usuario_id = ? $wAprov
         GROUP BY cliente ORDER BY total DESC LIMIT 5"
    );
    $stmt->execute([$usuario_id]);
    $top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Alertas de estoque
    $qtd_alertas = 0;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM produtos_servicos WHERE usuario_id = ? AND categoria = 'produto' AND estoque_minimo > 0 AND estoque_atual <= estoque_minimo");
        $stmt->execute([$usuario_id]);
        $qtd_alertas = (int)$stmt->fetchColumn();
    } catch(Exception $e) {}

    echo json_encode([
        'status'              => 'sucesso',
        'total_mes'           => $total_mes,
        'aprovados_mes'       => (int)$aprov_qtd,
        'receita_mes'         => (float)$aprov_receita,
        'pendentes_mes'       => (int)$pend_qtd,
        'pendentes_valor_mes' => (float)$pend_valor,
        'recusados_mes'       => (int)$recus_qtd,
        'despesas_mes'        => $despesas_mes,
        'total_geral'         => $total_geral,
        'receita_total'       => $receita_total,
        'historico_meses'     => $historico_meses,
        'top_clientes'        => $top_clientes,
        'qtd_alertas'         => $qtd_alertas
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
