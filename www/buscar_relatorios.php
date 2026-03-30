<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require_once 'config.php';

try {
    $usuario_id    = intval($_POST['usuario_id'] ?? 0);
    $modo          = trim($_POST['modo'] ?? 'mes'); // 'mes' | 'periodo'
    $mes           = intval($_POST['mes'] ?? date('n'));
    $ano           = intval($_POST['ano'] ?? date('Y'));
    $data_inicio   = trim($_POST['data_inicio'] ?? '');
    $data_fim      = trim($_POST['data_fim'] ?? '');

    if (!$usuario_id) { echo json_encode(['status'=>'erro','mensagem'=>'ID inválido']); exit; }

    // Monta cláusula WHERE por período
    if ($modo === 'periodo' && $data_inicio && $data_fim) {
        $where_orc  = "usuario_id = ? AND DATE(criado_em) BETWEEN ? AND ?";
        $params_orc = [$usuario_id, $data_inicio, $data_fim];
        $where_desp  = "usuario_id = ? AND data_despesa BETWEEN ? AND ?";
        $params_desp = [$usuario_id, $data_inicio, $data_fim];
        $label_periodo = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
    } else {
        if ($mes < 1 || $mes > 12) $mes = date('n');
        if ($ano < 2020 || $ano > 2100) $ano = date('Y');
        $where_orc  = "usuario_id = ? AND MONTH(criado_em) = ? AND YEAR(criado_em) = ?";
        $params_orc = [$usuario_id, $mes, $ano];
        $where_desp  = "usuario_id = ? AND MONTH(data_despesa) = ? AND YEAR(data_despesa) = ?";
        $params_desp = [$usuario_id, $mes, $ano];
        $label_periodo = '';
        $modo = 'mes';
    }

    // Detecta se coluna status existe
    $cols = $conn->query("SHOW COLUMNS FROM orcamentos LIKE 'status'")->fetchAll();
    $temStatus = count($cols) > 0;
    $wAprov = $temStatus ? "AND status = 'aprovado'"                   : "";
    $wPend  = $temStatus ? "AND (status = 'enviado' OR status IS NULL)" : "";
    $wRecus = $temStatus ? "AND status = 'recusado'"                   : "";
    $colStatus = $temStatus ? ", status" : "";

    // Todos os orçamentos do período
    $stmt = $conn->prepare("SELECT id, cliente, itens, total, tipo{$colStatus}, criado_em FROM orcamentos WHERE $where_orc ORDER BY criado_em DESC");
    $stmt->execute($params_orc);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orcamentos as &$o) {
        $o['itens'] = json_decode($o['itens'], true) ?? [];
        if (!$temStatus) $o['status'] = 'enviado';
    }

    $total_orc = count($orcamentos);

    // Receita = só aprovados
    $stmt = $conn->prepare("SELECT COALESCE(SUM(total),0) FROM orcamentos WHERE $where_orc $wAprov");
    $stmt->execute($params_orc);
    $receita_total = (float)$stmt->fetchColumn();

    // Funil: aprovados / pendentes / recusados
    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE $where_orc $wAprov");
    $stmt->execute($params_orc);
    [$qtd_aprovados, $val_aprovados] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE $where_orc $wPend");
    $stmt->execute($params_orc);
    [$qtd_pendentes, $val_pendentes] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE $where_orc $wRecus");
    $stmt->execute($params_orc);
    [$qtd_recusados, $val_recusados] = $stmt->fetch(PDO::FETCH_NUM);

    // Top clientes (só aprovados)
    $stmt = $conn->prepare("SELECT cliente, COUNT(*) as qtd, COALESCE(SUM(total), 0) as total FROM orcamentos WHERE $where_orc $wAprov GROUP BY cliente ORDER BY total DESC LIMIT 5");
    $stmt->execute($params_orc);
    $top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Por tipo (todos)
    $stmt = $conn->prepare("SELECT tipo, COUNT(*) as qtd, COALESCE(SUM(total), 0) as total FROM orcamentos WHERE $where_orc GROUP BY tipo");
    $stmt->execute($params_orc);
    $por_tipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Despesas
    $despesas_total = 0;
    $despesas_lista = [];
    try {
        $stmt = $conn->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM despesas WHERE $where_desp");
        $stmt->execute($params_desp);
        $despesas_total = (float)$stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT descricao, valor, categoria FROM despesas WHERE $where_desp ORDER BY data_despesa DESC");
        $stmt->execute($params_desp);
        $despesas_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {}

    echo json_encode([
        'status'          => 'sucesso',
        'modo'            => $modo,
        'mes'             => $mes,
        'ano'             => $ano,
        'label_periodo'   => $label_periodo,
        'total_orc'       => $total_orc,
        'receita_total'   => (float)$receita_total,
        'qtd_aprovados'   => (int)$qtd_aprovados,
        'val_aprovados'   => (float)$val_aprovados,
        'qtd_pendentes'   => (int)$qtd_pendentes,
        'val_pendentes'   => (float)$val_pendentes,
        'qtd_recusados'   => (int)$qtd_recusados,
        'val_recusados'   => (float)$val_recusados,
        'despesas_total'  => $despesas_total,
        'saldo'           => (float)$receita_total - $despesas_total,
        'orcamentos'      => $orcamentos,
        'top_clientes'    => $top_clientes,
        'por_tipo'        => $por_tipo,
        'despesas_lista'  => $despesas_lista
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'erro', 'mensagem' => $e->getMessage()]);
}
