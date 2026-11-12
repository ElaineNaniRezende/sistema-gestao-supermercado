<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDV - Frente de Caixa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; }
        .caixa-header { background: #1e293b; color: white; padding: 15px 25px; }
        .total-box { background: #0f172a; color: #38bdf8; border-radius: 10px; padding: 20px; }
        .cupom-lista { max-height: 420px; overflow-y: auto; }
    </style>
</head>
<body>

<div class="caixa-header d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">🛒 Frente de Caixa (PDV)</h4>
    <span class="badge bg-success fs-6">Caixa Aberto</span>
</div>

<div class="container-fluid px-4">
    <div class="row g-4">
        <!-- Coluna Esquerda: Leitura e Resumo -->
        <div class="col-md-5">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <label class="form-label fw-bold">Código de Barras / Código do Produto</label>
                    <div class="input-group input-group-lg mb-3">
                        <input type="text" id="codigoBarras" class="form-control" placeholder="Bipe ou digite o código e aperte Enter" autofocus>
                        <input type="number" id="quantidade" class="form-control" value="1" min="1" style="max-width: 90px;" title="Quantidade">
                        <button class="btn btn-primary" onclick="adicionarProduto()">Adicionar</button>
                    </div>
                    <small class="text-muted">Códigos teste: <b>101</b>, <b>7891000100103</b>, <b>7891000200202</b></small>
                    <div id="alertaErro" class="alert alert-danger mt-3 d-none"></div>
                </div>
            </div>

            <div class="total-box shadow-sm mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fs-5 text-light">TOTAL A PAGAR</span>
                    <span class="display-5 fw-bold" id="valorTotal">R$ 0,00</span>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-success btn-lg py-3 fw-bold" onclick="finalizarVenda()">
                    ✅ Finalizar Venda e Baixar Estoque (F2)
                </button>
                <button class="btn btn-outline-danger" onclick="cancelarVenda()">
                    Cancelar Venda
                </button>
            </div>
        </div>

        <!-- Coluna Direita: Cupom Fiscal / Itens -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold d-flex justify-content-between">
                    <span>Itens da Venda Atual</span>
                    <span id="contadorItens" class="text-muted">0 itens</span>
                </div>
                <div class="card-body p-0 cupom-lista">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Cód.</th>
                                <th>Produto</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Unit.</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-center">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="tabelaItens">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">Nenhum produto bipado ainda.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let carrinho = [];

    const inputCodigo = document.getElementById('codigoBarras');
    const inputQtd = document.getElementById('quantidade');
    const alertaErro = document.getElementById('alertaErro');

    // Permitir bipar com Enter
    inputCodigo.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') adicionarProduto();
    });

    // Atalho F2 para finalizar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F2') finalizarVenda();
    });

    function mostrarErro(msg) {
        alertaErro.textContent = msg;
        alertaErro.classList.remove('d-none');
        setTimeout(() => alertaErro.classList.add('d-none'), 3500);
    }

    async function adicionarProduto() {
        const codigo = inputCodigo.value.trim();
        const qtd = parseInt(inputQtd.value) || 1;

        if (!codigo) return;

        try {
            const resp = await fetch(`api_pdv.php?acao=buscar&codigo=${encodeURIComponent(codigo)}`);
            const data = await resp.json();

            if (!data.sucesso) {
                mostrarErro(data.mensagem);
                inputCodigo.select();
                return;
            }

            const prod = data.produto;
            const existente = carrinho.find(item => item.id === prod.id);
            const qtdTotalDesejada = (existente ? existente.qtd : 0) + qtd;

            if (qtdTotalDesejada > prod.estoque) {
                mostrarErro(`Estoque insuficiente! Disponível: ${prod.estoque} un.`);
                return;
            }

            if (existente) {
                existente.qtd += qtd;
            } else {
                carrinho.push({
                    id: prod.id,
                    codigo: prod.codigo_barras,
                    nome: prod.nome,
                    preco: parseFloat(prod.preco),
                    qtd: qtd
                });
            }

            inputCodigo.value = '';
            inputQtd.value = 1;
            inputCodigo.focus();
            atualizarTabela();

        } catch (err) {
            mostrarErro('Erro de comunicação com o servidor.');
        }
    }

    function removerItem(index) {
        carrinho.splice(index, 1);
        atualizarTabela();
    }

    function atualizarTabela() {
        const tbody = document.getElementById('tabelaItens');
        tbody.innerHTML = '';

        let total = 0;
        let totalItens = 0;

        if (carrinho.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Nenhum produto bipado ainda.</td></tr>';
        } else {
            carrinho.forEach((item, index) => {
                const subtotal = item.preco * item.qtd;
                total += subtotal;
                totalItens += item.qtd;

                tbody.innerHTML += `
                    <tr>
                        <td>${item.codigo}</td>
                        <td class="fw-semibold">${item.nome}</td>
                        <td class="text-center">${item.qtd}</td>
                        <td class="text-end">R$ ${item.preco.toFixed(2).replace('.', ',')}</td>
                        <td class="text-end fw-bold">R$ ${subtotal.toFixed(2).replace('.', ',')}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-danger" onclick="removerItem(${index})">X</button>
                        </td>
                    </tr>
                `;
            });
        }

        document.getElementById('valorTotal').textContent = `R$ ${total.toFixed(2).replace('.', ',')}`;
        document.getElementById('contadorItens').textContent = `${totalItens} item(ns)`;
    }

    async function finalizarVenda() {
        if (carrinho.length === 0) {
            mostrarErro('Adicione pelo menos um produto antes de finalizar.');
            return;
        }

        const total = carrinho.reduce((acc, item) => acc + (item.preco * item.qtd), 0);

        try {
            const resp = await fetch('api_pdv.php?acao=finalizar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ itens: carrinho, total: total })
            });
            const data = await resp.json();

            if (data.sucesso) {
                alert(`Venda #${data.venda_id} concluída com sucesso! Estoque atualizado.`);
                carrinho = [];
                atualizarTabela();
                inputCodigo.focus();
            } else {
                mostrarErro(data.mensagem);
            }
        } catch (err) {
            mostrarErro('Erro ao finalizar venda.');
        }
    }

    function cancelarVenda() {
        if (carrinho.length > 0 && confirm('Deseja limpar todos os itens da venda atual?')) {
            carrinho = [];
            atualizarTabela();
            inputCodigo.focus();
        }
    }
</script>

</body>
</html>