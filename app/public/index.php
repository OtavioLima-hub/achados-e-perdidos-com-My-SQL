<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - DASHBOARD PRINCIPAL (INDEX.PHP)
// =============================================================================
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once __DIR__ . '/../config/db.php';

$redis = Database::getRedis();

// Captura filtros da URL
$searchQuery = trim($_GET['q'] ?? '');
$categoriaFilter = trim($_GET['categoria'] ?? '');

$cacheKey = "";
$itemsList = [];

// Registra usuario logado no Redis (SET) se Redis estiver disponível
if ($redis && isset($_SESSION['user_name'])) {
    try {
        $redis->sAdd("online:usuarios", $_SESSION['user_name'] . " (" . ($_SESSION['user_tipo'] ?? 'estudante') . ")");
    } catch (Exception $e) {}
}

// -----------------------------------------------------------------------------
// CONSULTA DE ITENS COM MYSQL (E CACHE REDIS SE ATIVO)
// -----------------------------------------------------------------------------
if (!empty($searchQuery) || !empty($categoriaFilter)) {
    $cacheKey = "cache:busca:" . md5($searchQuery . "|" . $categoriaFilter);
    
    if ($redis && $redis->exists($cacheKey)) {
        $cachedData = json_decode($redis->get($cacheKey), true);
        $itemsList = $cachedData ?? [];
    } else {
        $where = ["i.desativado = 0"];
        $params = [];
        
        if (!empty($categoriaFilter)) {
            $where[] = "i.categoria = :categoria";
            $params[':categoria'] = $categoriaFilter;
        }
        if (!empty($searchQuery)) {
            $where[] = "(i.titulo LIKE :q1 OR i.descricao LIKE :q2 OR i.cor LIKE :q3 OR i.marca LIKE :q4 OR i.tags LIKE :q5)";
            $params[':q1'] = "%{$searchQuery}%";
            $params[':q2'] = "%{$searchQuery}%";
            $params[':q3'] = "%{$searchQuery}%";
            $params[':q4'] = "%{$searchQuery}%";
            $params[':q5'] = "%{$searchQuery}%";
        }
        
        $whereSql = implode(' AND ', $where);
        $sql = "SELECT i.*, l.nome AS local_nome 
                FROM itens i 
                LEFT JOIN locais l ON i.local_id = l.id 
                WHERE {$whereSql} 
                ORDER BY i.data_registro DESC 
                LIMIT 24";
        
        $rawRows = dbFetchAll($sql, $params);
        
        $itemsList = [];
        foreach ($rawRows as $row) {
            $itemsList[] = [
                'id' => (string)$row['id'],
                'titulo' => $row['titulo'] ?? '',
                'descricao' => $row['descricao'] ?? '',
                'categoria' => $row['categoria'] ?? '',
                'status' => $row['status'] ?? 'encontrado',
                'valor_estimado' => (float)($row['valor_estimado'] ?? 0),
                'local_nome' => $row['local_nome'] ?? 'Campus IFMG',
                'data_registro' => !empty($row['data_registro']) ? date('d/m/Y H:i', strtotime($row['data_registro'])) : 'Recente'
            ];
        }
        
        if ($redis && !empty($cacheKey)) {
            try {
                $redis->setex($cacheKey, 120, json_encode($itemsList));
            } catch (Exception $e) {}
        }
    }
} else {
    // Listagem geral
    $sql = "SELECT i.*, l.nome AS local_nome 
            FROM itens i 
            LEFT JOIN locais l ON i.local_id = l.id 
            WHERE i.desativado = 0 
            ORDER BY i.data_registro DESC 
            LIMIT 12";
    
    $rawRows = dbFetchAll($sql);
    
    foreach ($rawRows as $row) {
        $itemsList[] = [
            'id' => (string)$row['id'],
            'titulo' => $row['titulo'] ?? '',
            'descricao' => $row['descricao'] ?? '',
            'categoria' => $row['categoria'] ?? '',
            'status' => $row['status'] ?? 'encontrado',
            'valor_estimado' => (float)($row['valor_estimado'] ?? 0),
            'local_nome' => $row['local_nome'] ?? 'Campus IFMG',
            'data_registro' => !empty($row['data_registro']) ? date('d/m/Y H:i', strtotime($row['data_registro'])) : 'Recente'
        ];
    }
}

