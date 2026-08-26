// =============================================================================
// TRABALHO PRÁTICO BANCO DE DADOS NoSQL - MONGODB & REDIS
// Cenário: UniFind - Sistema de Achados e Perdidos Campus Universitário
// Arquivo 01: Inserção Inicial de Dados (01_dados.js)
// =============================================================================

db = db.getSiblingDB("unifind_db");

// Limpeza inicial de coleções para garantia de reprodutibilidade
db.usuarios.drop();
db.locais.drop();
db.itens.drop();

// -----------------------------------------------------------------------------
// 1. COLEÇÃO: usuarios
// Contém o cadastro permanente de usuários do campus (estudantes, servidores, admins)
// -----------------------------------------------------------------------------
const userAdminId = new ObjectId("65c100000000000000000001");
const userAluno1Id = new ObjectId("65c100000000000000000002");
const userAluno2Id = new ObjectId("65c100000000000000000003");
const userProfId   = new ObjectId("65c100000000000000000004");
const userServId   = new ObjectId("65c100000000000000000005");

// Usuarios de inicialização 
db.usuarios.insertMany([
  {
    _id: userAdminId,
    nome: "Carlos Eduardo Silva",
    email: "carlos.admin@uf.edu.br",
    matricula: "ADM2024001",
    tipo: "administrador",
    telefone: "(48) 99111-2233",
    ativo: true,
    data_cadastro: new Date("2024-01-15T08:00:00Z")
  },
  {
    _id: userAluno1Id,
    nome: "Ana Luíza Souza",
    email: "ana.souza@aluno.uf.edu.br",
    matricula: "202301452",
    tipo: "estudante",
    curso: "Ciência da Computação",
    telefone: "(48) 99888-1122",
    ativo: true,
    data_cadastro: new Date("2024-02-01T10:30:00Z")
  },
  {
    _id: userAluno2Id,
    nome: "Lucas Gabriel Martins",
    email: "lucas.martins@aluno.uf.edu.br",
    matricula: "202209871",
    tipo: "estudante",
    curso: "Engenharia Elétrica",
    telefone: "(48) 99777-4455",
    ativo: true,
    data_cadastro: new Date("2024-02-10T14:15:00Z")
  },
  {
    _id: userProfId,
    nome: "Prof. Ricardo Oliveira",
    email: "ricardo.oliveira@uf.edu.br",
    matricula: "DOC1998042",
    tipo: "servidor",
    departamento: "Departamento de Informática",
    telefone: "(48) 99666-7788",
    ativo: true,
    data_cadastro: new Date("2024-01-20T09:00:00Z")
  },
  {
    _id: userServId,
    nome: "Mariana Costa",
    email: "mariana.costa@uf.edu.br",
    matricula: "SER2019088",
    tipo: "servidor",
    departamento: "Secretaria de Segurança do Campus",
    telefone: "(48) 99555-9900",
    ativo: true,
    data_cadastro: new Date("2024-01-22T11:20:00Z")
  }
]);

// -----------------------------------------------------------------------------
// 2. COLEÇÃO: locais
// Locais físicos de guarda e recebimento de objetos no campus
// -----------------------------------------------------------------------------
const localBiblioId  = new ObjectId("65c200000000000000000001");
const localBlocoAId  = new ObjectId("65c200000000000000000002");
const localRUId      = new ObjectId("65c200000000000000000003");
const localGinasioId = new ObjectId("65c200000000000000000004");
const localGuaritaId = new ObjectId("65c200000000000000000005");
// Locais padrão
db.locais.insertMany([
  {
    _id: localBiblioId,
    nome: "Biblioteca Central - Balcão de Atendimento",
    bloco: "Bloco Central",
    responsavel: "Mariana Costa",
    telefone: "(48) 3721-9000",
    horario_funcionamento: "07:30 - 22:00",
    capacidade_armario: 50,
    ativo: true
  },
  {
    _id: localBlocoAId,
    nome: "Secretaria Acadêmica do Bloco A",
    bloco: "Bloco A (Engenharias)",
    responsavel: "João Pereira",
    telefone: "(48) 3721-8500",
    horario_funcionamento: "08:00 - 18:00",
    capacidade_armario: 30,
    ativo: true
  },
  {
    _id: localRUId,
    nome: "Restaurante Universitário (RU)",
    bloco: "Praça Central",
    responsavel: "Fernanda Lima",
    telefone: "(48) 3721-7070",
    horario_funcionamento: "11:00 - 14:00 | 17:30 - 20:00",
    capacidade_armario: 20,
    ativo: true
  },
  {
    _id: localGinasioId,
    nome: "Centro Esportivo / Ginásio",
    bloco: "Bloco Esportes",
    responsavel: "Marcos Vinícius",
    telefone: "(48) 3721-6500",
    horario_funcionamento: "07:00 - 21:00",
    capacidade_armario: 25,
    ativo: true
  },
  {
    _id: localGuaritaId,
    nome: "Guarita Principal da Portaria",
    bloco: "Entrada Norte",
    responsavel: "Equipe de Vigilância",
    telefone: "(48) 3721-5555",
    horario_funcionamento: "24 horas",
    capacidade_armario: 40,
    ativo: true
  }
]);

