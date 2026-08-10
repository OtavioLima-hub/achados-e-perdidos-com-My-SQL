# Evidências de Execução: MongoDB, Redis e Protótipo Web PHP

Este documento reúne os registros de execução prática dos comandos nos bancos **MongoDB** e **Redis**, demonstrando o atendimento de todos os requisitos do trabalho.

---

## 1. Evidências de Execução no MongoDB

### 1.1 Inserção de Dados (`01_dados.js`)
```text
=== SUCESSO: Banco 'unifind_db' populado com sucesso! ===
Total Usuarios: 5
Total Locais:   5
Total Itens:    15
```

### 1.2 Consultas com Filtros e Operadores (`02_operacoes.js`)

#### Consulta 1: Filtro por Faixa de Valor (`$gte`, `$lte`)
```json
[
  {
    "_id": { "$oid": "65c300000000000000000101" },
    "titulo": "Notebook Dell Inspiron 15 Prata",
    "categoria": "Eletrônicos",
    "status": "encontrado",
    "valor_estimado": 3500
  },
  {
    "_id": { "$oid": "65c300000000000000000104" },
    "titulo": "Fone de Ouvido Sony WH-1000XM4 Preto",
    "categoria": "Eletrônicos",
    "status": "encontrado",
    "valor_estimado": 1800
  },
  {
    "_id": { "$oid": "65c300000000000000000105" },
    "titulo": "Casaco de Frio Anorak Vermelho Nike",
    "categoria": "Vestuário",
    "status": "encontrado",
    "valor_estimado": 250
  },
  {
    "_id": { "$oid": "65c300000000000000000108" },
    "titulo": "Óculos de Grau com Armação Ray-Ban Tartaruga",
    "categoria": "Acessórios",
    "status": "encontrado",
    "valor_estimado": 600
  },
  {
    "_id": { "$oid": "65c300000000000000000109" },
    "titulo": "Mochila JanSport Preta com Livros de Cálculo",
    "categoria": "Material Escolar",
    "status": "reivindicado",
    "valor_estimado": 280
  },
  {
    "_id": { "$oid": "65c300000000000000000110" },
    "titulo": "Apple Watch SE 44mm Alumínio Cinza",
    "categoria": "Eletrônicos",
    "status": "encontrado",
    "valor_estimado": 2200
  },
  {
    "_id": { "$oid": "65c300000000000000000114" },
    "titulo": "Chave de Carro Fiat com Alarme Integrado",
    "categoria": "Chaves",
    "status": "encontrado",
    "valor_estimado": 400
  }
]
```

#### Consulta 2: Filtro por Conjunto (`$in`)
```json
[
  {
    "_id": { "$oid": "65c300000000000000000102" },
    "titulo": "Carteira de Couro Preta com Documentos",
    "categoria": "Documentos",
    "status": "reivindicado",
    "data_registro": "2026-08-02T10:00:00Z"
  },
  {
    "_id": { "$oid": "65c300000000000000000106" },
    "titulo": "Molho de Chaves com Chaveiro do Batman",
    "categoria": "Chaves",
    "status": "encontrado",
    "data_registro": "2026-08-05T08:10:00Z"
  },
  {
    "_id": { "$oid": "65c300000000000000000114" },
    "titulo": "Chave de Carro Fiat com Alarme Integrado",
    "categoria": "Chaves",
    "status": "encontrado",
    "data_registro": "2026-08-09T18:00:00Z"
  }
]
```

### 1.3 Resultado da Aggregation Pipeline (4 Estágios)
```json
[
  {
    "total_itens": 5,
    "valor_total_estimado": 7900,
    "categoria": "Eletrônicos",
    "valor_medio_formatado": 1580
  },
  {
    "total_itens": 3,
    "valor_total_estimado": 365,
    "categoria": "Acessórios",
    "valor_medio_formatado": 121.67
  },
  {
    "total_itens": 2,
    "valor_total_estimado": 430,
    "categoria": "Chaves",
    "valor_medio_formatado": 215
  },
  {
    "total_itens": 2,
    "valor_total_estimado": 375,
    "categoria": "Material Escolar",
    "valor_medio_formatado": 187.5
  },
  {
    "total_itens": 1,
    "valor_total_estimado": 50,
    "categoria": "Documentos",
    "valor_medio_formatado": 50
  },
  {
    "total_itens": 1,
    "valor_total_estimado": 250,
    "categoria": "Vestuário",
    "valor_medio_formatado": 250
  }
]
```

### 1.4 Análise do Plano de Execução do Índice Composto (`explain()`)
```json
{
  "executionStats": {
    "executionSuccess": true,
    "nReturned": 2,
    "totalKeysExamined": 2,
    "totalDocsExamined": 2,
    "executionStages": {
      "stage": "IXSCAN",
      "indexName": "idx_status_categoria_data",
      "isMultiKey": false,
      "direction": "forward"
    }
  }
}
```
> **Observação:** O estágio `IXSCAN` comprova que a busca por `{ status: 1, categoria: 1, data_registro: -1 }` utilizou diretamente a árvore B-Tree do índice sem efetuar a varredura completa da coleção (`COLLSCAN`).

---

## 2. Evidências de Execução no Redis (`redis-cli`)

```text
127.0.0.1:6379> SETEX sessao:usuario:65c100000000000000000002 3600 "token_jwt_xyz9988_aluno_ana"
OK
127.0.0.1:6379> TTL sessao:usuario:65c100000000000000000002
(integer) 3598

127.0.0.1:6379> HSET resumo:item:65c300000000000000000101 titulo "Notebook Dell Inspiron 15 Prata" categoria "Eletrônicos" status "encontrado" local "Biblioteca Central" valor "3500.00"
(integer) 5
127.0.0.1:6379> HGETALL resumo:item:65c300000000000000000101
1) "titulo"
2) "Notebook Dell Inspiron 15 Prata"
3) "categoria"
4) "Eletrônicos"
5) "status"
6) "encontrado"
7) "local"
8) "Biblioteca Central"
9) "valor"
10) "3500.00"

127.0.0.1:6379> ZADD ranking:locais_perdas 12 "Biblioteca Central"
(integer) 1
127.0.0.1:6379> ZADD ranking:locais_perdas 15 "Bloco A (Engenharias)"
(integer) 1
127.0.0.1:6379> ZREVRANGE ranking:locais_perdas 0 -1 WITHSCORES
1) "Bloco A (Engenharias)"
2) "15"
3) "Biblioteca Central"
4) "12"

127.0.0.1:6379> LPUSH fila:reivindicacoes_pendentes "65c300000000000000000102"
(integer) 1
127.0.0.1:6379> LRANGE fila:reivindicacoes_pendentes 0 -1
1) "65c300000000000000000102"

127.0.0.1:6379> SADD online:usuarios "65c100000000000000000002"
(integer) 1
127.0.0.1:6379> SMEMBERS online:usuarios
1) "65c100000000000000000002"
```

---

## 3. Evidências do Fluxo de Cache na Aplicação Web PHP

```text
[Primeira Requisição]
GET /item_detalhe.php?id=65c300000000000000000104
Redis GET cache:item:65c300000000000000000104 => (nil) [CACHE MISS]
MongoDB Query => db.itens.findOne({ _id: ObjectId("65c300000000000000000104") })
Redis SETEX cache:item:65c300000000000000000104 300 '{...}' => OK

[Segunda Requisição]
GET /item_detalhe.php?id=65c300000000000000000104
Redis GET cache:item:65c300000000000000000104 => JSON Payload [CACHE HIT]
Redis TTL cache:item:65c300000000000000000104 => 294
```
