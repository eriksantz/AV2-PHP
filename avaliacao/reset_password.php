<?php
session_start();
require('includes/db.php'); // Certifique-se de que o caminho está correto

$token = $_GET['token'] ?? '';
$mensagem = '';
$tipoMensagem = '';
$tokenValido = false; // Flag para controlar a exibição do formulário de nova senha

if (empty($token)) {
    $mensagem = 'Token de redefinição de senha inválido ou ausente.';
    $tipoMensagem = 'erro';
} else {
    try {
        // Busca o cliente pelo token e verifica se ele não expirou
        $stmt = $pdo->prepare("SELECT id, nome, email, reset_expires_at FROM clientes WHERE reset_token = ? AND reset_expires_at > NOW()");
        $stmt->execute([$token]);
        $cliente = $stmt->fetch();

        if ($cliente) {
            $tokenValido = true;
            // Se o token for válido e o formulário for submetido
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $novaSenha = $_POST['nova_senha'] ?? '';
                $confirmaSenha = $_POST['confirma_senha'] ?? '';

                if (empty($novaSenha) || empty($confirmaSenha)) {
                    $mensagem = 'Por favor, preencha ambos os campos de senha.';
                    $tipoMensagem = 'erro';
                } elseif ($novaSenha !== $confirmaSenha) {
                    $mensagem = 'As senhas não coincidem.';
                    $tipoMensagem = 'erro';
                } elseif (strlen($novaSenha) < 6) { // Exemplo de validação de força da senha
                    $mensagem = 'A nova senha deve ter no mínimo 6 caracteres.';
                    $tipoMensagem = 'erro';
                } else {
                    // Atualiza a senha e invalida o token
                    $hashNovaSenha = password_hash($novaSenha, PASSWORD_DEFAULT);
                    $stmtUpdate = $pdo->prepare("UPDATE clientes SET senha = ?, reset_token = NULL, reset_expires_at = NULL, tentativas_login_falhas = 0 WHERE id = ?");
                    $stmtUpdate->execute([$hashNovaSenha, $cliente['id']]);

                    $mensagem = 'Sua senha foi redefinida com sucesso! Você já pode fazer login com a nova senha.';
                    $tipoMensagem = 'sucesso';
                    $tokenValido = false; // Desabilita o formulário de senha após a redefinição
                    // Opcional: Redirecionar para a página de login
                    // header('Location: login.php?reset=success');
                    // exit;
                }
            }
        } else {
            $mensagem = 'Token de redefinição inválido ou expirado. Por favor, solicite um novo.';
            $tipoMensagem = 'erro';
        }
    } catch (PDOException $e) {
        $mensagem = 'Erro interno do servidor. Tente novamente mais tarde.';
        $tipoMensagem = 'erro';
        error_log("Erro em reset_password: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="main-content">
        <h1>Redefinir Senha</h1>

        <?php if ($mensagem): ?>
            <p class="<?= $tipoMensagem ?>"><?= $mensagem ?></p>
        <?php endif; ?>

        <?php if ($tokenValido): // Só exibe o formulário de senha se o token for válido ?>
            <form method="post" class="form-box">
                <label>Nova Senha:
                    <input type="password" name="nova_senha" required>
                </label>
                <label>Confirmar Nova Senha:
                    <input type="password" name="confirma_senha" required>
                </label>
                <button type="submit">Redefinir Senha</button>
            </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 15px;">
            <a href="login.php">Voltar para o Login</a>
        </p>
    </main>
</body>
</html>
