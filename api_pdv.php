<?php
header('Content-Type: application/json');
require 'conexao.php';

$acao = $_GET['acao'] ?? '';

// 1. Buscar produto pelo código de barras
if ($acao === 'buscar') {
    $codigo = $_GET['codigo'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo_barras = ?");
    $stmt->execute([$codigo]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($produto) {
        echo json_encode(['sucesso' => true, 'produto' => $produto]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Produto não encontrado!']);
    }
    exit;
}

// 2. Finalizar venda e dar baixa automática no estoque
if ($acao === 'finalizar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = json_decode(file_get_contents('php://input'), true);
    $itens = $dados['itens'] ?? [];
    $total = $dados['total'] ?? 0;

    if (empty($itens)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'O carrinho está vazio.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Registra a venda
        $stmtVenda = $pdo->prepare("INSERT INTO vendas (valor_total) VALUES (?)");
        $stmtVenda->execute([$total]);
        $vendaId = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND estoque >= ?");

        foreach ($itens as $item) {
            // Verifica e atualiza estoque
            $stmtEstoque->execute([$item['qtd'], $item['id'], $item['qtd']]);
            if ($stmtEstoque->rowCount() === 0) {
                throw new Exception("Estoque insuficiente para o produto: " . $item['nome']);
            }

            // Salva item da venda
            $stmtItem->execute([$vendaId, $item['id'], $item['qtd'], $item['preco']]);
        }

        $pdo->commit();
        echo json_encode(['sucesso' => true, 'venda_id' => $vendaId]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
    }
    exit;
}
?>