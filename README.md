# Achados e Perdidos IFMG (MongoDB + Redis + Protótipo Web PHP)

> **Trabalho Prático de Bancos de Dados NoSQL**  
> **Tema:** Sistema de Achados e Perdidos IFMG  
> **Tecnologias:** MongoDB (Documentos & Aggregations), Redis (Cache, Hashes, Sorted Sets, Lists, Sets, Sessões) e Protótipo Web em PHP com Docker Compose.

---

## 📖 Questionamentos & Explicações Técnicas das Estruturas NoSQL

### 1. O que é o Status do Cache? (Cache Hit vs Cache Miss)
- **Definição:** Mecanismo de alta velocidade para evitar consultas repetitivas ao banco de dados relacional ou de documentos (MongoDB).
- **Cache Miss:** Ocorre quando o sistema busca uma chave no Redis (ex: `GET cache:busca:md5`) e ela **não é encontrada** (retorna `nil`). O sistema precisa então consultar o **MongoDB**, recuperar os documentos e gravar uma cópia no Redis com expiração de tempo definida (`SETEX cache:busca:md5 120 JSON`).
- **Cache Hit:** Ocorre em consultas subsequentes quando a chave **está presente no Redis**. A resposta é entregue instantaneamente da memória RAM em sub-milissegundos, reduzindo drasticamente o consumo de CPU e I/O do servidor de banco principal.
- **Invalidação de Cache (`DEL`):** Sempre que um item tem seu status alterado (ex: ao ser reivindicado ou devolvido), o sistema executa `DEL cache:item:{id}` no Redis para garantir que a próxima consulta traga os dados atualizados do MongoDB.

---

### 2. O que é a Fila de Atendimento e Reivindicações? (Redis List)
- **Definição:** Estrutura do tipo **List** mantida no Redis sob a chave `fila:reivindicacoes_pendentes`.
- **Funcionamento (FIFO - First In, First Out):**
  - **Enfileiramento (`LPUSH`):** Quando um aluno solicita a devolução de um pertence na aplicação web, o ID da solicitação é inserido na extremidade esquerda da fila.
  - **Desembarque / Processamento (`RPOP`):** O administrador do sistema visualiza a fila por ordem de chegada no painel administrativo (`LRANGE`) e processa a devolução mais antiga removendo o elemento da extremidade direita (`RPOP`), atualizando simultaneamente o documento no MongoDB para o status "devolvido".

---

### 3. O que é o Ranking de Perdas por Local? (Redis Sorted Set)
- **Definição:** Estrutura de dados **Sorted Set (ZSET)** no Redis sob a chave `ranking:locais_perdas`.
- **Funcionamento:**
  - Diferente de conjuntos comuns, cada membro do Sorted Set possui uma pontuação numérica associada (`score`).
  - Cada vez que um novo objeto achado é cadastrado em um local do campus (ex: "Biblioteca Central" ou "Bloco A"), o sistema executa `ZINCRBY ranking:locais_perdas 1 "Local"`, incrementando automaticamente a pontuação.
  - A aplicação consulta instantaneamente o ranking dos locais mais críticos ordenados de forma decrescente através do comando `ZREVRANGE ranking:locais_perdas 0 -1 WITHSCORES`.

---

### 4. O que é o Sistema de Autenticação e Sessões no Redis? (Redis String + Set)
- **Definição:** Gerenciamento de sessões de acesso de usuários em memória via Redis.
- **Funcionamento:**
  - Ao realizar o login na página `login.php`, o sistema cria um registro na chave `sessao:usuario:{id}` via `SETEX` com TTL de **3600 segundos (1 hora)** contendo o token de acesso e dados do perfil.
  - O usuário é incluído no conjunto `online:usuarios` (`SADD`), permitindo a contagem e visualização de conexões ativas em tempo real (`SCARD` / `SMEMBERS`).
  - Ao fazer logout (`logout.php`), a chave `sessao:usuario:{id}` é deletada (`DEL`) e o usuário é removido do conjunto online (`SREM`).

---

## 📌 Visão Geral do Cenário

O **Achados e Perdidos IFMG** automatiza o controle de objetos achados e perdidos no campus universitário através da combinação sinérgica de dois bancos de dados NoSQL:

- **MongoDB (Dados Permanentes & Estruturados):** Armazena o cadastro completo de usuários, pontos de atendimento e os documentos detalhados de cada item (incluindo histórico embutido e reivindicações de posse).
- **Redis (Dados Temporários, Cache, Filas & Sessões):** Camada de alta velocidade para cache de consultas com TTL, resumos (Hashes), ranking em tempo real (Sorted Sets), fila FIFO de atendimento (Lists) e sessões de usuários ativas com controle de login.

---

## 🛠️ Estrutura de Arquivos da Entrega

```text
trabalho-backend/
├── README.md                 # Guia completo com questionamentos e explicações NoSQL
├── relatorio.md              # Relatório técnico resumido
├── slides.html               # Apresentação de slides interativa em HTML/CSS
├── docker-compose.yml        # Orquestrador dos contêineres PHP, MongoDB e Redis
├── Dockerfile                # Configuração da imagem PHP 8.2 + extensões PECL mongodb & redis
├── app/                      # Aplicação Web PHP
│   ├── config/db.php         # Conector único para MongoDB e Redis
│   ├── public/
│   │   ├── index.php         # Dashboard principal com busca no header, ranking e métricas
│   │   ├── login.php         # Tela de Login com sessões mantidas no Redis (TTL: 3600s)
│   │   ├── logout.php        # Encerramento de sessão no Redis
│   │   ├── item_detalhe.php  # Detalhes do item e formulário de reivindicação
│   │   ├── cadastrar_item.php# Formulário de cadastro de objetos
│   │   ├── admin_fila.php    # Gestão da fila de reivindicações Redis (LPUSH / RPOP)
│   │   └── css/style.css     # Estilização Preto e Verde (Design IFMG)
├── mongodb/
│   ├── 01_dados.js           # Script mongosh para criar e popular coleções
│   └── 02_operacoes.js       # Script mongosh com CRUD, 4 consultas, Aggregation e Índice
├── redis/
│   └── comandos_redis.txt    # Sequência de comandos redis-cli organizados para demonstração
└── evidencias/
    └── evidencias.md         # Registro de execução dos comandos e fluxos
```

---

## 🚀 Como Executar o Projeto

1. Certifique-se de que o **Docker Desktop** está em execução.
2. No terminal da raiz do projeto, execute:
   ```bash
   docker compose up -d
   ```
3. Acesse a aplicação no navegador:
   👉 **http://localhost:8000**

4. Para popular os dados no MongoDB (PowerShell):
   ```powershell
   Get-Content mongodb/01_dados.js | docker exec -i unifind_mongodb mongosh
   Get-Content mongodb/02_operacoes.js | docker exec -i unifind_mongodb mongosh
   ```