// -----------------------------------------------------------------------------
// 3. COLEÇÃO: itens
// Objetos achados ou perdidos com documentos embutidos (detalhes, histórico, reivindicação)
// -----------------------------------------------------------------------------
db.itens.insertMany([
  {
    _id: new ObjectId("65c300000000000000000101"),
    titulo: "Notebook Dell Inspiron 15 Prata",
    descricao: "Notebook deixado na bancada de estudos do 2º andar da biblioteca.",
    categoria: "Eletrônicos",
    status: "encontrado",
    valor_estimado: 3500.00,
    data_registro: new Date("2026-08-01T14:30:00Z"),
    local_id: localBiblioId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Cinza Prata",
      marca: "Dell",
      numero_serie: "SN-DELL-998822",
      tags: ["notebook", "dell", "computador", "prata"]
    },
    historico_status: [
      {
        data: new Date("2026-08-01T14:30:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Item entregue por um estudante na recepção."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000102"),
    titulo: "Carteira de Couro Preta com Documentos",
    descricao: "Contém CNH e cartão de estudante em nome de Lucas Gabriel Martins.",
    categoria: "Documentos",
    status: "reivindicado",
    valor_estimado: 50.00,
    data_registro: new Date("2026-08-02T10:00:00Z"),
    local_id: localBlocoAId,
    cadastrado_por_usuario_id: userAdminId,
    detalhes_item: {
      cor: "Preta",
      marca: "Couro Fino",
      tags: ["carteira", "cnh", "documentos", "cartao"]
    },
    historico_status: [
      {
        data: new Date("2026-08-02T10:00:00Z"),
        status: "encontrado",
        usuario_id: userAdminId,
        observacao: "Encontrado perto da sala A-104."
      },
      {
        data: new Date("2026-08-02T16:00:00Z"),
        status: "reivindicado",
        usuario_id: userAluno2Id,
        observacao: "Solicitação enviada pelo sistema aguardando retirada."
      }
    ],
    reivindicao: {
      usuario_id: userAluno2Id,
      data_reivindicacao: new Date("2026-08-02T16:00:00Z"),
      justificativa: "A carteira é minha, contém meu RG e carteirinha de estudante.",
      status_reivindicacao: "pendente_aprovacao"
    },
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000103"),
    titulo: "Garrafa Térmica Hydro Flask Azul 800ml",
    descricao: "Garrafa azul com adesivos de programação embutidos.",
    categoria: "Acessórios",
    status: "devolvido",
    valor_estimado: 120.00,
    data_registro: new Date("2026-07-28T09:15:00Z"),
    local_id: localRUId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Azul Marinho",
      marca: "Hydro Flask",
      tags: ["garrafa", "termica", "azul", "adesivos"]
    },
    historico_status: [
      {
        data: new Date("2026-07-28T09:15:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Esquecida no balcão de distribuição de pratos."
      },
      {
        data: new Date("2026-07-29T11:00:00Z"),
        status: "devolvido",
        usuario_id: userAluno1Id,
        observacao: "Entregue à aluna Ana Luíza após verificação dos adesivos."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000104"),
    titulo: "Fone de Ouvido Sony WH-1000XM4 Preto",
    descricao: "Fone de ouvido Bluetooth com cancelamento de ruído e estojo rígido.",
    categoria: "Eletrônicos",
    status: "encontrado",
    valor_estimado: 1800.00,
    data_registro: new Date("2026-08-03T16:45:00Z"),
    local_id: localBiblioId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Preto Matte",
      marca: "Sony",
      numero_serie: "SN-SONY-771122",
      tags: ["fone", "bluetooth", "sony", "headphone"]
    },
    historico_status: [
      {
        data: new Date("2026-08-03T16:45:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Achado na sala de estudo silencioso 3."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000105"),
    titulo: "Casaco de Frio Anorak Vermelho Nike",
    descricao: "Casaco impermeável tamanho L esquecido na arquibancada.",
    categoria: "Vestuário",
    status: "encontrado",
    valor_estimado: 250.00,
    data_registro: new Date("2026-08-04T18:20:00Z"),
    local_id: localGinasioId,
    cadastrado_por_usuario_id: userAdminId,
    detalhes_item: {
      cor: "Vermelho",
      marca: "Nike",
      tamanho: "L",
      tags: ["casaco", "jaqueta", "vermelho", "nike"]
    },
    historico_status: [
      {
        data: new Date("2026-08-04T18:20:00Z"),
        status: "encontrado",
        usuario_id: userAdminId,
        observacao: "Achado na arquibancada do ginásio após jogo de basquete."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000106"),
    titulo: "Molho de Chaves com Chaveiro do Batman",
    descricao: "Contém 3 chaves tetra e um chaveiro emborrachado do Batman.",
    categoria: "Chaves",
    status: "encontrado",
    valor_estimado: 30.00,
    data_registro: new Date("2026-08-05T08:10:00Z"),
    local_id: localGuaritaId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Preto e Amarelo",
      tags: ["chaves", "batman", "chaveiro"]
    },
    historico_status: [
      {
        data: new Date("2026-08-05T08:10:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Entregue pela segurança do estacionamento."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000107"),
    titulo: "Calculadora Científica Casio FX-991EX",
    descricao: "Calculadora solar cinza com tampa de proteção traseira.",
    categoria: "Eletrônicos",
    status: "encontrado",
    valor_estimado: 190.00,
    data_registro: new Date("2026-08-05T13:00:00Z"),
    local_id: localBlocoAId,
    cadastrado_por_usuario_id: userProfId,
    detalhes_item: {
      cor: "Cinza Escuro",
      marca: "Casio",
      tags: ["calculadora", "casio", "engenharia"]
    },
    historico_status: [
      {
        data: new Date("2026-08-05T13:00:00Z"),
        status: "encontrado",
        usuario_id: userProfId,
        observacao: "Esquecida no laboratório de física após prova."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000108"),
    titulo: "Óculos de Grau com Armação Ray-Ban Tartaruga",
    descricao: "Óculos de grau em estojo de couro marrom.",
    categoria: "Acessórios",
    status: "encontrado",
    valor_estimado: 600.00,
    data_registro: new Date("2026-08-06T11:45:00Z"),
    local_id: localBiblioId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Marrom Tartaruga",
      marca: "Ray-Ban",
      tags: ["oculos", "grau", "rayban", "estojo"]
    },
    historico_status: [
      {
        data: new Date("2026-08-06T11:45:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Esquecido na mesa do acervo de periódicos."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000109"),
    titulo: "Mochila JanSport Preta com Livros de Cálculo",
    descricao: "Mochila contendo livro 'Cálculo Vol 1 Stewart' e caderno espiral.",
    categoria: "Material Escolar",
    status: "reivindicado",
    valor_estimado: 280.00,
    data_registro: new Date("2026-08-06T15:30:00Z"),
    local_id: localBlocoAId,
    cadastrado_por_usuario_id: userAdminId,
    detalhes_item: {
      cor: "Preta",
      marca: "JanSport",
      tags: ["mochila", "jansport", "livro", "calculo"]
    },
    historico_status: [
      {
        data: new Date("2026-08-06T15:30:00Z"),
        status: "encontrado",
        usuario_id: userAdminId,
        observacao: "Achada perto dos armários do bloco A."
      }
    ],
    reivindicao: {
      usuario_id: userAluno1Id,
      data_reivindicacao: new Date("2026-08-07T09:00:00Z"),
      justificativa: "Perdi minha mochila ontem à tarde com o livro anotado.",
      status_reivindicacao: "pendente_aprovacao"
    },
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000110"),
    titulo: "Apple Watch SE 44mm Alumínio Cinza",
    descricao: "Relógio inteligente com pulseira de silicone preta.",
    categoria: "Eletrônicos",
    status: "encontrado",
    valor_estimado: 2200.00,
    data_registro: new Date("2026-08-07T17:00:00Z"),
    local_id: localGinasioId,
    cadastrado_por_usuario_id: userAdminId,
    detalhes_item: {
      cor: "Cinza Espacial",
      marca: "Apple",
      numero_serie: "SN-APPLE-334455",
      tags: ["apple", "watch", "relogio", "smartwatch"]
    },
    historico_status: [
      {
        data: new Date("2026-08-07T17:00:00Z"),
        status: "encontrado",
        usuario_id: userAdminId,
        observacao: "Encontrado no vestiário masculino do ginásio."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000111"),
    titulo: "Sombrinha Dobrável Automática Azul",
    descricao: "Guarda-chuva compacto de cor azul marinho.",
    categoria: "Acessórios",
    status: "encontrado",
    valor_estimado: 45.00,
    data_registro: new Date("2026-08-08T12:00:00Z"),
    local_id: localRUId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Azul",
      tags: ["guarda-chuva", "sombrinha", "chuva"]
    },
    historico_status: [
      {
        data: new Date("2026-08-08T12:00:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Deixada sob a mesa do RU após almoço de dia chuvoso."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000112"),
    titulo: "Caderno Inteligente A4 Capa Marmorizada",
    descricao: "Caderno de discos com divisórias coloridas e anotações de Algoritmos.",
    categoria: "Material Escolar",
    status: "devolvido",
    valor_estimado: 95.00,
    data_registro: new Date("2026-07-25T14:00:00Z"),
    local_id: localBlocoAId,
    cadastrado_por_usuario_id: userProfId,
    detalhes_item: {
      cor: "Marmorizado Rosa e Branco",
      marca: "Caderno Inteligente",
      tags: ["caderno", "estudos", "algoritmos"]
    },
    historico_status: [
      {
        data: new Date("2026-07-25T14:00:00Z"),
        status: "encontrado",
        usuario_id: userProfId,
        observacao: "Esquecido na sala A-201."
      },
      {
        data: new Date("2026-07-26T10:00:00Z"),
        status: "devolvido",
        usuario_id: userAluno1Id,
        observacao: "Devolvido para a aluna Ana Luíza."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000113"),
    titulo: "Carregador Anker USB-C 65W GaN",
    descricao: "Carregador rápido preto de tomada com cabo trançado.",
    categoria: "Eletrônicos",
    status: "encontrado",
    valor_estimado: 210.00,
    data_registro: new Date("2026-08-09T09:30:00Z"),
    local_id: localBiblioId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Preto",
      marca: "Anker",
      tags: ["carregador", "usbc", "anker", "fonte"]
    },
    historico_status: [
      {
        data: new Date("2026-08-09T09:30:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Esquecido conectado na tomada do piso térreo."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000114"),
    titulo: "Chave de Carro Fiat com Alarme Integrado",
    descricao: "Chave canivete preta com logotipo Fiat.",
    categoria: "Chaves",
    status: "encontrado",
    valor_estimado: 400.00,
    data_registro: new Date("2026-08-09T18:00:00Z"),
    local_id: localGuaritaId,
    cadastrado_por_usuario_id: userServId,
    detalhes_item: {
      cor: "Preta",
      marca: "Fiat",
      tags: ["chave", "carro", "fiat", "alarme"]
    },
    historico_status: [
      {
        data: new Date("2026-08-09T18:00:00Z"),
        status: "encontrado",
        usuario_id: userServId,
        observacao: "Encontrada no chão do estacionamento E3."
      }
    ],
    desativado: false
  },
  {
    _id: new ObjectId("65c300000000000000000115"),
    titulo: "Item Antigo Danificado - Pendrive Quebrado",
    descricao: "Pendrive antigo com conector danificado sem uso.",
    categoria: "Eletrônicos",
    status: "desativado",
    valor_estimado: 10.00,
    data_registro: new Date("2026-06-01T10:00:00Z"),
    local_id: localBiblioId,
    cadastrado_por_usuario_id: userAdminId,
    detalhes_item: {
      cor: "Azul",
      marca: "SanDisk",
      tags: ["pendrive", "danificado"]
    },
    historico_status: [
      {
        data: new Date("2026-06-01T10:00:00Z"),
        status: "encontrado",
        usuario_id: userAdminId,
        observacao: "Achado quebrado sem possibilidade de identificação."
      },
      {
        data: new Date("2026-07-01T10:00:00Z"),
        status: "desativado",
        usuario_id: userAdminId,
        observacao: "Desativação lógica efetuada por descartabilidade."
      }
    ],
    desativado: true
  }
]);

print("=== SUCESSO: Banco 'unifind_db' populado com sucesso! ===");
print("Total Usuarios: " + db.usuarios.countDocuments());
print("Total Locais:   " + db.locais.countDocuments());
print("Total Itens:    " + db.itens.countDocuments());
