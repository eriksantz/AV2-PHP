<?php
require('includes/db.php'); // Certifique-se de que este caminho está correto para o seu arquivo db.php

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    // NOVOS CAMPOS
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    // FIM NOVOS CAMPOS
    $foto = $_FILES['foto'] ?? null;
    $documento = $_FILES['documento'] ?? null;

    // Defina os limites de tamanho de arquivo (em bytes)
    $maxFotoSize = 5 * 1024 * 1024; // 5 MB
    $maxDocSize = 10 * 1024 * 1024; // 10 MB

    // Validação dos campos obrigatórios (agora incluindo telefone e endereco)
    if (!$nome || !$email || !$senha || !$telefone || !$endereco || !$foto || !$documento) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif ($foto['error'] !== UPLOAD_ERR_OK || $documento['error'] !== UPLOAD_ERR_OK) {
        // Um erro ocorreu no upload inicial (tamanho muito grande pelo php.ini, etc.)
        $erro = 'Erro no upload dos arquivos. Verifique o tamanho dos arquivos e tente novamente.';
    } else {
        $extensaoFoto = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
        $extensaoDoc = strtolower(pathinfo($documento['name'], PATHINFO_EXTENSION));

        // Validação de tipo e tamanho dos arquivos
        if (!in_array($extensaoFoto, ['jpg', 'jpeg', 'png'])) {
            $erro = 'Foto de perfil deve ser JPG, JPEG ou PNG.';
        } elseif ($foto['size'] > $maxFotoSize) { // Validação de tamanho da foto
            $erro = 'A foto é muito grande (máximo ' . ($maxFotoSize / (1024*1024)) . ' MB).';
        } elseif ($extensaoDoc !== 'pdf') {
            $erro = 'Documento deve ser PDF.';
        } elseif ($documento['size'] > $maxDocSize) { // Validação de tamanho do documento
            $erro = 'O documento é muito grande (máximo ' . ($maxDocSize / (1024*1024)) . ' MB).';
        } else {
            // Gera nomes únicos para os arquivos para evitar conflitos
            $fotoNome = uniqid('foto_', true) . "." . $extensaoFoto;
            $docNome = uniqid('doc_', true) . ".pdf";

            // Define os caminhos de destino
            $caminhoFoto = "uploads/photos/$fotoNome";
            $caminhoDoc = "uploads/documents/$docNome";

            // Garante que os diretórios de upload existam
            if (!is_dir('uploads/photos')) {
                mkdir('uploads/photos', 0777, true);
            }
            if (!is_dir('uploads/documents')) {
                mkdir('uploads/documents', 0777, true);
            }

            // Move os arquivos temporários para os caminhos permanentes
            if (move_uploaded_file($foto['tmp_name'], $caminhoFoto) && move_uploaded_file($documento['tmp_name'], $caminhoDoc)) {
                $hash = password_hash($senha, PASSWORD_DEFAULT);

                try {
                    // Atualiza a query INSERT para incluir telefone e endereco
                    $stmt = $pdo->prepare("INSERT INTO clientes (nome, email, senha, telefone, endereco, foto, pdf) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$nome, $email, $hash, $telefone, $endereco, $fotoNome, $docNome]);
                    
                    // Redirecionamento direto após sucesso, conforme o requisito
                    header('Location: login.php');
                    exit;

                } catch (PDOException $e) {
                    // Se o e-mail já existir (coluna email é UNIQUE no DB)
                    // Verifica se o erro é de violação de chave única (código 23000 é comum para UNIQUE constraint)
                    if ($e->getCode() == 23000) {
                        $erro = 'Erro ao cadastrar: e-mail já está em uso. Por favor, use outro e-mail.';
                    } else {
                        $erro = 'Erro ao cadastrar: ' . $e->getMessage(); // Para depuração, em produção, use uma mensagem mais genérica
                    }
                    // Opcional: Se a inserção falhar, você pode querer remover os arquivos que já foram movidos
                    unlink($caminhoFoto);
                    unlink($caminhoDoc);
                }
            } else {
                $erro = 'Falha ao mover um ou mais arquivos para o diretório de upload.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Cliente</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="main-content">
        <h1>Cadastro</h1>

        <?php if ($erro): ?>
            <p class="erro"><?= htmlspecialchars($erro) ?></p>
        <?php elseif ($sucesso): ?>
            <p class="sucesso"><?= $sucesso ?></p>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="form-box">
            <label>Nome:
                <input type="text" name="nome" value="<?= htmlspecialchars($nome ?? '') ?>" required>
            </label>

            <label>Email:
                <input type="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
            </label>

            <label>Senha:
                <input type="password" name="senha" required>
            </label>

            <label>Telefone:
                <input type="text" name="telefone" value="<?= htmlspecialchars($telefone ?? '') ?>" required placeholder="(XX) XXXXX-XXXX">
            </label>

            <label>Endereço:
                <textarea name="endereco" required rows="3" placeholder="Rua, Número, Bairro, Cidade, Estado"><?= htmlspecialchars($endereco ?? '') ?></textarea>
            </label>

            <label>Foto de Perfil:
                <input type="file" name="foto" accept=".jpg,.jpeg,.png" required>
            </label>

            <label>Documento PDF:
                <input type="file" name="documento" accept=".pdf" required>
            </label>

            <button type="submit">Cadastrar</button>
        </form>

        <p style="text-align: center; margin-top: 15px;">
            <a href="login.php">Já tem conta? Faça login</a>
        </p>
    </main>
</body>
</html>