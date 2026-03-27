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
    $sql = "SELECT * FROM usuarios WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        
        // --- LOGICA DE EXPIRAÇÃO ---
        $hoje = new DateTime();
        $expira = new DateTime($usuario['data_expiracao']);

        if ($hoje > $expira) {
            echo json_encode([
                "status" => "expirado",
                "mensagem" => "Sua licença expirou em " . $expira->format('d/m/Y') . ". Realize o pagamento para continuar."
            ]);
        } else {
        // ESTA É A LINHA QUE MUDA: Adicionamos o "id"
            echo json_encode([
                "status" => "sucesso",
                "id" => $usuario['id'], 
                "nome" => $usuario['nome_completo'],
                "validade" => $expira->format('d/m/Y')
            ]);
        }
    } else {
        echo json_encode(["status" => "erro", "mensagem" => "E-mail ou senha incorretos."]);
    }

} catch(PDOException $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}
?>