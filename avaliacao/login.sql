CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    endereco TEXT NOT NULL,
    foto VARCHAR(255) NOT NULL,
    pdf VARCHAR(255) NOT NULL,
    tentativas_login_falhas INT DEFAULT 0,
    bloqueado_ate DATETIME NULL,
    reset_token VARCHAR(255) NULL,
    reset_expires_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
