-- =============================================================================
-- UNIFIND - SISTEMA DE ACHADOS E PERDIDOS (MYSQL + REDIS)
-- Script de Inicialização e Criação do Banco Relacional (init.sql)
-- =============================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

CREATE DATABASE IF NOT EXISTS unifind_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE unifind_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS reivindicacoes;
DROP TABLE IF EXISTS historico_status;
DROP TABLE IF EXISTS itens;
DROP TABLE IF EXISTS locais;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. TABELA: usuarios
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    matricula VARCHAR(50) NULL,
    tipo ENUM('administrador', 'servidor', 'estudante') NOT NULL DEFAULT 'estudante',
    curso_departamento VARCHAR(150) NULL,
    telefone VARCHAR(30) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. TABELA: locais
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS locais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    bloco VARCHAR(100) NOT NULL,
    responsavel VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NULL,
    horario_funcionamento VARCHAR(100) NULL,
    capacidade_armario INT NOT NULL DEFAULT 30,
    ativo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. TABELA: itens
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    descricao TEXT NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    status ENUM('encontrado', 'reivindicado', 'devolvido', 'desativado') NOT NULL DEFAULT 'encontrado',
    valor_estimado DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    local_id INT NOT NULL,
    cadastrado_por_usuario_id INT NOT NULL,
    cor VARCHAR(60) NULL,
    marca VARCHAR(60) NULL,
    numero_serie VARCHAR(100) NULL,
    tamanho VARCHAR(20) NULL,
    tags TEXT NULL,
    desativado TINYINT(1) NOT NULL DEFAULT 0,
    data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_itens_local FOREIGN KEY (local_id) REFERENCES locais (id) ON UPDATE CASCADE,
    CONSTRAINT fk_itens_usuario FOREIGN KEY (cadastrado_por_usuario_id) REFERENCES usuarios (id) ON UPDATE CASCADE,
    INDEX idx_itens_status_categoria (status, categoria),
    INDEX idx_itens_data (data_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. TABELA: historico_status
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS historico_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    status ENUM('encontrado', 'reivindicado', 'devolvido', 'desativado') NOT NULL,
    usuario_id INT NOT NULL,
    observacao TEXT NULL,
    data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_historico_item FOREIGN KEY (item_id) REFERENCES itens (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_historico_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. TABELA: reivindicacoes
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reivindicacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    usuario_id INT NOT NULL,
    justificativa TEXT NOT NULL,
    status_reivindicacao ENUM('pendente_aprovacao', 'aprovada', 'rejeitada') NOT NULL DEFAULT 'pendente_aprovacao',
    data_reivindicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reivindicacoes_item FOREIGN KEY (item_id) REFERENCES itens (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reivindicacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- INSERÇÃO DE DADOS DE SEMENTE (SEED DATA)
-- =============================================================================

-- Inserção de Usuários
INSERT INTO usuarios (id, nome, email, matricula, tipo, curso_departamento, telefone, ativo, data_cadastro) VALUES
(1, 'Carlos Eduardo Silva', 'carlos.admin@uf.edu.br', 'ADM2024001', 'administrador', 'Administração Geral', '(48) 99111-2233', 1, '2024-01-15 08:00:00'),
(2, 'Ana Luíza Souza', 'ana.souza@aluno.uf.edu.br', '202301452', 'estudante', 'Ciência da Computação', '(48) 99888-1122', 1, '2024-02-01 10:30:00'),
(3, 'Lucas Gabriel Martins', 'lucas.martins@aluno.uf.edu.br', '202209871', 'estudante', 'Engenharia Elétrica', '(48) 99777-4455', 1, '2024-02-10 14:15:00'),
(4, 'Prof. Ricardo Oliveira', 'ricardo.oliveira@uf.edu.br', 'DOC1998042', 'servidor', 'Departamento de Informática', '(48) 99666-7788', 1, '2024-01-20 09:00:00'),
(5, 'Mariana Costa', 'mariana.costa@uf.edu.br', 'SER2019088', 'servidor', 'Secretaria de Segurança do Campus', '(48) 99555-9900', 1, '2024-01-22 11:20:00')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Inserção de Locais
INSERT INTO locais (id, nome, bloco, responsavel, telefone, horario_funcionamento, capacidade_armario, ativo) VALUES
(1, 'Biblioteca Central - Balcão de Atendimento', 'Bloco Central', 'Mariana Costa', '(48) 3721-9000', '07:30 - 22:00', 50, 1),
(2, 'Secretaria Acadêmica do Bloco A', 'Bloco A (Engenharias)', 'João Pereira', '(48) 3721-8500', '08:00 - 18:00', 30, 1),
(3, 'Restaurante Universitário (RU)', 'Praça Central', 'Fernanda Lima', '(48) 3721-7070', '11:00 - 14:00 | 17:30 - 20:00', 20, 1),
(4, 'Centro Esportivo / Ginásio', 'Bloco Esportes', 'Marcos Vinícius', '(48) 3721-6500', '07:00 - 21:00', 25, 1),
(5, 'Guarita Principal da Portaria', 'Entrada Norte', 'Equipe de Vigilância', '(48) 3721-5555', '24 horas', 40, 1)
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- Inserção de Itens
INSERT INTO itens (id, titulo, descricao, categoria, status, valor_estimado, local_id, cadastrado_por_usuario_id, cor, marca, numero_serie, tamanho, tags, desativado, data_registro) VALUES
(1, 'Notebook Dell Inspiron 15 Prata', 'Notebook deixado na bancada de estudos do 2º andar da biblioteca.', 'Eletrônicos', 'encontrado', 3500.00, 1, 5, 'Cinza Prata', 'Dell', 'SN-DELL-998822', NULL, 'notebook,dell,computador,prata', 0, '2026-08-01 14:30:00'),
(2, 'Carteira de Couro Preta com Documentos', 'Contém CNH e cartão de estudante em nome de Lucas Gabriel Martins.', 'Documentos', 'reivindicado', 50.00, 2, 1, 'Preta', 'Couro Fino', NULL, NULL, 'carteira,cnh,documentos,cartao', 0, '2026-08-02 10:00:00'),
(3, 'Garrafa Térmica Hydro Flask Azul 800ml', 'Garrafa azul com adesivos de programação embutidos.', 'Acessórios', 'devolvido', 120.00, 3, 5, 'Azul Marinho', 'Hydro Flask', NULL, NULL, 'garrafa,termica,azul,adesivos', 0, '2026-07-28 09:15:00'),
(4, 'Fone de Ouvido Sony WH-1000XM4 Preto', 'Fone de ouvido Bluetooth com cancelamento de ruído e estojo rígido.', 'Eletrônicos', 'encontrado', 1800.00, 1, 5, 'Preto Matte', 'Sony', 'SN-SONY-771122', NULL, 'fone,bluetooth,sony,headphone', 0, '2026-08-03 16:45:00'),
(5, 'Casaco de Frio Anorak Vermelho Nike', 'Casaco impermeável tamanho L esquecido na arquibancada.', 'Vestuário', 'encontrado', 250.00, 4, 1, 'Vermelho', 'Nike', NULL, 'L', 'casaco,jaqueta,vermelho,nike', 0, '2026-08-04 18:20:00'),
(6, 'Molho de Chaves com Chaveiro do Batman', 'Contém 3 chaves tetra e um chaveiro emborrachado do Batman.', 'Chaves', 'encontrado', 30.00, 5, 5, 'Preto e Amarelo', NULL, NULL, NULL, 'chaves,batman,chaveiro', 0, '2026-08-05 08:10:00'),
(7, 'Calculadora Científica Casio FX-991EX', 'Calculadora solar cinza com tampa de proteção traseira.', 'Eletrônicos', 'encontrado', 190.00, 2, 4, 'Cinza Escuro', 'Casio', NULL, NULL, 'calculadora,casio,engenharia', 0, '2026-08-05 13:00:00'),
(8, 'Óculos de Grau com Armação Ray-Ban Tartaruga', 'Óculos de grau em estojo de couro marrom.', 'Acessórios', 'encontrado', 600.00, 1, 5, 'Marrom Tartaruga', 'Ray-Ban', NULL, NULL, 'oculos,grau,rayban,estojo', 0, '2026-08-06 11:45:00'),
(9, 'Mochila JanSport Preta com Livros de Cálculo', 'Mochila contendo livro Cálculo Vol 1 Stewart e caderno espiral.', 'Material Escolar', 'reivindicado', 280.00, 2, 1, 'Preta', 'JanSport', NULL, NULL, 'mochila,jansport,livro,calculo', 0, '2026-08-06 15:30:00'),
(10, 'Apple Watch SE 44mm Alumínio Cinza', 'Relógio inteligente com pulseira de silicone preta.', 'Eletrônicos', 'encontrado', 2200.00, 4, 1, 'Cinza Espacial', 'Apple', 'SN-APPLE-334455', '44mm', 'apple,watch,relogio,smartwatch', 0, '2026-08-07 17:00:00'),
(11, 'Sombrinha Dobrável Automática Azul', 'Guarda-chuva compacto de cor azul marinho.', 'Acessórios', 'encontrado', 45.00, 3, 5, 'Azul', NULL, NULL, NULL, 'guarda-chuva,sombrinha,chuva', 0, '2026-08-08 12:00:00'),
(12, 'Caderno Inteligente A4 Capa Marmorizada', 'Caderno de discos com divisórias coloridas e anotações de Algoritmos.', 'Material Escolar', 'devolvido', 95.00, 2, 4, 'Marmorizado Rosa e Branco', 'Caderno Inteligente', NULL, 'A4', 'caderno,estudos,algoritmos', 0, '2026-07-25 14:00:00'),
(13, 'Carregador Anker USB-C 65W GaN', 'Carregador rápido preto de tomada com cabo trançado.', 'Eletrônicos', 'encontrado', 210.00, 1, 5, 'Preto', 'Anker', NULL, NULL, 'carregador,usbc,anker,fonte', 0, '2026-08-09 09:30:00'),
(14, 'Chave de Carro Fiat com Alarme Integrado', 'Chave canivete preta com logotipo Fiat.', 'Chaves', 'encontrado', 400.00, 5, 5, 'Preta', 'Fiat', NULL, NULL, 'chave,carro,fiat,alarme', 0, '2026-08-09 18:00:00'),
(15, 'Item Antigo Danificado - Pendrive Quebrado', 'Pendrive antigo com conector danificado sem uso.', 'Eletrônicos', 'desativado', 10.00, 1, 1, 'Azul', 'SanDisk', NULL, NULL, 'pendrive,danificado', 1, '2026-06-01 10:00:00')
ON DUPLICATE KEY UPDATE titulo = VALUES(titulo);

-- Inserção de Histórico de Status
INSERT INTO historico_status (id, item_id, status, usuario_id, observacao, data_registro) VALUES
(1, 1, 'encontrado', 5, 'Item entregue por um estudante na recepção da biblioteca.', '2026-08-01 14:30:00'),
(2, 2, 'encontrado', 1, 'Encontrado perto da sala A-104.', '2026-08-02 10:00:00'),
(3, 2, 'reivindicado', 3, 'Solicitação enviada pelo sistema aguardando retirada.', '2026-08-02 16:00:00'),
(4, 3, 'encontrado', 5, 'Esquecida no balcão de distribuição de pratos.', '2026-07-28 09:15:00'),
(5, 3, 'devolvido', 2, 'Entregue à aluna Ana Luíza após verificação dos adesivos.', '2026-07-29 11:00:00'),
(6, 4, 'encontrado', 5, 'Achado na sala de estudo silencioso 3.', '2026-08-03 16:45:00'),
(7, 5, 'encontrado', 1, 'Achado na arquibancada do ginásio após jogo de basquete.', '2026-08-04 18:20:00'),
(8, 6, 'encontrado', 5, 'Entregue pela segurança do estacionamento.', '2026-08-05 08:10:00'),
(9, 7, 'encontrado', 4, 'Esquecida no laboratório de física após prova.', '2026-08-05 13:00:00'),
(10, 8, 'encontrado', 5, 'Esquecido na mesa do acervo de periódicos.', '2026-08-06 11:45:00'),
(11, 9, 'encontrado', 1, 'Achada perto dos armários do bloco A.', '2026-08-06 15:30:00'),
(12, 9, 'reivindicado', 2, 'Solicitação registrada por Ana Luíza.', '2026-08-07 09:00:00'),
(13, 10, 'encontrado', 1, 'Encontrado no vestiário masculino do ginásio.', '2026-08-07 17:00:00'),
(14, 11, 'encontrado', 5, 'Deixada sob a mesa do RU após almoço de dia chuvoso.', '2026-08-08 12:00:00'),
(15, 12, 'encontrado', 4, 'Esquecido na sala A-201.', '2026-07-25 14:00:00'),
(16, 12, 'devolvido', 2, 'Devolvido para a aluna Ana Luíza.', '2026-07-26 10:00:00'),
(17, 13, 'encontrado', 5, 'Esquecido conectado na tomada do piso térreo.', '2026-08-09 09:30:00'),
(18, 14, 'encontrado', 5, 'Encontrada no chão do estacionamento E3.', '2026-08-09 18:00:00'),
(19, 15, 'encontrado', 1, 'Achado quebrado sem possibilidade de identificação.', '2026-06-01 10:00:00'),
(20, 15, 'desativado', 1, 'Desativação lógica efetuada por descartabilidade.', '2026-07-01 10:00:00')
ON DUPLICATE KEY UPDATE observacao = VALUES(observacao);

-- Inserção de Reivindicações
INSERT INTO reivindicacoes (id, item_id, usuario_id, justificativa, status_reivindicacao, data_reivindicacao) VALUES
(1, 2, 3, 'A carteira é minha, contém meu RG e carteirinha de estudante.', 'pendente_aprovacao', '2026-08-02 16:00:00'),
(2, 9, 2, 'Perdi minha mochila ontem à tarde com o livro anotado.', 'pendente_aprovacao', '2026-08-07 09:00:00')
ON DUPLICATE KEY UPDATE justificativa = VALUES(justificativa);
