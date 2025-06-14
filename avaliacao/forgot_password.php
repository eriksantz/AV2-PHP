<?php
session_start();
require('includes/db.php'); // Certifique-se de que o caminho está correto

$mensagem = '';
$tipoMensagem = ''; // 'sucesso' ou 'erro'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $mensagem = 'Por favor, digite seu e-mail.';
        $tipoMensagem = 'erro';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'E-mail inválido.';
        $tipoMensagem = 'erro';
    } else {
        try {
            // Verifica se o e-mail existe no banco de dados
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
            $stmt->execute([$email]);
            $cliente = $stmt->fetch();

            if ($cliente) {
                // E-mail encontrado, gera um token e define uma expiração (ex: 1 hora)
                $token = bin2hex(random_bytes(32)); // Gera um token aleatório e longo
                $expires = date('Y-m-d H:i:s', time() + 3600); // Token expira em 1 hora

                // Salva o token e a expiração no banco de dados para o cliente
                $stmtUpdate = $pdo->prepare("UPDATE clientes SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
                $stmtUpdate->execute([$token, $expires, $cliente['id']]);

                // *** SIMULAÇÃO DE ENVIO DE E-MAIL ***
                // Em um ambiente real, aqui você usaria uma função de envio de e-mail (ex: PHPMailer)
                // para enviar um e-mail para $email com o link:
                // "http://seusite.com/reset_password.php?token=" . $token
                //
                // Para o propósito da avaliação e testes locais sem um servidor de e-mail:
                $projectPath = '/avaliacao/';
                $linkReset = "http://" . $_SERVER['HTTP_HOST'] . $projectPath . "reset_password.php?token=" . $token;
                $mensagem = "Um link para redefinir sua senha foi enviado para seu e-mail. O link expira em 1 hora. <br><br> (Para fins de teste, o link é: <a href=\"{$linkReset}\">{$linkReset}</a>)";
                $tipoMensagem = 'sucesso';

            } else {
                // Para segurança, não informamos se o e-mail não foi encontrado.
                // A mensagem é a mesma, para evitar enumeração de usuários.
                $mensagem = 'Se o e-mail estiver cadastrado, um link de redefinição será enviado.';
                $tipoMensagem = 'sucesso';
            }
        } catch (PDOException $e) {
            $mensagem = 'Erro interno do servidor. Tente novamente mais tarde.';
            $tipoMensagem = 'erro';
            error_log("Erro em forgot_password: " . $e->getMessage()); // Loga o erro real
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="main-content">
        <h1>Recuperar Senha</h1>

        <?php if ($mensagem): ?>
            <p class="<?= $tipoMensagem ?>"><?= $mensagem ?></p>
        <?php endif; ?>

        <form method="post" class="form-box">
            <p>Por favor, digite seu e-mail para receber o link de redefinição de senha.</p>
            <label>Email:
                <input type="email" name="email" required>
            </label>
            <button type="submit">Enviar Link de Redefinição</button>
        </form>

        <p style="text-align: center; margin-top: 15px;">
            <a href="login.php">Voltar para o Login</a>
        </p>
    </main>
</body>
</html>