<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$usuario_id   = $_POST['usuario_id']   ?? '';
$nome_fantasia = trim($_POST['nome_fantasia'] ?? '');
$cnpj_cpf     = trim($_POST['cnpj_cpf']      ?? '');
$whatsapp     = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
$email        = trim($_POST['email']          ?? '');
$cep          = preg_replace('/\D/', '', $_POST['cep'] ?? '');
$endereco     = trim($_POST['endereco']       ?? '');
$numero       = trim($_POST['numero']         ?? '');
$bairro       = trim($_POST['bairro']         ?? '');
$cidade       = trim($_POST['cidade']         ?? '');
$estado       = strtoupper(trim($_POST['estado'] ?? ''));
$site         = trim($_POST['site']           ?? '');

if(!$usuario_id || !is_numeric($usuario_id)) {
    echo json_encode(["status" => "erro", "mensagem" => "Usuário não identificado."]);
    exit;
}
if(strlen($nome_fantasia) < 2) {
    echo json_encode(["status" => "erro", "mensagem" => "Nome da empresa é obrigatório."]);
    exit;
}
if(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "erro", "mensagem" => "E-mail da empresa inválido."]);
    exit;
}
if(!empty($whatsapp) && !preg_match('/^\d{10,11}$/', $whatsapp)) {
    echo json_encode(["status" => "erro", "mensagem" => "WhatsApp inválido. Use DDD + número (10 ou 11 dígitos)."]);
    exit;
}
if(!empty($estado) && !preg_match('/^[A-Z]{2}$/', $estado)) {
    echo json_encode(["status" => "erro", "mensagem" => "Estado inválido. Use a sigla com 2 letras (ex: CE)."]);
    exit;
}

try {
    $check = $conn->prepare("SELECT id FROM empresas WHERE usuario_id = :uid");
    $check->execute([':uid' => $usuario_id]);

    if($check->rowCount() > 0) {
        $sql = "UPDATE empresas SET
                    nome_fantasia = :nome, cnpj_cpf = :cnpj, whatsapp = :zap,
                    email = :email, cep = :cep, endereco = :end, numero = :num,
                    bairro = :bairro, cidade = :cidade, estado = :estado, site = :site
                WHERE usuario_id = :uid";
    } else {
        $sql = "INSERT INTO empresas
                    (usuario_id, nome_fantasia, cnpj_cpf, whatsapp, email, cep, endereco, numero, bairro, cidade, estado, site)
                VALUES
                    (:uid, :nome, :cnpj, :zap, :email, :cep, :end, :num, :bairro, :cidade, :estado, :site)";
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':uid'    => $usuario_id,
        ':nome'   => $nome_fantasia,
        ':cnpj'   => $cnpj_cpf,
        ':zap'    => $whatsapp,
        ':email'  => $email,
        ':cep'    => $cep,
        ':end'    => $endereco,
        ':num'    => $numero,
        ':bairro' => $bairro,
        ':cidade' => $cidade,
        ':estado' => $estado,
        ':site'   => $site
    ]);

    echo json_encode(["status" => "sucesso"]);

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>
