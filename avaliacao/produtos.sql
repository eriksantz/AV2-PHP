CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    preco DECIMAL(10, 2) NOT NULL,
    promocao DECIMAL(10, 2) NULL,
    imagem VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO produtos (nome, preco, promocao, imagem) VALUES
('Cyberpunk 2077', 129.90, 49.90, 'assets/cyberpunk.jpg'),
('GTA V', 39.90, NULL, 'assets/gta5.jpg'),
('Resident Evil 4 Remake', 59.90, NULL, 'assets/re4.jpg'),
('Kenshi', 29.90, NULL, 'assets/kenshi.jpg'),
('Ghost of Tsushima', 199.90, NULL, 'assets/ghost.jpg'),
('The Witcher 3', 32.30, NULL, 'assets/thewitcher.jpeg');
