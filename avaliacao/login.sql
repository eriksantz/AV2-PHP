CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    senha VARCHAR(255), -- senha com hash
    foto VARCHAR(255),
    documento VARCHAR(255)
);
