<?php
session_start();
require_once __DIR__ . '/conexao.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'cadastrar') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $erro = 'Sua sessão expirou. Atualize a página e tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $precoTexto = str_replace(',', '.', trim($_POST['preco'] ?? ''));
        $codigo = trim($_POST['codigo_barras'] ?? '');
        $estoqueTexto = trim($_POST['estoque'] ?? '');

        if ($nome === '' || $codigo === '' || $precoTexto === '' || $estoqueTexto === '') {
            $erro = 'Preencha todos os campos.';
        } elseif (!is_numeric($precoTexto) || (float)$precoTexto < 0) {
            $erro = 'Informe um preço válido.';
        } elseif (filter_var($estoqueTexto, FILTER_VALIDATE_INT) === false || (int)$estoqueTexto < 0) {
            $erro = 'Informe uma estoque inteira igual ou maior que zero.';
        } else {
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos (nome, preco, codigo_barras, estoque)
                     VALUES (:nome, :preco, :codigo, :estoque)'
                );
                $stmt->execute([
                    ':nome' => $nome,
                    ':preco' => number_format((float)$precoTexto, 2, '.', ''),
                    ':codigo' => $codigo,
                    ':estoque' => (int)$estoqueTexto
                ]);

                $_SESSION['mensagem_sucesso'] = 'Produto cadastrado com sucesso!';
                header('Location: produtos.php');
                exit;
            } catch (PDOException $ex) {
                if ($ex->getCode() === '23000') {
                    $erro = 'Já existe um produto cadastrado com esse código de barras.';
                } else {
                    $erro = 'Não foi possível cadastrar o produto.';
                }
            }
        }
    }
}

if (isset($_SESSION['mensagem_sucesso'])) {
    $sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}

$termo = trim($_GET['busca'] ?? '');
$sql = 'SELECT id, nome, preco, codigo_barras, estoque FROM produtos';
$params = [];

