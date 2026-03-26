<?php
// 1. Inclui a nossa ponte de conexão
require_once 'config.php';

// 2. Simulando os dados que virão do App (No futuro, isso virá de um formulário)
$nome  = "Danilo Teste";
$email = "danilo@teste.com";
$senha = password_hash("123456", PASSWORD_DEFAULT); // Criptografa a senha por segurança
$zap   = "85999999999";

// 3. Lógica da Recorrência: Definindo 7 dias de teste a partir de HOJE
$data_hoje = new DateTime();
$data_hoje->modify('+7 days');
$data_expira = $data_hoje->format('Y-m-d H:i:s');

try {
    // 4. Prepara o comando SQL para inserir o usuário
    $sql = "INSERT INTO usuarios (nome_completo, email, senha, whatsapp, data_expiracao, status_assinatura) 
            VALUES (:nome, :email, :senha, :zap, :expira, 'teste')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);
    $stmt->bindParam(':zap', $zap);
    $stmt->bindParam(':expira', $data_expira);
    
    $stmt->execute();
    
    echo "✅ USUÁRIO CADASTRADO COM SUCESSO!<br>";
    echo "Sua licença grátis vence em: " . $data_expira;

} catch(PDOException $e) {
    // Se o e-mail já existir, o banco vai avisar aqui (por causa do UNIQUE que colocamos no Workbench)
    echo "❌ ERRO AO CADASTRAR: " . $e->getMessage();
}
?>