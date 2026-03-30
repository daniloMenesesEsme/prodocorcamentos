<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once 'config.php';

$email = trim($_POST['email'] ?? '');
$senha =      $_POST['senha'] ?? '';

if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "erro", "mensagem" => "E-mail inválido."]);
    exit;
}
if(empty($senha)) {
    echo json_encode(["status" => "erro", "mensagem" => "Senha não informada."]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Usuário não encontrado — mensagem genérica para não revelar se o e-mail existe
    if(!$usuario) {
        echo json_encode(["status" => "erro", "mensagem" => "E-mail ou senha incorretos."]);
        exit;
    }

    // Verifica se a conta está bloqueada
    $agora = new DateTime();
    if($usuario['bloqueado_ate'] && $agora < new DateTime($usuario['bloqueado_ate'])) {
        $diff = $agora->diff(new DateTime($usuario['bloqueado_ate']));
        $min  = $diff->i + ($diff->h * 60) + 1;
        echo json_encode([
            "status"   => "erro",
            "mensagem" => "Conta bloqueada por tentativas excessivas. Aguarde {$min} minuto(s) para tentar novamente."
        ]);
        exit;
    }

    // Senha incorreta
    if(!password_verify($senha, $usuario['senha'])) {
        $tentativas = $usuario['tentativas_login'] + 1;

        if($tentativas >= 5) {
            $bloqueio = (new DateTime())->modify('+15 minutes')->format('Y-m-d H:i:s');
            $conn->prepare("UPDATE usuarios SET tentativas_login = :t, bloqueado_ate = :b WHERE id = :id")
                 ->execute([':t' => $tentativas, ':b' => $bloqueio, ':id' => $usuario['id']]);
            echo json_encode([
                "status"   => "erro",
                "mensagem" => "Muitas tentativas incorretas. Conta bloqueada por 15 minutos."
            ]);
        } else {
            $restantes = 5 - $tentativas;
            $conn->prepare("UPDATE usuarios SET tentativas_login = :t WHERE id = :id")
                 ->execute([':t' => $tentativas, ':id' => $usuario['id']]);
            echo json_encode([
                "status"   => "erro",
                "mensagem" => "Senha incorreta. {$restantes} tentativa(s) restante(s) antes do bloqueio."
            ]);
        }
        exit;
    }

    // Login correto — zera o contador de tentativas
    $conn->prepare("UPDATE usuarios SET tentativas_login = 0, bloqueado_ate = NULL WHERE id = :id")
         ->execute([':id' => $usuario['id']]);

    // Verifica expiração da licença
    $expira = new DateTime($usuario['data_expiracao']);
    if($agora > $expira) {
        echo json_encode([
            "status"   => "expirado",
            "mensagem" => "Sua licença expirou em " . $expira->format('d/m/Y') . ". Realize o pagamento para continuar."
        ]);
    } else {
        echo json_encode([
            "status"     => "sucesso",
            "id"         => $usuario['id'],
            "nome"       => $usuario['nome_completo'],
            "validade"   => $expira->format('d/m/Y'),
            "papel"      => $usuario['papel']      ?? 'dono',
            "empresa_id" => $usuario['empresa_id'] ?? $usuario['id']
        ]);
    }

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>