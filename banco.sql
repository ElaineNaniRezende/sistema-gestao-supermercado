CREATE DATABASE IF NOT EXISTS mercado_pdv;
USE mercado_pdv;

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_barras VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    estoque INT NOT NULL
);

CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data_venda DATETIME DEFAULT CURRENT_TIMESTAMP,
    valor_total DECIMAL(10, 2) NOT NULL
);

CREATE TABLE itens_venda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venda_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (venda_id) REFERENCES vendas(id),
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);

-- Produtos de exemplo para testar o código de barras
INSERT INTO produtos (codigo_barras, nome, preco, estoque) VALUES
('7891000100103', 'Arroz Branco 5kg', 24.90, 50),
('7891000200202', 'Feijão Carioca 1kg', 8.50, 40),
('7891000300301', 'Óleo de Soja 900ml', 6.20, 30),
('7891000400400', 'Leite Integral 1L', 4.75, 60),
('101', 'Café Torrado 500g', 15.90, 25);