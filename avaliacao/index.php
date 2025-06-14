<?php
session_start();
$produtos = [
        1 => ['nome' => 'Cyberpunk 2077', 'preco' => 129.90, 'promocao' => 49.90, 'imagem' => 'assets/cyberpunk.jpg'],
        2 => ['nome' => 'GTA V', 'preco' => 39.90, 'imagem' => 'assets/gta5.jpg'],
        3 => ['nome' => 'Resident Evil 4 Remake', 'preco' => 59.90, 'imagem' => 'assets/re4.jpg'],
        4 => ['nome' => 'Kenshi', 'preco' => 29.90, 'imagem' => 'assets/kenshi.jpg'],
        5 => ['nome' => 'Ghost of Tsushima', 'preco' => 199.90, 'imagem' => 'assets/ghost.jpg'],
        6 => ['nome' => 'The Witcher 3', 'preco' => 32.30, 'imagem' => 'assets/thewitcher.jpeg'] 
    ];
    

// Adiciona ao carrinho via cookie
if (isset($_GET['add'])) {
    $id = $_GET['add'];
    $cart = isset($_COOKIE['cart']) ? json_decode($_COOKIE['cart'], true) : [];
    $cart[$id] = ($cart[$id] ?? 0) + 1;
    setcookie('cart', json_encode($cart), time() + 3600, '/');
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minha Loja</title>
    <link rel="stylesheet" href="assets/styles.css"> <!-- Corrigido caminho -->
</head>
<body>
    <header class="header">
        <div class="logo">🎮 GameVerse</div>
        <nav>
            <?php if (isset($_SESSION['cliente'])): ?>
                <span>Olá, <?= htmlspecialchars($_SESSION['cliente']['nome']) ?></span>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Cadastrar</a>
            <?php endif; ?>
            <a href="cart.php">Carrinho 🛒</a>
        </nav>
    </header>

    <main class="main-content">
        <!-- Destaque -->
        <section class="destaque">
            <div class="destaque-info">
                <h2>🔥 Em Promoção: <?= $produtos[1]['nome'] ?></h2>
                <p><span class="original">R$ <?= number_format($produtos[1]['preco'], 2, ',', '.') ?></span> <span class="desconto">R$ <?= number_format($produtos[1]['promocao'], 2, ',', '.') ?></span></p>
                <p>Desconto imperdível por tempo limitado!</p>
                <a href="?add=1"> <button class="btn-add-to-cart">Adicionar ao carrinho</button> </a>
            </div>
            <div class="destaque-img">
                <img class="banner" src="<?= $produtos[1]['imagem'] ?>" alt="Banner do Jogo">
            </div>
        </section>

        <!-- Outros produtos -->
        <h2 class="section-title">Outros Jogos</h2>
        <div class="produtos">
            <?php foreach ($produtos as $id => $produto): if ($id == 1) continue; ?>
                <div class="produto-card">
                    <img class="thumb" src="<?= $produto['imagem'] ?>" alt="<?= $produto['nome'] ?>">
                    <h3><?= $produto['nome'] ?></h3>
                    <p>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                    <a href="?add=<?= $id ?>">
                     <button class="btn-add-to-cart">Adicionar ao carrinho</button></a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer class="footer">
        &copy; 2025 - GameVerse. Todos os direitos reservados.
    </footer>
</body>
</html>
