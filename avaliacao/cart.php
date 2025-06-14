<?php
session_start();
require('includes/db.php'); // Inclua seu arquivo de conexão com o banco de dados

// Carrega os produtos do banco de dados
$produtos = [];
try {
    $stmt = $pdo->query("SELECT id, nome, preco, promocao, imagem FROM produtos");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $produtos[$row['id']] = $row;
    }
} catch (PDOException $e) {
    error_log("Erro ao carregar produtos do DB em cart.php: " . $e->getMessage());
    // Em um ambiente real, você pode querer exibir uma mensagem amigável ao usuário
}

// Pega carrinho do cookie
$cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];

// ... restante do seu código PHP
// Pega carrinho do cookie
$cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];

// Remover item
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($cart[$id]);
    setcookie('cart', json_encode($cart), time() + 3600, '/');
    header('Location: cart.php');
    exit;
}

// Finalizar compra
if (isset($_GET['finalizar'])) {
    // 1. Verificar se o cliente está logado
    if (!isset($_SESSION['cliente']['id'])) {
        // Redireciona para o login se não estiver logado
        header('Location: login.php?redirect=cart.php'); // Pode passar um parâmetro para voltar depois
        exit;
    }

    $clienteId = $_SESSION['cliente']['id'];
    $sucessoCompra = false;

    // Iniciar uma transação para garantir que todos os itens sejam salvos ou nenhum
    $pdo->beginTransaction();
    try {
        foreach ($cart as $produtoId => $quantidade) {
            // Verifica se o produto ainda existe e obtém o preço atual (pode ser diferente do preço do cookie se a loja mudar)
            if (isset($produtos[$produtoId])) {
                $precoUnitario = $produtos[$produtoId]['promocao'] ?? $produtos[$produtoId]['preco'];

                // Insere cada item do carrinho na tabela carrinho_compras
                $stmt = $pdo->prepare("INSERT INTO carrinho_compras (cliente_id, produto_id, quantidade, preco_unitario_compra, data_compra) VALUES (?, ?, ?, ?, NOW())");
                // Adicionei 'preco_unitario_compra' e 'data_compra' para maior detalhe histórico,
                // certifique-se de adicioná-los à sua tabela 'carrinho_compras' se ainda não tiver.
                $stmt->execute([$clienteId, $produtoId, $quantidade, $precoUnitario]);
            }
        }

        // Se tudo deu certo, comita a transação
        $pdo->commit();
        $sucessoCompra = true;

    } catch (PDOException $e) {
        // Se algo falhar, reverte a transação
        $pdo->rollBack();
        error_log("Erro ao finalizar compra para cliente {$clienteId}: " . $e->getMessage());
        // Em um cenário real, você exibiria uma mensagem de erro ao usuário aqui
        // Para a avaliação, podemos apenas redirecionar para o carrinho com um erro
        header('Location: cart.php?error=purchase_failed');
        exit;
    }

    // Se a compra foi bem-sucedida, limpa o cookie e redireciona
    if ($sucessoCompra) {
        setcookie('cart', '', time() - 3600, '/'); // Limpa o cookie do carrinho
        // Redireciona para uma página de resumo do pedido conforme o requisito
        // Você precisará criar "order_summary.php" ou algo similar
        header('Location: order_summary.php?status=success'); // Exemplo de redirecionamento para o resumo
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Meu Carrinho</title>
     <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header class="header">
        <div class="logo">🎮 GameVerse</div>
        <nav>
            <a href="index.php">Loja</a>
            <?php if (isset($_SESSION['cliente'])): ?>
                <span>Olá, <?= htmlspecialchars($_SESSION['cliente']['nome']) ?></span>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Cadastrar</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="main-content">
        <h1>Seu Carrinho</h1>

        <?php if (isset($_GET['done'])): ?>
            <p style="color: green">Compra finalizada com sucesso!</p>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <p>O carrinho está vazio. <a href="index.php">Voltar para a loja</a></p>
        <?php else: ?>
            <div class="carrinho-lista">
                <?php
                $total = 0;
                foreach ($cart as $id => $quantidade):
                    if (!isset($produtos[$id])) continue;
                    $p = $produtos[$id];
                    $preco = $p['promocao'] ?? $p['preco'];
                    $subtotal = $preco * $quantidade;
                    $total += $subtotal;
                ?>
                    <div class="carrinho-item">
                        <img src="<?= $p['imagem'] ?>" alt="<?= $p['nome'] ?>">
                        <div class="info">
                            <h3><?= $p['nome'] ?></h3>
                            <p>Quantidade: <?= $quantidade ?></p>
                            <p>Preço unitário: R$ <?= number_format($preco, 2, ',', '.') ?></p>
                            <p><strong>Subtotal: R$ <?= number_format($subtotal, 2, ',', '.') ?></strong></p>
                            <a href="?remove=<?= $id ?>">Remover</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2>Total: R$ <?= number_format($total, 2, ',', '.') ?></h2>
            <a class="btn-finalizar" href="?finalizar=1" onclick="return confirm('Deseja finalizar a compra?')">Finalizar Compra</a>
        <?php endif; ?>
    </main>

    <footer class="footer">
        &copy; 2025 - GameVerse. Todos os direitos reservados.
    </footer>
</body>
</html>
