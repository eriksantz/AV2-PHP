<?php
session_start();
require('includes/db.php'); // Inclua seu arquivo de conexão com o banco de dados

// Verifica se o usuário está logado
if (!isset($_SESSION['cliente']['id'])) {
    header('Location: login.php?redirect=order_summary.php');
    exit;
}

$clienteId = $_SESSION['cliente']['id'];
$clienteNome = htmlspecialchars($_SESSION['cliente']['nome']);
$pedidos = [];
$mensagem = '';
$tipoMensagem = '';

// Mensagem de sucesso da finalização da compra
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $mensagem = 'Sua compra foi realizada com sucesso!';
    $tipoMensagem = 'sucesso';
} elseif (isset($_GET['error']) && $_GET['error'] === 'purchase_failed') {
    $mensagem = 'Houve um problema ao finalizar sua compra. Por favor, tente novamente.';
    $tipoMensagem = 'erro';
}

try {
    // Consulta para buscar os pedidos do cliente usando JOINs
    // Juntamos 'carrinho_compras' com 'produtos' para obter detalhes do produto
    // E juntamos com 'clientes' para obter detalhes do cliente (embora o nome já esteja na sessão)
    $stmt = $pdo->prepare("
        SELECT
            cc.id AS compra_id,
            cc.quantidade,
            cc.preco_unitario_compra,
            cc.data_compra,
            p.nome AS produto_nome,
            p.imagem AS produto_imagem,
            c.nome AS cliente_nome,
            c.foto AS cliente_foto,
            c.pdf AS cliente_pdf
        FROM
            carrinho_compras cc
        JOIN
            produtos p ON cc.produto_id = p.id
        JOIN
            clientes c ON cc.cliente_id = c.id
        WHERE
            cc.cliente_id = ?
        ORDER BY
            cc.data_compra DESC, cc.id DESC
    ");
    $stmt->execute([$clienteId]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar pedidos por data de compra (se houver várias compras, para melhor visualização)
    $pedidosAgrupados = [];
    foreach ($pedidos as $pedido) {
        $dataCompra = (new DateTime($pedido['data_compra']))->format('d/m/Y H:i');
        if (!isset($pedidosAgrupados[$dataCompra])) {
            $pedidosAgrupados[$dataCompra] = [
                'cliente_nome' => $pedido['cliente_nome'],
                'cliente_foto' => $pedido['cliente_foto'],
                'cliente_pdf' => $pedido['cliente_pdf'],
                'itens' => []
            ];
        }
        $pedidosAgrupados[$dataCompra]['itens'][] = $pedido;
    }

} catch (PDOException $e) {
    $mensagem = 'Erro ao carregar seu histórico de pedidos.';
    $tipoMensagem = 'erro';
    error_log("Erro ao carregar pedidos para cliente {$clienteId}: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Resumo do Pedido</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        /* Estilos específicos para o resumo do pedido */
        .resumo-cliente {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            background-color: #2a475e;
            padding: 15px;
            border-radius: 8px;
            color: #c7d5e0;
        }
        .resumo-cliente img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #66c0f4;
        }
        .resumo-cliente h2 {
            margin: 0;
            color: #66c0f4;
        }
        .resumo-cliente p {
            margin: 5px 0;
        }
        .pedido-bloco {
            background-color: #1e1e2f;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.4);
        }
        .pedido-bloco h3 {
            color: #66c0f4;
            margin-top: 0;
            border-bottom: 1px solid #3a5c7c;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .pedido-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #2a475e;
            border-radius: 8px;
        }
        .pedido-item img {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            flex-shrink: 0;
        }
        .pedido-item-info {
            flex-grow: 1;
        }
        .pedido-item-info p {
            margin: 3px 0;
        }
        .total-pedido {
            font-size: 1.2em;
            font-weight: bold;
            color: #c7d5e0;
            text-align: right;
            margin-top: 15px;
        }
        .link-documento {
            display: inline-block;
            background-color: #4CAF50; /* Um verde amigável */
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            margin-left: 10px; /* Espaço do nome */
            font-size: 0.9em;
        }
        .link-documento:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo">🎮 GameVerse</div>
        <nav>
            <a href="index.php">Loja</a>
            <?php if (isset($_SESSION['cliente'])): ?>
                <span>Olá, <?= $clienteNome ?></span>
                <a href="logout.php">Sair</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Cadastrar</a>
            <?php endif; ?>
            <a href="cart.php">Carrinho 🛒</a>
        </nav>
    </header>

    <main class="main-content">
        <h1>Seus Pedidos</h1>

        <?php if ($mensagem): ?>
            <p class="<?= $tipoMensagem ?>"><?= htmlspecialchars($mensagem) ?></p>
        <?php endif; ?>

        <?php if (empty($pedidosAgrupados)): ?>
            <p>Você ainda não realizou nenhuma compra. <a href="index.php">Voltar para a loja</a></p>
        <?php else: ?>
            <?php
            // Exibe as informações do cliente apenas uma vez (podemos pegar do primeiro pedido)
            $primeiroPedido = reset($pedidosAgrupados); // Pega o primeiro elemento para ter os dados do cliente
            ?>
            <div class="resumo-cliente">
                <img src="uploads/photos/<?= htmlspecialchars($primeiroPedido['cliente_foto']) ?>" alt="Foto de Perfil">
                <div>
                    <h2>Histórico de Compras de <?= $primeiroPedido['cliente_nome'] ?></h2>
                    <p>Documento:
                        <a href="uploads/documents/<?= htmlspecialchars($primeiroPedido['cliente_pdf']) ?>" target="_blank" class="link-documento">Visualizar PDF</a>
                    </p>
                </div>
            </div>

            <?php foreach ($pedidosAgrupados as $dataCompra => $compraAgrupada): ?>
                <div class="pedido-bloco">
                    <h3>Compra realizada em: <?= $dataCompra ?></h3>
                    <?php
                    $totalCompraAtual = 0;
                    foreach ($compraAgrupada['itens'] as $item):
                        $totalItem = $item['preco_unitario_compra'] * $item['quantidade'];
                        $totalCompraAtual += $totalItem;
                    ?>
                        <div class="pedido-item">
                            <img src="<?= htmlspecialchars($item['produto_imagem']) ?>" alt="<?= htmlspecialchars($item['produto_nome']) ?>">
                            <div class="pedido-item-info">
                                <p><strong><?= htmlspecialchars($item['produto_nome']) ?></strong></p>
                                <p>Quantidade: <?= htmlspecialchars($item['quantidade']) ?></p>
                                <p>Preço Unitário: R$ <?= number_format($item['preco_unitario_compra'], 2, ',', '.') ?></p>
                                <p>Subtotal do Item: R$ <?= number_format($totalItem, 2, ',', '.') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="total-pedido">Total da Compra: R$ <?= number_format($totalCompraAtual, 2, ',', '.') ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 20px;">
            <a href="index.php">Voltar para a loja</a>
        </p>
    </main>

    <footer class="footer">
        &copy; 2025 - GameVerse. Todos os direitos reservados.
    </footer>
</body>
</html>