// Total de itens ativos no MySQL
$countRow = dbFetchOne("SELECT COUNT(*) as total FROM itens WHERE desativado = 0");
$totalItensCadastrados = (int)($countRow['total'] ?? count($itemsList));

// -----------------------------------------------------------------------------
// METRICAS DO REDIS (COM FALLBACK MYSQL SE REDIS ESTIVER OFFLINE)
// -----------------------------------------------------------------------------
$rankingLocais = [];
$totalFila = 0;
$totalOnline = 0;

if ($redis) {
    try {
        $rankingLocais = $redis->zRevRange("ranking:locais_perdas", 0, 4, true);
        $totalFila = $redis->lLen("fila:reivindicacoes_pendentes");
        $totalOnline = $redis->sCard("online:usuarios");
    } catch (Exception $e) {}
}

// Fallback caso Redis não tenha ranking ou esteja sem dados: carrega dos locais com mais itens
if (empty($rankingLocais)) {
    $locaisStats = dbFetchAll("SELECT l.nome, COUNT(i.id) as total 
                              FROM locais l 
                              JOIN itens i ON i.local_id = l.id 
                              WHERE i.desativado = 0 
                              GROUP BY l.id, l.nome 
                              ORDER BY total DESC 
                              LIMIT 5");
    foreach ($locaisStats as $l) {
        $rankingLocais[$l['nome']] = (int)$l['total'];
    }
}

// Fallback de contagem da fila caso Redis não esteja com ela populada
if ($totalFila === 0) {
    $filaRow = dbFetchOne("SELECT COUNT(*) as total FROM reivindicacoes WHERE status_reivindicacao = 'pendente_aprovacao'");
    $totalFila = (int)($filaRow['total'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achados e Perdidos IFMG | MySQL & Redis</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

    <!-- Header Navigation com Busca e Autenticação -->
    <header class="header-nav">
        <div class="nav-container">
            <a href="index.php" class="logo-group">
                <img src="https://www.ifmg.edu.br/portal/imagens/logovertical.jpg" alt="Logo IFMG" class="logo-img">
                <div class="logo-text">Achados e Perdidos</div>
            </a>

            <!-- Campo de Busca Integrado no Header -->
            <form method="GET" action="index.php" class="header-search">
                <input type="text" name="q" class="input-field" placeholder="Buscar por objeto..." value="<?= htmlspecialchars($searchQuery, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                <select name="categoria" class="input-field" style="max-width: 150px;">
                    <option value="">Categorias</option>
                    <option value="Eletrônicos" <?= $categoriaFilter === 'Eletrônicos' ? 'selected' : '' ?>>Eletrônicos</option>
                    <option value="Documentos" <?= $categoriaFilter === 'Documentos' ? 'selected' : '' ?>>Documentos</option>
                    <option value="Acessórios" <?= $categoriaFilter === 'Acessórios' ? 'selected' : '' ?>>Acessórios</option>
                    <option value="Material Escolar" <?= $categoriaFilter === 'Material Escolar' ? 'selected' : '' ?>>Material Escolar</option>
                    <option value="Chaves" <?= $categoriaFilter === 'Chaves' ? 'selected' : '' ?>>Chaves</option>
                    <option value="Vestuário" <?= $categoriaFilter === 'Vestuário' ? 'selected' : '' ?>>Vestuário</option>
                </select>
                <button type="submit" class="btn-primary">Buscar</button>
            </form>

            <ul class="nav-links">
                <li><a href="index.php" class="nav-link active">Início</a></li>
                <li><a href="admin_fila.php" class="nav-link">Fila (<?= $totalFila ?>)</a></li>
                <li><a href="cadastrar_item.php" class="btn-primary">+ Cadastrar Item</a></li>
                
                <!-- Sistema de Login no Header -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li style="display: flex; align-items: center; gap: 0.5rem; margin-left: 0.5rem;">
                        <span style="font-size: 0.85rem; color: var(--neon-green); font-weight: 700;">
                            <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </span>
                        <a href="logout.php" class="nav-link" style="color: var(--text-muted); font-size: 0.8rem; padding: 0.3rem 0.6rem;">Sair</a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link" style="border: 1px solid var(--border-glass);">Entrar / Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="container">

        <!-- Hero Section -->
        <section class="hero-section">
            <h1 class="hero-title">Achados e Perdidos IFMG</h1>
            <p class="hero-desc">
                Sistema centralizado para cadastro, localização e devolução de pertences perdidos no campus com persistência em <strong>MySQL</strong> e aceleração em tempo real.
            </p>
        </section>

        <!-- Stats Grid -->
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Itens Cadastrados</span>
                </div>
                <div class="stat-value"><?= $totalItensCadastrados ?></div>
                <div class="stat-explanation">Total de objetos ativos registrados no banco de dados relacional MySQL.</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Fila de Atendimento</span>
                </div>
                <div class="stat-value"><?= $totalFila ?></div>
                <div class="stat-explanation">Solicitações de devolução aguardando validação e entrega pelo administrador.</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-label">Usuários Ativos / Online:</span>
                </div>
                <div class="stat-value"><?= $totalOnline > 0 ? $totalOnline : '1' ?></div>
                <div class="stat-explanation">Controle de conexões e sessões de usuários ativas no sistema.</div>
            </div>
        </div>

        <!-- Layout Grid: Main Cards & Sidebar Ranking -->
        <div class="layout-grid">
            
            <!-- Items Grid -->
            <div>
                <h2 style="font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 1.25rem; color: #ffffff;">Objetos em Destaque</h2>
                <div class="cards-grid">
                    <?php if (!empty($itemsList)): ?>
                        <?php foreach ($itemsList as $item): ?>
                            <div class="item-card">
                                <div>
                                    <div class="item-category"><?= htmlspecialchars($item['categoria'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
                                    <h3 class="item-title"><?= htmlspecialchars($item['titulo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                                    <p class="item-desc"><?= htmlspecialchars(mb_substr($item['descricao'], 0, 95, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>...</p>
                                </div>
                                <div>
                                    <div class="item-meta">
                                        <span class="badge-status status-<?= $item['status'] ?>">
                                            <?= strtoupper($item['status']) ?>
                                        </span>
                                        <span style="font-weight: 700; color: white;">R$ <?= number_format($item['valor_estimado'], 2, ',', '.') ?></span>
                                    </div>
                                    <a href="item_detalhe.php?id=<?= $item['id'] ?>" class="btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem; padding: 0.5rem;">Ver Detalhes</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; background: var(--bg-card); padding: 3rem; border-radius: 16px; text-align: center; color: var(--text-muted); border: 1px solid var(--border-glass);">
                            Nenhum objeto encontrado no momento com os critérios selecionados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar: Ranking por Local -->
            <div>
                <div class="sidebar-panel">
                    <h3 class="panel-title">Ranking de Perdas por Local</h3>
                    
                    <div class="panel-explanation">
                        <strong>Estatística do Campus</strong><br>
                        Contabiliza e ordena em tempo real os locais do campus com maior incidência de objetos encontrados.
                    </div>

                    <ul class="ranking-list">
                        <?php if (!empty($rankingLocais)): ?>
                            <?php $rank = 1; foreach ($rankingLocais as $localNome => $score): ?>
                                <li class="ranking-item">
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <span class="ranking-rank"><?= $rank++ ?></span>
                                        <span><?= htmlspecialchars($localNome, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                    </div>
                                    <span class="ranking-score"><?= $score ?> perdas</span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li style="color: var(--text-muted); font-size: 0.85rem; padding: 0.5rem 0;">
                                Nenhum local registrado com perdas até o momento.
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
