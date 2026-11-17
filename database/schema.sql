-- ============================================================
-- Meridiano Viagens — banco de dados
-- MySQL 8+ / MariaDB 10.4+
-- Execute este arquivo uma única vez para criar e popular o banco.
-- ============================================================

CREATE DATABASE IF NOT EXISTS meridiano
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE meridiano;

SET NAMES utf8mb4;
SET time_zone = '-03:00';

DROP VIEW IF EXISTS vw_reservas_financeiro;
DROP VIEW IF EXISTS vw_viagens_resumo;
DROP TABLE IF EXISTS pagamentos;
DROP TABLE IF EXISTS reservas;
DROP TABLE IF EXISTS viagens;
DROP TABLE IF EXISTS pacotes;
DROP TABLE IF EXISTS destinos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;

-- ------------------------------------------------------------
-- Equipe
-- ------------------------------------------------------------
CREATE TABLE usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(120) NOT NULL,
  email         VARCHAR(160) NOT NULL UNIQUE,
  senha_hash    VARCHAR(255) NOT NULL,
  perfil        ENUM('admin', 'operador') NOT NULL DEFAULT 'operador',
  ativo         TINYINT(1) NOT NULL DEFAULT 1,
  criado_em     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Clientes
-- ------------------------------------------------------------
CREATE TABLE clientes (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome             VARCHAR(160) NOT NULL,
  email            VARCHAR(160) NOT NULL UNIQUE,
  telefone         VARCHAR(24) NOT NULL,
  cpf              CHAR(11) NOT NULL UNIQUE,
  data_nascimento  DATE NULL,
  cidade           VARCHAR(100) NULL,
  estado           CHAR(2) NULL,
  observacoes      TEXT NULL,
  criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clientes_nome (nome)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Destinos
-- ------------------------------------------------------------
CREATE TABLE destinos (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(120) NOT NULL UNIQUE,
  pais           VARCHAR(80) NOT NULL DEFAULT 'Brasil',
  regiao         VARCHAR(80) NULL,
  descricao      TEXT NULL,
  imagem         VARCHAR(255) NULL,
  melhor_epoca   VARCHAR(100) NULL,
  ativo          TINYINT(1) NOT NULL DEFAULT 1,
  criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Pacotes turísticos
-- ------------------------------------------------------------
CREATE TABLE pacotes (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  destino_id     INT UNSIGNED NOT NULL,
  titulo         VARCHAR(160) NOT NULL,
  descricao      TEXT NULL,
  duracao_dias   TINYINT UNSIGNED NOT NULL,
  preco          DECIMAL(10,2) NOT NULL,
  inclusos       TEXT NULL,
  ativo          TINYINT(1) NOT NULL DEFAULT 1,
  criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pacotes_destino FOREIGN KEY (destino_id)
    REFERENCES destinos (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_pacotes_destino (destino_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Viagens (partidas de um pacote)
-- ------------------------------------------------------------
CREATE TABLE viagens (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pacote_id        INT UNSIGNED NOT NULL,
  data_ida         DATE NOT NULL,
  data_volta       DATE NOT NULL,
  capacidade       SMALLINT UNSIGNED NOT NULL,
  status           ENUM('planejada', 'confirmada', 'em_andamento', 'concluida', 'cancelada')
                   NOT NULL DEFAULT 'planejada',
  ponto_encontro   VARCHAR(180) NULL,
  observacoes      TEXT NULL,
  criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_viagens_pacote FOREIGN KEY (pacote_id)
    REFERENCES pacotes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_viagens_data (data_ida),
  INDEX idx_viagens_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Reservas
-- ------------------------------------------------------------
CREATE TABLE reservas (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo         VARCHAR(24) NOT NULL UNIQUE,
  cliente_id     INT UNSIGNED NOT NULL,
  viagem_id      INT UNSIGNED NOT NULL,
  data_reserva   DATE NOT NULL,
  viajantes      TINYINT UNSIGNED NOT NULL DEFAULT 1,
  valor_total    DECIMAL(10,2) NOT NULL,
  status         ENUM('pendente', 'confirmada', 'cancelada') NOT NULL DEFAULT 'pendente',
  observacoes    TEXT NULL,
  criado_em      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reservas_cliente FOREIGN KEY (cliente_id)
    REFERENCES clientes (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_reservas_viagem FOREIGN KEY (viagem_id)
    REFERENCES viagens (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_reservas_viagem (viagem_id),
  INDEX idx_reservas_cliente (cliente_id),
  INDEX idx_reservas_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Pagamentos
-- ------------------------------------------------------------
CREATE TABLE pagamentos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reserva_id       INT UNSIGNED NOT NULL,
  valor            DECIMAL(10,2) NOT NULL,
  forma            ENUM('pix', 'cartao', 'boleto', 'transferencia') NOT NULL DEFAULT 'pix',
  status           ENUM('pendente', 'pago', 'estornado') NOT NULL DEFAULT 'pago',
  vencimento       DATE NULL,
  data_pagamento   DATE NULL,
  observacoes      VARCHAR(255) NULL,
  criado_em        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pagamentos_reserva FOREIGN KEY (reserva_id)
    REFERENCES reservas (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_pagamentos_reserva (reserva_id),
  INDEX idx_pagamentos_status (status),
  INDEX idx_pagamentos_data (data_pagamento)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Views de apoio a relatórios
-- ------------------------------------------------------------
CREATE VIEW vw_reservas_financeiro AS
SELECT
  r.id,
  r.codigo,
  r.data_reserva,
  r.criado_em,
  r.viajantes,
  r.valor_total,
  r.status,
  r.observacoes,
  c.id AS cliente_id,
  c.nome AS cliente_nome,
  c.email AS cliente_email,
  c.telefone AS cliente_telefone,
  v.id AS viagem_id,
  v.data_ida,
  v.data_volta,
  v.status AS viagem_status,
  p.titulo AS pacote_titulo,
  p.preco AS pacote_preco,
  d.id AS destino_id,
  d.nome AS destino_nome,
  d.pais AS destino_pais,
  d.imagem AS destino_imagem,
  COALESCE(SUM(CASE WHEN pg.status = 'pago' THEN pg.valor ELSE 0 END), 0) AS valor_pago,
  COALESCE(SUM(CASE WHEN pg.status = 'pendente' THEN pg.valor ELSE 0 END), 0) AS valor_a_pagar,
  r.valor_total - COALESCE(SUM(CASE WHEN pg.status = 'pago' THEN pg.valor ELSE 0 END), 0) AS saldo
FROM reservas r
INNER JOIN clientes c ON c.id = r.cliente_id
INNER JOIN viagens v ON v.id = r.viagem_id
INNER JOIN pacotes p ON p.id = v.pacote_id
INNER JOIN destinos d ON d.id = p.destino_id
LEFT JOIN pagamentos pg ON pg.reserva_id = r.id
GROUP BY r.id;

CREATE VIEW vw_viagens_resumo AS
SELECT
  v.id,
  v.data_ida,
  v.data_volta,
  v.capacidade,
  v.status,
  v.ponto_encontro,
  v.observacoes,
  v.criado_em,
  p.id AS pacote_id,
  p.titulo AS pacote_titulo,
  p.preco AS pacote_preco,
  p.duracao_dias,
  d.id AS destino_id,
  d.nome AS destino_nome,
  d.pais AS destino_pais,
  d.imagem AS destino_imagem,
  COALESCE(SUM(CASE WHEN r.status IN ('pendente', 'confirmada') THEN r.viajantes ELSE 0 END), 0) AS lugares_ocupados,
  COALESCE(SUM(CASE WHEN r.status IN ('pendente', 'confirmada') THEN 1 ELSE 0 END), 0) AS total_reservas,
  COALESCE(SUM(CASE WHEN r.status IN ('pendente', 'confirmada') THEN r.valor_total ELSE 0 END), 0) AS receita_prevista
FROM viagens v
INNER JOIN pacotes p ON p.id = v.pacote_id
INNER JOIN destinos d ON d.id = p.destino_id
LEFT JOIN reservas r ON r.viagem_id = v.id
GROUP BY v.id;

-- ============================================================
-- DADOS INICIAIS
-- ============================================================

-- Senha de demonstração: meridiano123
INSERT INTO usuarios (id, nome, email, senha_hash, perfil) VALUES
(1, 'Marina Duarte', 'admin@meridiano.tur.br', '$2y$10$BUVO0A9acawU77a7o6V0OezUpHHnSY2fkcxfGNMyNjldU1RyAGCC6', 'admin');

INSERT INTO clientes (id, nome, email, telefone, cpf, data_nascimento, cidade, estado, observacoes, criado_em) VALUES
(1, 'Helena Aguiar', 'helena.aguiar@email.com', '11987654321', '10433218100', '1986-04-12', 'São Paulo', 'SP', 'Prefere assentos na janela e hotéis com academia.', DATE_SUB(NOW(), INTERVAL 14 MONTH)),
(2, 'Rafael Mendonça', 'rafael.mendonca@email.com', '21996541230', '96001338914', '1979-09-03', 'Rio de Janeiro', 'RJ', 'Viaja com frequência a trabalho.', DATE_SUB(NOW(), INTERVAL 12 MONTH)),
(3, 'Camila Ferraz', 'camila.ferraz@email.com', '31988776655', '08386379499', '1992-01-27', 'Belo Horizonte', 'MG', 'Alergia a frutos do mar.', DATE_SUB(NOW(), INTERVAL 11 MONTH)),
(4, 'Bruno Tavares', 'bruno.tavares@email.com', '41991234567', '02654235114', '1988-06-18', 'Curitiba', 'PR', NULL, DATE_SUB(NOW(), INTERVAL 10 MONTH)),
(5, 'Lívia Nakamura', 'livia.nakamura@email.com', '11993456781', '16155940789', '1995-11-08', 'São Paulo', 'SP', 'Lua de mel em Noronha.', DATE_SUB(NOW(), INTERVAL 9 MONTH)),
(6, 'Diego Salles', 'diego.salles@email.com', '51994561238', '81618495950', '1983-03-30', 'Porto Alegre', 'RS', NULL, DATE_SUB(NOW(), INTERVAL 8 MONTH)),
(7, 'Beatriz Lopes', 'beatriz.lopes@email.com', '62992345678', '31034131656', '1990-07-22', 'Goiânia', 'GO', 'Fotógrafa, pede dicas de trilhas.', DATE_SUB(NOW(), INTERVAL 7 MONTH)),
(8, 'Otávio Prado', 'otavio.prado@email.com', '11985551234', '47525534144', '1975-12-05', 'São Paulo', 'SP', 'Cliente corporativo.', DATE_SUB(NOW(), INTERVAL 6 MONTH)),
(9, 'Aline Rocha', 'aline.rocha@email.com', '71993334455', '92832764851', '1993-08-14', 'Salvador', 'BA', NULL, DATE_SUB(NOW(), INTERVAL 5 MONTH)),
(10, 'Henrique Vasconcelos', 'henrique.vasconcelos@email.com', '11991112233', '35030564160', '1969-02-19', 'Campinas', 'SP', 'Sempre solicita quarto silencioso.', DATE_SUB(NOW(), INTERVAL 4 MONTH)),
(11, 'Sofia Almeida', 'sofia.almeida@email.com', '21995558877', '39537672409', '1998-05-09', 'Niterói', 'RJ', NULL, DATE_SUB(NOW(), INTERVAL 3 MONTH)),
(12, 'Caio Bittencourt', 'caio.bittencourt@email.com', '48992221144', '23884969692', '1981-10-25', 'Florianópolis', 'SC', 'Interesse em pacotes de neve.', DATE_SUB(NOW(), INTERVAL 2 MONTH)),
(13, 'Mariana Pires', 'mariana.pires@email.com', '11996667788', '53287101269', '1987-09-16', 'São Paulo', 'SP', NULL, DATE_SUB(NOW(), INTERVAL 1 MONTH)),
(14, 'Thiago Nogueira', 'thiago.nogueira@email.com', '11994445566', '26916697857', '1991-04-02', 'Santo André', 'SP', 'Indicado pela cliente Helena Aguiar.', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(15, 'Isabela Fontes', 'isabela.fontes@email.com', '31993332211', '48018451427', '1994-12-11', 'Belo Horizonte', 'MG', NULL, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(16, 'Gustavo Ribas', 'gustavo.ribas@email.com', '11997778899', '62704828105', '1977-08-07', 'São Paulo', 'SP', 'Viaja com filho de 9 anos.', DATE_SUB(NOW(), INTERVAL 5 DAY));

INSERT INTO destinos (id, nome, pais, regiao, descricao, imagem, melhor_epoca, ativo) VALUES
(1, 'Rio de Janeiro', 'Brasil', 'Sudeste',
 'Entre o mar e a montanha, o Rio combina paisagens patrimônio da humanidade, bairros boêmios e uma hospitalidade que se reconhece de longe. Dias cheios de praia, mirantes e boa música.',
 'assets/img/destinos/rio.jpg', 'Abril a outubro', 1),
(2, 'Fernando de Noronha', 'Brasil', 'Nordeste',
 'Um arquipélago de águas transparentes, enseadas preservadas e vida marinha abundante. Noronha é para quem desacelera o ritmo e se entrega ao mar.',
 'assets/img/destinos/noronha.jpg', 'Agosto a novembro', 1),
(3, 'Salvador', 'Brasil', 'Bahia',
 'A primeira capital do Brasil guarda o Pelourinho, a música nas ruas e uma culinária que vale a viagem. O pôr do sol na Barra é parada obrigatória.',
 'assets/img/destinos/salvador.jpg', 'Setembro a março', 1),
(4, 'Gramado', 'Brasil', 'Serra Gaúcha',
 'Chocolates, flores, arquitetura europeia e noites frias. Gramado é o refúgio de inverno para casais, famílias e quem aprecia uma mesa farta.',
 'assets/img/destinos/gramado.jpg', 'Abril a setembro', 1),
(5, 'Bonito', 'Brasil', 'Mato Grosso do Sul',
 'Rios de água cristalina, grutas e flutuações em meio a peixes coloridos. O ecoturismo de Bonito é uma aula de respeito à natureza.',
 'assets/img/destinos/bonito.jpg', 'Maio a setembro', 1),
(6, 'Florianópolis', 'Brasil', 'Santa Catarina',
 'Quarenta e duas praias, trilhas, ostras e uma cena gastronômica que surpreende. A Ilha da Magia funciona para o verão agitado e para o descanso fora de temporada.',
 'assets/img/destinos/florianopolis.jpg', 'Dezembro a março', 1),
(7, 'Lisboa', 'Portugal', 'Europa',
 'Sete colinas, bondes amarelos e miradouros sobre o Tejo. Lisboa mistura história, azulejos e uma mesa generosa — e Sintra fica logo ali.',
 'assets/img/destinos/lisboa.jpg', 'Abril a outubro', 1),
(8, 'Bariloche', 'Argentina', 'Patagônia',
 'Lagos de origem glacial, florestas e picos cobertos de neve. No inverno, Bariloche é sinônimo de esqui; no verão, de trilhas e chocolate quente.',
 'assets/img/destinos/bariloche.jpg', 'Junho a setembro', 1);

INSERT INTO pacotes (id, destino_id, titulo, descricao, duracao_dias, preco, inclusos, ativo) VALUES
(1, 1, 'Rio Essencial', 'Quatro dias para conhecer o Cristo Redentor, o Pão de Açúcar e a orla de Copacabana com tempo para respirar.', 4, 2890.00, 'Aéreo ida e volta, hotel 4 estrelas com café, transfers e ingressos dos principais atrativos.', 1),
(2, 2, 'Noronha Inteira', 'Uma semana no arquipélago com passeios de barco, trilhas e tempo livre nas enseadas.', 7, 7450.00, 'Aéreo, pousada charmosa, café da manhã, transfer e dois passeios de barco.', 1),
(3, 3, 'Salvador Cultural', 'Cinco dias entre o Pelourinho, o Mercado Modelo e o pôr do sol da Barra.', 5, 3290.00, 'Aéreo, hotel no centro histórico, café da manhã, city tour e jantar típico.', 1),
(4, 4, 'Inverno em Gramado', 'Cinco dias de serra, chocolate, vinho e noites aconchegantes.', 5, 4180.00, 'Aéreo, hotel 4 estrelas, café da manhã, ingressos do Natal Luz e tour gastronômico.', 1),
(5, 5, 'Bonito Aventura', 'Seis dias de flutuação, grutas e trilhas em contato direto com a natureza.', 6, 5260.00, 'Aéreo, pousada, pensão completa, transfers e quatro passeios ecológicos.', 1),
(6, 6, 'Floripa Verão', 'Cinco dias para explorar praias do norte e do sul da ilha com ritmo de férias.', 5, 3980.00, 'Aéreo, hotel pé na areia, café da manhã e aluguel de carro por três dias.', 1),
(7, 7, 'Lisboa Clássica', 'Oito dias na capital portuguesa com day trip a Sintra e jantar de fado.', 8, 11900.00, 'Aéreo, hotel 4 estrelas, café da manhã, city tour, ingresso em Sintra e jantar de fado.', 1),
(8, 8, 'Bariloche Neve', 'Sete dias na Patagônia argentina para esquiar ou apenas contemplar a neve.', 7, 9450.00, 'Aéreo, hotel 4 estrelas, café da manhã, transfers e passe de esqui por dois dias.', 1),
(9, 1, 'Rio Réveillon', 'Cinco dias de Rio com réveillon em Copacabana e ceia à beira-mar.', 5, 8900.00, 'Aéreo, hotel 5 estrelas, café da manhã, ceia de réveillon e transfer.', 1),
(10, 2, 'Noronha Lua de Mel', 'Sete dias em pousada boutique com jantar romântico e passeio privativo.', 7, 12800.00, 'Aéreo, pousada boutique, café da manhã, passeio privativo e jantar para dois.', 1),
(11, 4, 'Serra Gaúcha Gastronômica', 'Quatro dias entre vinícolas, queijarias e restaurantes premiados da serra.', 4, 3650.00, 'Aéreo, hotel boutique, café da manhã, três experiências gastronômicas e transfer.', 1),
(12, 7, 'Lisboa & Sintra', 'Dez dias para viver Lisboa, Sintra e Cascais sem pressa.', 10, 14500.00, 'Aéreo, hotel 4 estrelas, café da manhã, passeios guiados, trem para Sintra e seguro viagem.', 1);

INSERT INTO viagens (id, pacote_id, data_ida, data_volta, capacidade, status, ponto_encontro, observacoes) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 18, 'confirmada', 'Aeroporto de Congonhas — balcão da Meridiano, 06h30', 'Grupo acompanhado pela guia Paula.'),
(2, 2, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 12, 'confirmada', 'Aeroporto de Guarulhos — Terminal 2, 07h10', 'Taxa ambiental de Noronha cobrada no destino.'),
(3, 4, DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), 20, 'planejada', 'Aeroporto de Guarulhos — Terminal 1, 08h00', 'Confirmar ingressos do Natal Luz até 10 dias antes.'),
(4, 3, DATE_ADD(CURDATE(), INTERVAL 32 DAY), DATE_ADD(CURDATE(), INTERVAL 36 DAY), 16, 'confirmada', 'Aeroporto de Congonhas — 05h50', NULL),
(5, 6, DATE_ADD(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 49 DAY), 20, 'planejada', 'Aeroporto de Guarulhos — 09h20', 'Sugerir locadora parceira.'),
(6, 7, DATE_ADD(CURDATE(), INTERVAL 58 DAY), DATE_ADD(CURDATE(), INTERVAL 65 DAY), 14, 'confirmada', 'Aeroporto de Guarulhos — Terminal 3, 20h15', 'Documentação em dia: passaporte válido por 6 meses.'),
(7, 5, DATE_ADD(CURDATE(), INTERVAL 72 DAY), DATE_ADD(CURDATE(), INTERVAL 77 DAY), 12, 'planejada', 'Aeroporto de Campo Grande — 10h40', NULL),
(8, 8, DATE_ADD(CURDATE(), INTERVAL 89 DAY), DATE_ADD(CURDATE(), INTERVAL 95 DAY), 14, 'planejada', 'Aeroporto de Guarulhos — 23h30', 'Aluguel de equipamento de neve opcional.'),
(9, 9, DATE_ADD(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 54 DAY), 16, 'confirmada', 'Aeroporto de Congonhas — 18h00', 'Ceia de réveillon confirmada no hotel.'),
(10, 10, DATE_ADD(CURDATE(), INTERVAL 110 DAY), DATE_ADD(CURDATE(), INTERVAL 116 DAY), 10, 'planejada', 'Aeroporto de Guarulhos — 07h00', NULL),
(11, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 17 DAY), 18, 'concluida', 'Aeroporto de Congonhas — 06h30', NULL),
(12, 3, DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 46 DAY), 16, 'concluida', 'Aeroporto de Guarulhos — 06h00', NULL),
(13, 11, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 13 DAY), 12, 'confirmada', 'Aeroporto de Guarulhos — 07h45', 'Menus especiais solicitados ao hotel.'),
(14, 12, DATE_ADD(CURDATE(), INTERVAL 130 DAY), DATE_ADD(CURDATE(), INTERVAL 139 DAY), 12, 'planejada', 'Aeroporto de Guarulhos — Terminal 3, 22h10', 'Visto Schengen não é necessário para turismo.'),
(15, 2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), 12, 'confirmada', 'Aeroporto de Guarulhos — Terminal 2, 07h10', 'Embarque próximo. Conferir documentação.'),
(16, 4, DATE_ADD(CURDATE(), INTERVAL 95 DAY), DATE_ADD(CURDATE(), INTERVAL 99 DAY), 20, 'cancelada', 'Aeroporto de Guarulhos — 08h00', 'Cancelada por indisponibilidade do hotel.'),
(17, 1, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 41 DAY), 18, 'concluida', 'Aeroporto de Congonhas — 06h30', NULL),
(18, 3, DATE_SUB(CURDATE(), INTERVAL 75 DAY), DATE_SUB(CURDATE(), INTERVAL 70 DAY), 16, 'concluida', 'Aeroporto de Guarulhos — 06h00', NULL),
(19, 4, DATE_SUB(CURDATE(), INTERVAL 120 DAY), DATE_SUB(CURDATE(), INTERVAL 115 DAY), 20, 'concluida', 'Aeroporto de Guarulhos — 08h00', NULL),
(20, 2, DATE_SUB(CURDATE(), INTERVAL 160 DAY), DATE_SUB(CURDATE(), INTERVAL 154 DAY), 12, 'concluida', 'Aeroporto de Guarulhos — Terminal 2, 07h10', NULL);

INSERT INTO reservas (id, codigo, cliente_id, viagem_id, data_reserva, viajantes, valor_total, status, observacoes) VALUES
(1, 'MRD-2026-0001', 1, 1, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 2, 5780.00, 'confirmada', 'Solicitou vista para o mar.'),
(2, 'MRD-2026-0002', 2, 1, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 4, 11560.00, 'confirmada', NULL),
(3, 'MRD-2026-0003', 3, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 3, 8670.00, 'pendente', 'Aguardando confirmação do grupo.'),
(4, 'MRD-2026-0004', 4, 1, DATE_SUB(CURDATE(), INTERVAL 9 DAY), 2, 5780.00, 'confirmada', NULL),
(5, 'MRD-2026-0005', 5, 2, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 2, 14900.00, 'confirmada', 'Lua de mel — quarto com varanda.'),
(6, 'MRD-2026-0006', 6, 2, DATE_SUB(CURDATE(), INTERVAL 26 DAY), 2, 14900.00, 'confirmada', NULL),
(7, 'MRD-2026-0007', 7, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 4, 29800.00, 'pendente', 'Grupo de fotógrafos.'),
(8, 'MRD-2026-0008', 8, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 3, 12540.00, 'confirmada', NULL),
(9, 'MRD-2026-0009', 9, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 4, 16720.00, 'pendente', NULL),
(10, 'MRD-2026-0010', 10, 3, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 3, 12540.00, 'confirmada', 'Quarto silencioso, andar alto.'),
(11, 'MRD-2026-0011', 11, 4, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 2, 6580.00, 'confirmada', NULL),
(12, 'MRD-2026-0012', 12, 4, DATE_SUB(CURDATE(), INTERVAL 17 DAY), 2, 6580.00, 'confirmada', NULL),
(13, 'MRD-2026-0013', 1, 4, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 3, 9870.00, 'pendente', 'Aguardando aprovação do pagamento.'),
(14, 'MRD-2026-0014', 2, 5, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 4, 15920.00, 'confirmada', NULL),
(15, 'MRD-2026-0015', 3, 5, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 2, 7960.00, 'pendente', NULL),
(16, 'MRD-2026-0016', 4, 6, DATE_SUB(CURDATE(), INTERVAL 35 DAY), 2, 23800.00, 'confirmada', 'Solicitou jantar de fado na chegada.'),
(17, 'MRD-2026-0017', 5, 6, DATE_SUB(CURDATE(), INTERVAL 33 DAY), 2, 23800.00, 'confirmada', NULL),
(18, 'MRD-2026-0018', 6, 6, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 3, 35700.00, 'pendente', NULL),
(19, 'MRD-2026-0019', 7, 8, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 2, 18900.00, 'confirmada', 'Interesse em aula de esqui.'),
(20, 'MRD-2026-0020', 8, 8, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 4, 37800.00, 'pendente', 'Grupo corporativo.'),
(21, 'MRD-2026-0021', 9, 9, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 4, 35600.00, 'confirmada', 'Mesa para quatro na ceia.'),
(22, 'MRD-2026-0022', 10, 9, DATE_SUB(CURDATE(), INTERVAL 38 DAY), 2, 17800.00, 'confirmada', NULL),
(23, 'MRD-2026-0023', 11, 9, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 4, 35600.00, 'pendente', NULL),
(24, 'MRD-2026-0024', 12, 13, DATE_SUB(CURDATE(), INTERVAL 14 DAY), 2, 7300.00, 'confirmada', 'Tour gastronômico com harmonização.'),
(25, 'MRD-2026-0025', 7, 13, DATE_SUB(CURDATE(), INTERVAL 11 DAY), 4, 14600.00, 'confirmada', NULL),
(26, 'MRD-2026-0026', 2, 13, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 3, 10950.00, 'pendente', NULL),
(27, 'MRD-2026-0027', 1, 15, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 2, 14900.00, 'confirmada', 'Confirmar transfer do aeroporto.'),
(28, 'MRD-2026-0028', 3, 15, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 3, 22350.00, 'confirmada', NULL),
(29, 'MRD-2026-0029', 5, 15, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 2, 14900.00, 'pendente', 'Aguardando pagamento restante.'),
(30, 'MRD-2026-0030', 5, 11, DATE_SUB(CURDATE(), INTERVAL 38 DAY), 4, 11560.00, 'confirmada', NULL),
(31, 'MRD-2026-0031', 6, 12, DATE_SUB(CURDATE(), INTERVAL 60 DAY), 2, 6580.00, 'confirmada', NULL),
(32, 'MRD-2026-0032', 8, 12, DATE_SUB(CURDATE(), INTERVAL 55 DAY), 3, 9870.00, 'confirmada', NULL),
(33, 'MRD-2026-0033', 9, 7, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 2, 10520.00, 'confirmada', 'Levar equipamento de flutuação próprio.'),
(34, 'MRD-2026-0034', 10, 10, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 2, 25600.00, 'pendente', 'Primeira parcela agendada.'),
(35, 'MRD-2026-0035', 2, 17, DATE_SUB(CURDATE(), INTERVAL 50 DAY), 4, 11560.00, 'confirmada', NULL),
(36, 'MRD-2026-0036', 6, 17, DATE_SUB(CURDATE(), INTERVAL 48 DAY), 2, 5780.00, 'confirmada', NULL),
(37, 'MRD-2026-0037', 9, 18, DATE_SUB(CURDATE(), INTERVAL 85 DAY), 3, 9870.00, 'confirmada', NULL),
(38, 'MRD-2026-0038', 11, 18, DATE_SUB(CURDATE(), INTERVAL 80 DAY), 4, 13160.00, 'confirmada', NULL),
(39, 'MRD-2026-0039', 4, 19, DATE_SUB(CURDATE(), INTERVAL 130 DAY), 2, 8360.00, 'confirmada', NULL),
(40, 'MRD-2026-0040', 7, 19, DATE_SUB(CURDATE(), INTERVAL 128 DAY), 2, 8360.00, 'confirmada', NULL),
(41, 'MRD-2026-0041', 5, 20, DATE_SUB(CURDATE(), INTERVAL 170 DAY), 2, 14900.00, 'confirmada', NULL),
(42, 'MRD-2026-0042', 12, 14, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 2, 29000.00, 'pendente', 'Cliente pediu cotação de upgrade.'),
(43, 'MRD-2026-0043', 8, 14, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 3, 43500.00, 'confirmada', 'Grupo corporativo com nota fiscal.');

-- Pagamentos de demonstração (valores parciais e integrais)
INSERT INTO pagamentos (reserva_id, valor, forma, status, vencimento, data_pagamento, observacoes) VALUES
(1, 3000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Sinal da reserva'),
(1, 2780.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Saldo em 3x'),
(2, 5780.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Primeira parcela'),
(2, 5780.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Segunda parcela'),
(3, 2000.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'Sinal combinado'),
(4, 5780.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 9 DAY), NULL),
(5, 7450.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'Sinal'),
(5, 7450.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Saldo'),
(6, 14900.00, 'transferencia', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 26 DAY), NULL),
(7, 5000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Sinal do grupo'),
(7, 12000.00, 'boleto', 'pendente', DATE_SUB(CURDATE(), INTERVAL 3 DAY), NULL, 'Boleto vencido — cobrar cliente'),
(8, 6270.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 8 DAY), '50% do pacote'),
(8, 6270.00, 'cartao', 'pendente', DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'Saldo no cartão'),
(9, 8000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Sinal'),
(9, 8720.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL, NULL),
(10, 12540.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 12 DAY), NULL),
(11, 6580.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 22 DAY), NULL),
(12, 3000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 17 DAY), 'Sinal'),
(12, 3580.00, 'boleto', 'pendente', DATE_SUB(CURDATE(), INTERVAL 1 DAY), NULL, 'Boleto vencido — cobrar cliente'),
(13, 4000.00, 'pix', 'pendente', DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'Aguardando pagamento'),
(14, 15920.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 15 DAY), NULL),
(15, 4000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Sinal'),
(15, 3960.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL, NULL),
(16, 11900.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'Sinal'),
(16, 11900.00, 'transferencia', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Saldo'),
(17, 23800.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 33 DAY), NULL),
(18, 15000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Sinal do grupo'),
(18, 20700.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 11 DAY), NULL, NULL),
(19, 18900.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 10 DAY), NULL),
(20, 18900.00, 'transferencia', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 4 DAY), '50% corporativo'),
(20, 18900.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, NULL),
(21, 17800.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Sinal'),
(21, 17800.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Saldo'),
(22, 17800.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 38 DAY), NULL),
(23, 10000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Sinal'),
(23, 25600.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL, NULL),
(24, 7300.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 14 DAY), NULL),
(25, 14600.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 11 DAY), NULL),
(26, 3000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Sinal'),
(26, 5000.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, NULL),
(27, 14900.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 25 DAY), NULL),
(28, 12000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Sinal'),
(28, 10350.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Saldo'),
(29, 4000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Sinal'),
(29, 10900.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 8 DAY), NULL, NULL),
(30, 11560.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 38 DAY), NULL),
(31, 6580.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 60 DAY), NULL),
(32, 9870.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 55 DAY), NULL),
(33, 5260.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 5 DAY), '50% do pacote'),
(33, 5260.00, 'boleto', 'pendente', DATE_ADD(CURDATE(), INTERVAL 20 DAY), NULL, NULL),
(34, 12800.00, 'pix', 'pendente', DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'Primeira parcela do casal'),
(35, 5780.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 60 DAY), NULL),
(35, 5780.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 46 DAY), NULL),
(36, 5780.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 48 DAY), NULL),
(37, 9870.00, 'transferencia', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 80 DAY), NULL),
(38, 6580.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 85 DAY), 'Sinal'),
(38, 6580.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 72 DAY), 'Saldo'),
(39, 8360.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 125 DAY), NULL),
(40, 4000.00, 'pix', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 126 DAY), 'Sinal'),
(40, 4360.00, 'boleto', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 118 DAY), 'Saldo'),
(41, 14900.00, 'cartao', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 155 DAY), NULL),
(42, 10000.00, 'pix', 'pendente', DATE_ADD(CURDATE(), INTERVAL 15 DAY), NULL, 'Sinal para bloqueio de vagas'),
(43, 21750.00, 'transferencia', 'pago', NULL, DATE_SUB(CURDATE(), INTERVAL 6 DAY), '50% corporativo'),
(43, 21750.00, 'boleto', 'pendente', DATE_SUB(CURDATE(), INTERVAL 2 DAY), NULL, 'Boleto vencido — contatar financeiro');
