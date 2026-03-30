<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

try {
    $usuario_id  = intval($_POST['usuario_id']  ?? 0);
    $empresa_id  = intval($_POST['empresa_id']  ?? 0);
    $filtro_uid  = intval($_POST['filtro_uid']  ?? 0); // 0 = todos
    $modo        = trim($_POST['modo']          ?? 'mes'); // 'mes' | 'periodo'
    $mes         = intval($_POST['mes']         ?? date('n'));
    $ano         = intval($_POST['ano']         ?? date('Y'));
    $data_inicio = trim($_POST['data_inicio']   ?? '');
    $data_fim    = trim($_POST['data_fim']      ?? '');

    if (!$usuario_id || !$empresa_id) {
        echo json_encode(['status'=>'erro','mensagem'=>'Dados inválidos.']); exit;
    }

    // Verifica se é dono ou gerente
    $stmt = $conn->prepare("SELECT papel FROM usuarios WHERE id = ? AND empresa_id = ?");
    $stmt->execute([$usuario_id, $empresa_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !in_array($user['papel'], ['dono','gerente'])) {
        echo json_encode(['status'=>'erro','mensagem'=>'Sem permissão.']); exit;
    }

    // IDs dos membros da empresa
    $stmtM = $conn->prepare("SELECT id, nome_completo FROM usuarios WHERE empresa_id = ? ORDER BY nome_completo");
    $stmtM->execute([$empresa_id]);
    $membros = $stmtM->fetchAll(PDO::FETCH_ASSOC);
    $ids = array_column($membros, 'id');
    if (empty($ids)) { echo json_encode(['status'=>'sucesso','membros'=>[],'orcamentos'=>[],'resumo'=>[]]); exit; }

    // Se filtro por funcionário específico
    if ($filtro_uid && in_array($filtro_uid, $ids)) {
        $ids_filtro = [$filtro_uid];
    } else {
        $ids_filtro = $ids;
    }

    $placeholders = implode(',', array_fill(0, count($ids_filtro), '?'));

    // Detecta coluna status
    $cols = $conn->query("SHOW COLUMNS FROM orcamentos LIKE 'status'")->fetchAll();
    $temStatus = count($cols) > 0;
    $wAprov = $temStatus ? "AND status = 'aprovado'" : "";
    $wPend  = $temStatus ? "AND (status = 'enviado' OR status IS NULL)" : "";
    $wRecus = $temStatus ? "AND status = 'recusado'" : "";
    $colStatus = $temStatus ? ", status" : "";

    // Monta cláusula de período
    if ($modo === 'periodo' && $data_inicio && $data_fim) {
        $where_periodo = "AND DATE(criado_em) BETWEEN ? AND ?";
        $params_periodo = [$data_inicio, $data_fim];
        $label_periodo  = date('d/m/Y', strtotime($data_inicio)) . ' a ' . date('d/m/Y', strtotime($data_fim));
    } else {
        if ($mes < 1 || $mes > 12) $mes = date('n');
        if ($ano < 2020 || $ano > 2100) $ano = date('Y');
        $where_periodo = "AND MONTH(criado_em) = ? AND YEAR(criado_em) = ?";
        $params_periodo = [$mes, $ano];
        $label_periodo  = '';
        $modo = 'mes';
    }

    $params_base = array_merge($ids_filtro, $params_periodo);

    // Todos os orçamentos do período com nome do funcionário
    $stmt = $conn->prepare(
        "SELECT o.id, o.usuario_id, u.nome_completo as funcionario, o.cliente,
                o.itens, o.total, o.tipo{$colStatus}, o.criado_em
         FROM orcamentos o
         JOIN usuarios u ON u.id = o.usuario_id
         WHERE o.usuario_id IN ($placeholders) $where_periodo
         ORDER BY o.criado_em DESC LIMIT 100"
    );
    $stmt->execute($params_base);
    $orcamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orcamentos as &$o) {
        $o['itens']  = json_decode($o['itens'], true) ?? [];
        if (!$temStatus) $o['status'] = 'enviado';
        $o['criado_em'] = date('d/m/Y H:i', strtotime($o['criado_em']));
    }

    // Resumo por funcionário
    $resumo = [];
    foreach ($membros as $m) {
        if ($filtro_uid && $m['id'] != $filtro_uid) continue;
        $pid = $m['id'];
        $p2  = array_merge([$pid], $params_periodo);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id = ? $where_periodo");
        $stmt->execute($p2); $total = (int)$stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE usuario_id = ? $where_periodo $wAprov");
        $stmt->execute($p2); [$aprov_q, $aprov_v] = $stmt->fetch(PDO::FETCH_NUM);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id = ? $where_periodo $wPend");
        $stmt->execute($p2); $pend_q = (int)$stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id = ? $where_periodo $wRecus");
        $stmt->execute($p2); $recus_q = (int)$stmt->fetchColumn();

        $resumo[] = [
            'usuario_id'  => $pid,
            'nome'        => $m['nome_completo'],
            'total'       => $total,
            'aprovados'   => (int)$aprov_q,
            'receita'     => (float)$aprov_v,
            'pendentes'   => (int)$pend_q,
            'recusados'   => (int)$recus_q,
        ];
    }

    // Totais consolidados
    $stmt = $conn->prepare("SELECT COUNT(*), COALESCE(SUM(total),0) FROM orcamentos WHERE usuario_id IN ($placeholders) $where_periodo $wAprov");
    $stmt->execute($params_base);
    [$total_aprov, $receita_total] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id IN ($placeholders) $where_periodo $wPend");
    $stmt->execute($params_base);
    $total_pend = (int)$stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM orcamentos WHERE usuario_id IN ($placeholders) $where_periodo $wRecus");
    $stmt->execute($params_base);
    $total_recus = (int)$stmt->fetchColumn();

    echo json_encode([
        'status'        => 'sucesso',
        'modo'          => $modo,
        'mes'           => $mes,
        'ano'           => $ano,
        'label_periodo' => $label_periodo,
        'membros'       => $membros,
        'resumo'        => $resumo,
        'orcamentos'    => $orcamentos,
        'total_aprov'   => (int)$total_aprov,
        'receita_total' => (float)$receita_total,
        'total_pend'    => $total_pend,
        'total_recus'   => $total_recus,
    ]);
} catch (Exception $e) {
    echo json_encode(['status'=>'erro','mensagem'=>$e->getMessage()]);
}
