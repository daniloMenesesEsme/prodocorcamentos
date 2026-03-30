<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id = $_POST['usuario_id'] ?? '';

if(!$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "ID inválido."]);
    exit;
}

try {
    // Detecta se as colunas obs/status já existem (adicionadas pelo ponto B)
    $cols = $conn->query("SHOW COLUMNS FROM orcamentos LIKE 'status'")->fetchAll();
    $temStatus = count($cols) > 0;
    $extraCols = $temStatus ? ", obs, status" : "";

    $stmt = $conn->prepare(
        "SELECT id, cliente, itens, total, tipo{$extraCols}, criado_em,
                editado_em, editado_por, historico_edicoes
         FROM orcamentos
         WHERE usuario_id = :uid
         ORDER BY criado_em DESC
         LIMIT 50"
    );
    $stmt->execute([':uid' => $usuario_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($rows as &$row) {
        $row['itens']             = json_decode($row['itens'], true);
        $row['historico_edicoes'] = json_decode($row['historico_edicoes'] ?? '[]', true) ?: [];
        $row['criado_em']         = date('d/m/Y H:i', strtotime($row['criado_em']));
        if ($row['editado_em']) {
            $row['editado_em'] = date('d/m/Y H:i', strtotime($row['editado_em']));
        }
        if (!$temStatus) {
            $row['obs']    = '';
            $row['status'] = 'enviado';
        }
    }

    echo json_encode(["status" => "sucesso", "orcamentos" => $rows]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
