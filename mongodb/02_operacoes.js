// =============================================================================
// TRABALHO PRÁTICO BANCO DE DADOS NoSQL - MONGODB & REDIS
// Cenário: UniFind - Sistema de Achados e Perdidos Campus Universitário
// Arquivo 02: Operações, Consultas, Aggregation Pipeline e Índices (02_operacoes.js)
// =============================================================================

db = db.getSiblingDB("unifind_db");

print("=====================================================================");
print("1. DEMONSTRAÇÃO DE OPERAÇÕES CRUD BÁSICAS");
print("=====================================================================");

// 1.1 INSERÇÃO (Create)
print("\n--- [CREATE] Inserindo um novo item achado ---");
const novoItem = {
  _id: new ObjectId("65c300000000000000000116"),
  titulo: "Teclado Mecânico Keychron K2 Wireless",
  descricao: "Teclado mecânico compacto RGB encontrado no laboratório LAMI 2.",
  categoria: "Eletrônicos",
  status: "encontrado",
  valor_estimado: 550.00,
  data_registro: new Date(),
  local_id: new ObjectId("65c200000000000000000002"), // Bloco A
  cadastrado_por_usuario_id: new ObjectId("65c100000000000000000001"),
  detalhes_item: {
    cor: "Cinza e Amarelo",
    marca: "Keychron",
    tags: ["teclado", "keychron", "mecanico", "bluetooth"]
  },
  historico_status: [
    {
      data: new Date(),
      status: "encontrado",
      usuario_id: new ObjectId("65c100000000000000000001"),
      observacao: "Achado sobre a bancada 14."
    }
  ],
  desativado: false
};
db.itens.insertOne(novoItem);
print("Novo item inserido com ID: " + novoItem._id);

// 1.2 CONSULTA (Read)
print("\n--- [READ] Consultando o item recém-inserido ---");
const itemConsultado = db.itens.findOne({ _id: novoItem._id });
printjson(itemConsultado);

// 1.3 ATUALIZAÇÃO (Update)
print("\n--- [UPDATE] Atualizando status de item e adicionando histórico ---");
db.itens.updateOne(
  { _id: novoItem._id },
  {
    $set: { status: "reivindicado" },
    $push: {
      historico_status: {
        data: new Date(),
        status: "reivindicado",
        usuario_id: new ObjectId("65c100000000000000000002"),
        observacao: "Aluno apresentou nota fiscal do produto."
      }
    }
  }
);
print("Item atualizado para status 'reivindicado'.");

// 1.4 DESATIVAÇÃO LÓGICA / EXCLUSÃO (Delete/Disable)
print("\n--- [DELETE LÓGICO] Desativando logicamente um item obsoleto ---");
db.itens.updateOne(
  { _id: new ObjectId("65c300000000000000000115") },
  { 
    $set: { 
      desativado: true,
      status: "desativado",
      data_desativacao: new Date()
    } 
  }
);
print("Item desativado logicamente com sucesso.");

print("\n=====================================================================");
print("2. QUATRO CONSULTAS COM OPERADORES DIFERENTES");
print("=====================================================================");

// CONSULTA 1: Operadores de Comparação ($gte, $lte)
// Encontrar todos os itens eletrônicos de alto valor (entre R$ 200,00 e R$ 4.000,00)
print("\n--- CONSULTA 1: Itens com valor_estimado entre R$ 200,00 e R$ 4.000,00 ($gte, $lte) ---");
const consultaValor = db.itens.find({
  valor_estimado: { $gte: 200.00, $lte: 4000.00 },
  desativado: false
}, { titulo: 1, categoria: 1, valor_estimado: 1, status: 1 }).toArray();
printjson(consultaValor);

// CONSULTA 2: Operador de Conjunto ($in)
// Filtrar itens das categorias 'Chaves' ou 'Documentos'
print("\n--- CONSULTA 2: Itens pertencentes às categorias 'Chaves' ou 'Documentos' ($in) ---");
const consultaCategorias = db.itens.find({
  categoria: { $in: ["Chaves", "Documentos"] },
  desativado: false
}, { titulo: 1, categoria: 1, status: 1, data_registro: 1 }).toArray();
printjson(consultaCategorias);

