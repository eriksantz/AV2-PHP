<?php
session_start();
require('includes/db.php');

$erro = '';
$limiteTentativas = 3; // Define o limite de tentativas de login
$tempoBloqueioSegundos = 300; // Tempo de bloqueio em segundos (ex: 300 segundos = 5 minutos)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    // 1. Busca o cliente para verificar as tentativas de login e bloqueio
    $stmt = $pdo->prepare("SELECT id, nome, email, senha, tentativas_login_falhas, bloqueado_ate FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch();

    // Verifica se o cliente existe e se está bloqueado
    if ($cliente) {
        $agora = new DateTime();
        $bloqueadoAte = $cliente['bloqueado_ate'] ? new DateTime($cliente['bloqueado_ate']) : null;

        if ($bloqueadoAte && $bloqueadoAte > $agora) {
            $tempoRestante = $bloqueadoAte->getTimestamp() - $agora->getTimestamp();
            $minutos = ceil($tempoRestante / 60);
            $erro = "Sua conta está bloqueada devido a muitas tentativas falhas. Tente novamente em aproximadamente {$minutos} minutos.";
        } elseif (password_verify($senha, $cliente['senha'])) {
            // Login bem-sucedido: reseta as tentativas e o bloqueio
            $stmtUpdate = $pdo->prepare("UPDATE clientes SET tentativas_login_falhas = 0, bloqueado_ate = NULL WHERE id = ?");
            $stmtUpdate->execute([$cliente['id']]);

            $_SESSION['cliente'] = [
                'id' => $cliente['id'],
                'nome' => $cliente['nome'],
                'email' => $cliente['email']
            ];
            header('Location: index.php');
            exit;
        } else {
            // Senha incorreta: incrementa tentativas
            $novasTentativas = $cliente['tentativas_login_falhas'] + 1;
            $bloqueioTemporario = NULL;

            if ($novasTentativas >= $limiteTentativas) {
                // Se atingiu o limite, define o tempo de bloqueio
                $bloqueioTemporario = $agora->add(new DateInterval('PT' . $tempoBloqueioSegundos . 'S'))->format('Y-m-d H:i:s');
                $erro = "E-mail ou senha inválidos. Você atingiu o limite de tentativas. Sua conta foi bloqueada temporariamente. Por favor, tente novamente em {$tempoBloqueioSegundos} segundos ou use a opção de 'Esqueci a Senha'.";
            } else {
                $erro = 'E-mail ou senha inválidos. Tentativas restantes: ' . ($limiteTentativas - $novasTentativas);
            }

            // Atualiza as tentativas e/ou o tempo de bloqueio no DB
            $stmtUpdate = $pdo->prepare("UPDATE clientes SET tentativas_login_falhas = ?, bloqueado_ate = ? WHERE id = ?");
            $stmtUpdate->execute([$novasTentativas, $bloqueioTemporario, $cliente['id']]);
        }
    } else {
        // Cliente não encontrado (e-mail não existe)
        $erro = 'E-mail ou senha inválidos.';
        // Para evitar enumeração de usuários, não diga se o e-mail existe ou não.
        // Apenas para fins de depuração ou teste, você pode ter uma lógica diferente.
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login do Cliente</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="main-content">
        <h1>Login</h1>
        <?php if ($erro): ?>
            <p class="erro"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>
        <form method="post" class="form-box">
            <label>Email:
                <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </label>
            <label>Senha:
                <input type="password" name="senha" required>
            </label>
            <button type="submit">Entrar</button>
        </form>
        <p style="text-align: center; margin-top: 15px;">
            <a href="register.php">Não tem conta? Cadastre-se</a>
        </p>
        <p style="text-align: center; margin-top: 10px;">
            <a href="forgot_password.php">Esqueceu a senha?</a>
        </p>
    </main>
</body>
</html>