# Achados e Perdidos IFMG (MySQL + Redis + Aplicação Web PHP)

> **Sistema Integrado de Achados e Perdidos Campus Universitário (UniFind)**  
> **Tecnologias:** MySQL 8.0 (Relacional / PDO / Transações), Redis (Cache, Hashes, Sorted Sets, Lists, Sessões) e Aplicação Web em PHP 8.2 pronta para hospedagem online.

---

## 🗄️ Arquitetura do Banco de Dados Relacional (MySQL)

O sistema utiliza o **MySQL** como banco de dados principal permanente, estruturado com integridade referencial (`FOREIGN KEY`), restrições e índices para otimização de consultas:

```mermaid
erDiagram
    USUARIOS ||--o{ ITENS : "cadastra"
    USUARIOS ||--o{ REIVINDICACOES : "solicita"
    USUARIOS ||--o{ HISTORICO_STATUS : "registra"
    LOCAIS ||--o{ ITENS : "armazena"
    ITENS ||--o{ REIVINDICACOES : "possui"
    ITENS ||--o{ HISTORICO_STATUS : "possui"

    USUARIOS {
        int id PK
        string nome
        string email UK
        string matricula
        enum tipo
        string curso_departamento
        string telefone
        boolean ativo
        datetime data_cadastro
    }

    LOCAIS {
        int id PK
        string nome
        string bloco
        string responsavel
        string telefone
        string horario_funcionamento
        int capacidade_armario
        boolean ativo
    }

    ITENS {
        int id PK
        string titulo
        text descricao
        string categoria
        enum status
        decimal valor_estimado
        int local_id FK
        int cadastrado_por_usuario_id FK
        string cor
        string marca
        string numero_serie
        string tamanho
        text tags
        boolean desativado
        datetime data_registro
    }

    REIVINDICACOES {
        int id PK
        int item_id FK
        int usuario_id FK
        text justificativa
        enum status_reivindicacao
        datetime data_reivindicacao
    }

    HISTORICO_STATUS {
        int id PK
        int item_id FK
        enum status
        int usuario_id FK
        text observacao
        datetime data_registro
    }
```

---

## 🚀 Como Executar Localmente com Docker

1. Certifique-se de que o **Docker Desktop** está em execução.
2. Na raiz do projeto, execute o comando para construir e inicializar os contêineres:
   ```bash
   docker compose up --build -d
   ```
3. Acesse a aplicação no seu navegador:
   👉 **http://localhost:8000**

> O banco de dados MySQL e todos os dados de teste são criados e populados automaticamente pelo script `mysql/init.sql`.

---

## 🌐 Como Hospedar o Serviço Online (Guia Passo a Passo)

O sistema foi refatorado com **PDO** e variáveis de ambiente padronizadas para funcionar diretamente em qualquer provedor de nuvem ou hospedagem compartilhada.

### Opção 1: Hospedagem Gratuita/PaaS (ex: Railway / Render)

1. **Subir o Código no GitHub:**
   - Faça push deste repositório para a sua conta GitHub.
2. **Criar Banco de Dados MySQL na Nuvem:**
   - No **Railway** (ou **Render** / **Aiven** / **Clever Cloud**), adicione um serviço **MySQL**.
   - Importe o script `mysql/init.sql` no banco criado (pode usar o DBeaver, phpMyAdmin ou linha de comando).
3. **Implantar a Aplicação Web PHP:**
   - Adicione um novo serviço web conectado ao seu repositório GitHub.
   - Configure as variáveis de ambiente (*Environment Variables*):
     - `DB_HOST`: Host do MySQL fornecido pelo provedor
     - `DB_PORT`: `3306` (ou porta informada)
     - `DB_NAME`: `unifind_db` (ou nome do banco)
     - `DB_USER`: Usuário do banco
     - `DB_PASSWORD`: Senha do banco
     - *(Opcional)* `REDIS_HOST` e `REDIS_PORT` caso crie uma instância Redis.
   - *(Dica)* Você também pode simplesmente definir a variável `DATABASE_URL` no formato: `mysql://usuario:senha@host:porta/nomedobanco`.

### Opção 2: Hospedagem Compartilhada / cPanel (Hostinger, Locaweb, AlwaysData, etc.)

1. Acesse o **cPanel** ou painel de controle da sua hospedagem.
2. Vá em **Bancos de Dados MySQL** e crie uma nova base de dados e usuário.
3. Abra o **phpMyAdmin**, selecione a base criada e vá na aba **Importar**.
4. Selecione o arquivo `mysql/init.sql` e clique em **Executar**.
5. Envie os arquivos da pasta `app/` para a pasta pública do seu domínio (`public_html`).
6. Ajuste o arquivo `app/config/db.php` ou crie as variáveis no `.htaccess` / painel com as credenciais do seu banco.

---

## 🛠️ Estrutura do Projeto

```text
achados-e-perdidos/
├── mysql/
│   └── init.sql              # Script SQL com criação das tabelas e carga inicial
├── app/                      # Aplicação Web PHP 8.2
│   ├── config/db.php         # Camada de Conexão PDO (MySQL) e Redis
│   ├── public/
│   │   ├── index.php         # Dashboard principal com busca, categorias e ranking
│   │   ├── login.php         # Autenticação de usuários no MySQL
│   │   ├── logout.php        # Encerramento de sessão
│   │   ├── item_detalhe.php  # Detalhes, histórico e solicitação de reivindicação
│   │   ├── cadastrar_item.php# Formulário de cadastro de objetos achados
│   │   ├── admin_fila.php    # Painel administrativo da fila de devoluções
│   │   └── css/style.css     # Design System Preto e Verde (IFMG)
├── .env.example              # Modelo de variáveis de ambiente
├── Dockerfile                # Configuração da imagem PHP 8.2 com PDO MySQL e Redis
└── docker-compose.yml        # Orquestrador dos contêineres PHP (Apache), MySQL 8.0 e Redis
```

---

## 👥 Usuários Rápidos para Teste

Ao iniciar o sistema, você pode testar o login com qualquer um dos usuários de teste:

| Tipo | Nome | E-mail |
| :--- | :--- | :--- |
| **Administrador** | Carlos Eduardo Silva | `carlos.admin@uf.edu.br` |
| **Estudante** | Ana Luíza Souza | `ana.souza@aluno.uf.edu.br` |
| **Estudante** | Lucas Gabriel Martins | `lucas.martins@aluno.uf.edu.br` |
| **Servidor** | Mariana Costa | `mariana.costa@uf.edu.br` |
| **Professor** | Prof. Ricardo Oliveira | `ricardo.oliveira@uf.edu.br` |