// CONSULTA 3: Expressão Regular / Busca Textual ($regex e $options)
// Buscar itens cujo título ou marca contenham a palavra 'Sony' ou 'Dell' (case-insensitive)
print("\n--- CONSULTA 3: Busca textual no título usando expressão regular ($regex) ---");
const consultaRegex = db.itens.find({
  $or: [
    { titulo: { $regex: "Dell|Sony|Apple", $options: "i" } },
    { "detalhes_item.marca": { $regex: "Dell|Sony|Apple", $options: "i" } }
  ],
  desativado: false
}, { titulo: 1, "detalhes_item.marca": 1, valor_estimado: 1 }).toArray();
printjson(consultaRegex);

// CONSULTA 4: Consulta em Documento Embutido ($elemMatch ou Notação Ponto)
// Buscar itens que possuem reivindicação pendente de aprovação
print("\n--- CONSULTA 4: Itens com solicitação de reivindicação pendente (Notação Ponto em Embutido) ---");
const consultaReivindicacoes = db.itens.find({
  "reivindicao.status_reivindicacao": "pendente_aprovacao",
  desativado: false
}, { titulo: 1, status: 1, reivindicao: 1 }).toArray();
printjson(consultaReivindicacoes);

print("\n=====================================================================");
print("3. AGGREGATION PIPELINE (4 ESTÁGIOS)");
print("=====================================================================");
/*
  JUSTIFICATIVA DO AGGREGATION PIPELINE:
  O objetivo desta agregação é gerar um relatório consolidado por categoria de itens.
  
  Estágios utilizados:
  1. $match: Filtra apenas objetos ativos (desativado: false).
  2. $group: Agrupa por categoria, contando a quantidade de itens, somando o valor total estimado e calculando o valor médio.
  3. $sort: Ordena os grupos pela quantidade total de itens de forma decrescente.
  4. $project: Formata os nomes das colunas e arredonda o valor médio para 2 casas decimais.
*/

print("\n--- AGGREGATION PIPELINE: Resumo Estatístico por Categoria de Itens ---");
const resultadoAgregacao = db.itens.aggregate([
  // Estágio 1: Filtrar apenas itens ativos
  { 
    $match: { desativado: false } 
  },
  
  // Estágio 2: Agrupar por Categoria e calcular métricas
  {
    $group: {
      _id: "$categoria",
      total_itens: { $sum: 1 },
      valor_total_estimado: { $sum: "$valor_estimado" },
      valor_medio: { $avg: "$valor_estimado" }
    }
  },
  
  // Estágio 3: Ordenar pelo total de itens (decrescente)
  { 
    $sort: { total_itens: -1 } 
  },
  
  // Estágio 4: Projetar e formatar o resultado final
  {
    $project: {
      _id: 0,
      categoria: "$_id",
      total_itens: 1,
      valor_total_estimado: 1,
      valor_medio_formatado: { $round: ["$valor_medio", 2] }
    }
  }
]).toArray();

printjson(resultadoAgregacao);

print("\n=====================================================================");
print("4. CRIAÇÃO E JUSTIFICATIVA DE ÍNDICES");
print("=====================================================================");
/*
  JUSTIFICATIVA DO ÍNDICE:
  A consulta mais frequente no sistema UniFind é a busca de itens por status (ex: apenas "encontrado")
  combinado com a categoria (ex: "Eletrônicos") e ordenados pela data de registro mais recente.
  
  Sem o índice composto { status: 1, categoria: 1, data_registro: -1 }, o MongoDB precisaria realizar
  um COLLSCAN (scan completo em toda a coleção) e em seguida uma ordenação em memória (In-Memory Sort).
  
  Com o índice composto:
  - O filtro de status + categoria é resolvido instantaneamente via árvore B-Tree (IXSCAN).
  - A ordenação por data já é entregue na ordem correta pelo índice, eliminando o custo de ordenação.
*/

print("\n--- Criando Índice Composto: { status: 1, categoria: 1, data_registro: -1 } ---");
db.itens.createIndex(
  { status: 1, categoria: 1, data_registro: -1 },
  { name: "idx_status_categoria_data" }
);

print("\n--- Lista de índices existentes na coleção 'itens' ---");
printjson(db.itens.getIndexes());

print("\n--- Demonstração do beneficio do índice usando explain() ---");
const explicacaoConsulta = db.itens.find({
  status: "encontrado",
  categoria: "Eletrônicos"
}).sort({ data_registro: -1 }).explain("executionStats");

print("Estágio de execução da consulta (deve ser IXSCAN): " + 
      explicacaoConsulta.executionStats.executionStages.stage);
print("Total de documentos examinados: " + 
      explicacaoConsulta.executionStats.totalDocsExamined);

print("\n=== OPERAÇÕES MONGODB CONCLUÍDAS COM SUCESSO! ===");
