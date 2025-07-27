-- SQL para criar a tabela de pedidos de equipamentos
CREATE TABLE pedidos_equipamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    atleta_id INT NOT NULL,
    tipo_equipamento VARCHAR(50) NOT NULL,
    tamanho VARCHAR(20),
    status VARCHAR(20) DEFAULT 'pendente',
    data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (atleta_id) REFERENCES users(id)
); 