if ($termo !== '') {
    $sql .= ' WHERE nome LIKE :busca OR codigo_barras LIKE :busca';
    $params[':busca'] = '%' . $termo . '%';
}
$sql .= ' ORDER BY nome ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$totalProdutos = (int)$pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
$totalUnidades = (int)$pdo->query('SELECT COALESCE(SUM(estoque), 0) FROM produtos')->fetchColumn();
$estoqueBaixo = (int)$pdo->query('SELECT COUNT(*) FROM produtos WHERE estoque BETWEEN 1 AND 5')->fetchColumn();
$estoqueZerado = (int)$pdo->query('SELECT COUNT(*) FROM produtos WHERE estoque = 0')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Produtos | Controle de Estoque</title>

    <!-- Mantém os arquivos locais do template SB Admin 2 -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body id="page-top">
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="produtos.php">
            <div class="sidebar-brand-icon"><i class="fas fa-boxes"></i></div>
            <div class="sidebar-brand-text mx-3">Controle <sup>Estoque</sup></div>
        </a>

        <hr class="sidebar-divider my-0">

        <li class="nav-item">
            <a class="nav-link" href="produtos.php">
                <i class="fas fa-fw fa-tachometer-alt"></i>
                <span>Visão geral</span>
            </a>
        </li>

        <hr class="sidebar-divider">
        <div class="sidebar-heading">Gerenciamento</div>

        <li class="nav-item active">
            <a class="nav-link" href="produtos.php">
                <i class="fas fa-fw fa-box"></i>
                <span>Produtos</span>
            </a>
        </li>

        <hr class="sidebar-divider d-none d-md-block">
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle"></button>
        </div>
    </ul>
    <!-- End Sidebar -->

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                    <i class="fa fa-bars"></i>
                </button>

                <h1 class="h6 mb-0 text-gray-800">Sistema de Controle de Estoque</h1>

                <ul class="navbar-nav ml-auto">
                    <li class="nav-item no-arrow">
                        <span class="nav-link text-gray-600">
                            <i class="fas fa-user-circle fa-fw"></i> Administrador
                        </span>
                    </li>
                </ul>
            </nav>
            <!-- End Topbar -->

            <div class="container-fluid">
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">Gerenciamento de Produtos</h1>
                        <p class="mb-0 text-muted">Cadastre produtos e acompanhe as estoques em estoque.</p>
                    </div>
                </div>

                <?php if ($erro): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= e($erro) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>
                <?php if ($sucesso): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= e($sucesso) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endif; ?>

                <!-- Cards de resumo -->
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Produtos cadastrados</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalProdutos"><?= $totalProdutos ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-boxes fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Unidades em estoque</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalUnidades"><?= $totalUnidades ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-cubes fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Estoque baixo (1 a 5)</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="estoqueBaixo"><?= $estoqueBaixo ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Estoque zerado</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="estoqueZerado"><?= $estoqueZerado ?></div>
                                    </div>
                                    <div class="col-auto"><i class="fas fa-times-circle fa-2x text-gray-300"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de cadastro -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle mr-1"></i> Cadastrar novo produto</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="produtos.php" autocomplete="off">
                            <input type="hidden" name="acao" value="cadastrar">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <div class="form-row">
                                <div class="form-group col-md-5">
                                    <label for="nome">Nome do produto</label>
                                    <input type="text" class="form-control" id="nome" name="nome" maxlength="150" required placeholder="Ex.: Teclado USB">
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="preco">Preço (R$)</label>
                                    <input type="number" class="form-control" id="preco" name="preco" min="0" step="0.01" required placeholder="0,00">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="codigo_barras">Código de barras</label>
                                    <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" maxlength="64" required placeholder="Código único">
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="estoque">Quantidade</label>
                                    <input type="number" class="form-control" id="estoque" name="estoque" min="0" step="1" required placeholder="0">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Cadastrar produto
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Tabela de produtos -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary mb-2 mb-md-0">Produtos cadastrados</h6>
                        <form method="GET" action="produtos.php" class="form-inline">
                            <input type="search" name="busca" value="<?= e($termo) ?>" class="form-control form-control-sm mr-2" placeholder="Nome ou código de barras">
                            <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-search"></i> Buscar</button>
                            <?php if ($termo !== ''): ?>
                                <a href="produtos.php" class="btn btn-light btn-sm ml-2">Limpar</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Produto</th>
                                        <th>Código de barras</th>
                                        <th>Preço</th>
                                        <th>Quantidade</th>
                                        <th>Situação</th>
                                    </tr>
                                </thead>
                                <tbody id="listaProdutos">
                                <?php if (!$produtos): ?>
                                    <tr><td colspan="5" class="text-center text-muted">Nenhum produto encontrado.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($produtos as $produto): ?>
                                        <tr>
                                            <td><?= e($produto['nome']) ?></td>
                                            <td><?= e($produto['codigo_barras']) ?></td>
                                            <td>R$ <?= number_format((float)$produto['preco'], 2, ',', '.') ?></td>
                                            <td><?= (int)$produto['estoque'] ?></td>
                                            <td>
                                                <?php if ((int)$produto['estoque'] === 0): ?>
                                                    <span class="badge badge-danger">Sem estoque</span>
                                                <?php elseif ((int)$produto['estoque'] <= 5): ?>
                                                    <span class="badge badge-warning">Estoque baixo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Disponível</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted"><i class="fas fa-sync-alt mr-1"></i> A listagem e os indicadores são atualizados automaticamente a cada 5 segundos.</small>
                    </div>
                </div>
            </div>
        </div>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Controle de Estoque &copy; <?= date('Y') ?></span>
                </div>
            </div>
        </footer>
    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<script>
// Atualiza a tabela e os indicadores sem recarregar a página.
const termoBusca = <?= json_encode($termo, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function atualizarEstoque() {
    const url = 'produtos_atualizar.php?busca=' + encodeURIComponent(termoBusca);

    fetch(url, { cache: 'no-store' })
        .then(response => {
            if (!response.ok) throw new Error('Falha ao consultar estoque');
            return response.json();
        })
        .then(dados => {
            document.getElementById('listaProdutos').innerHTML = dados.tabela;
            document.getElementById('totalProdutos').textContent = dados.totalProdutos;
            document.getElementById('totalUnidades').textContent = dados.totalUnidades;
            document.getElementById('estoqueBaixo').textContent = dados.estoqueBaixo;
            document.getElementById('estoqueZerado').textContent = dados.estoqueZerado;
        })
        .catch(erro => console.error(erro));
}

setInterval(atualizarEstoque, 5000);
</script>
</body>
</html>
