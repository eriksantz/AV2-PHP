<?php
session_start();
require 'includes/db.php';

if (!isset($_SESSION['cliente'])) {
    header('Location: login.php');
    exit;
}

// Pega dados atualizados do cliente
$id = $_SESSION['cliente']['id'];
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) {
    echo "Cliente não encontrado.";
    exit;
}

// Caminhos para arquivos
$fotoPath = 'uploads/photos/' . $cliente['foto'];
$docPath = 'uploads/documents/' . $cliente['documento'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil do Cliente</title>
     <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <h1>Perfil de <?= htmlspecialchars($cliente['nome']) ?></h1>
    <p><a href="index.php">← Voltar para a loja</a> | <a href="logout.php">Sair</a></p>

    <h3>Informações</h3>
    <ul>
        <li><strong>Nome:</strong> <?= htmlspecialchars($cliente['nome']) ?></li>
        <li><strong>Email:</strong> <?= htmlspecialchars($cliente['email']) ?></li>
    </ul>

    <h3>Foto de Perfil</h3>
    <?php if (file_exists($fotoPath)): ?>
        <img src="<?= $fotoPath ?>" alt="Foto de perfil" width="150" height="150">
    <?php else: ?>
        <p>Foto não encontrada.</p>
    <?php endif; ?>

    <h3>Documento PDF</h3>
    <?php if (file_exists($docPath)): ?>
        <a href="<?= $docPath ?>" target="_blank">Baixar Documento</a>
    <?php else: ?>
        <p>Documento não encontrado.</p>
    <?php endif; ?>
</body>
</html>
