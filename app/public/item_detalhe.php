<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - DETALHES DO ITEM (ITEM_DETALHE.PHP)
// =============================================================================
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once __DIR__ . '/../config/db.php';

$itemId = $_GET['id'] ?? '';
if (empty($itemId)) {
    header("Location: index.php");
    exit;
}

$redis = Database::getRedis();
$cacheKey = "cache:item:" . $itemId;
$itemData = null;

// Processar solicitacao de Reivindicacao (Segundo Fluxo)
$msgSucesso = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reivindicar'])) {
    $justificativa = trim($_POST['justificativa'] ?? '');
    $reivindicanteId = $_SESSION['user_id'] ?? '65c100000000000000000002';
    
    updateMongoDocument('itens', ['_id' => new MongoDB\BSON\ObjectId($itemId)], [
        '$set' => [
            'status' => 'reivindicado',
            'reivindicao' => [
                'usuario_id' => new MongoDB\BSON\ObjectId($reivindicanteId),
                'data_reivindicacao' => new MongoDB\BSON\UTCDateTime(),
                'justificativa' => $justificativa,
                'status_reivindicacao' => 'pendente_aprovacao'
            ]
        ],
        '$push' => [
            'historico_status' => [
                'data' => new MongoDB\BSON\UTCDateTime(),
                'status' => 'reivindicado',
                'usuario_id' => new MongoDB\BSON\ObjectId($reivindicanteId),
                'observacao' => 'Solicitação registrada por ' . ($_SESSION['user_name'] ?? 'Usuário') . '.'
            ]
        ]
    ]);
    
    if ($redis) {
        try {
            $redis->lPush("fila:reivindicacoes_pendentes", $itemId);
            $redis->hSet("resumo:item:" . $itemId, "status", "reivindicado");
            $redis->del($cacheKey);
        } catch (Exception $e) {}
    }
    
    $msgSucesso = "Solicitação de reivindicação enviada com sucesso. O item foi adicionado à fila do Redis e o cache foi invalidado.";
}

// -----------------------------------------------------------------------------
// FLUXO DE CACHE COM REDIS
// -----------------------------------------------------------------------------
if ($redis && $redis->exists($cacheKey)) {
    $itemData = json_decode($redis->get($cacheKey), true);
} else {
    $rawDoc = getMongoDocumentById('itens', $itemId);
    
    if ($rawDoc) {
        $itemData = [
            'id' => (string)$rawDoc->_id,
            'titulo' => $rawDoc->titulo ?? '',
            'descricao' => $rawDoc->descricao ?? '',
            'categoria' => $rawDoc->categoria ?? '',
            'status' => $rawDoc->status ?? 'encontrado',
            'valor_estimado' => $rawDoc->valor_estimado ?? 0,
            'cor' => $rawDoc->detalhes_item->cor ?? 'Não Informada',
            'marca' => $rawDoc->detalhes_item->marca ?? 'Não Informada',
            'tags' => isset($rawDoc->detalhes_item->tags) ? (array)$rawDoc->detalhes_item->tags : []
        ];
        
        if ($redis) {
            try {
                $redis->setex($cacheKey, 300, json_encode($itemData));
            } catch (Exception $e) {}
        }
    }
}

if (!$itemData) {
    die("Item não encontrado.");
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($itemData['titulo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> | Achados e Perdidos IFMG</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/public/css/style.css">
</head>
<body>

    <header class="header-nav">
        <div class="nav-container">
            <a href="index.php" class="logo-group">
                <img src="https://www.ifmg.edu.br/portal/imagens/logovertical.jpg" alt="Logo IFMG" class="logo-img">
                <div class="logo-text">Achados e Perdidos</div>
            </a>
            <ul class="nav-links">
                <li><a href="index.php" class="nav-link">Voltar ao Catálogo</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 0.85rem; color: var(--neon-green); font-weight: 700;">
                            <?= htmlspecialchars($_SESSION['user_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                        </span>
                        <a href="logout.php" class="nav-link" style="color: var(--text-muted); font-size: 0.8rem;">Sair</a>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link" style="border: 1px solid var(--border-glass);">Entrar / Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="container">

        <?php if (!empty($msgSucesso)): ?>
            <div class="cache-banner hit" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($msgSucesso, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Main Detail Card -->
        <div class="hero-section">
            <div class="item-category"><?= htmlspecialchars($itemData['categoria'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
            <h1 class="hero-title"><?= htmlspecialchars($itemData['titulo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
            <p class="hero-desc" style="margin-bottom: 1.5rem;"><?= htmlspecialchars($itemData['descricao'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>

            <div style="display: flex; gap: 2rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.85rem;">Status Atual:</span><br>
                    <span class="badge-status status-<?= $itemData['status'] ?>" style="font-size: 0.9rem;">
                        <?= strtoupper($itemData['status']) ?>
                    </span>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.85rem;">Valor Estimado:</span><br>
                    <strong style="font-size: 1.1rem; color: white;">R$ <?= number_format($itemData['valor_estimado'], 2, ',', '.') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.85rem;">Marca / Fabricante:</span><br>
                    <strong style="color: white;"><?= htmlspecialchars($itemData['marca'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.85rem;">Cor Predominante:</span><br>
                    <strong style="color: white;"><?= htmlspecialchars($itemData['cor'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
                </div>
            </div>

            <!-- Reivindicar Form -->
            <?php if ($itemData['status'] === 'encontrado'): ?>
                <div style="background: rgba(5, 12, 8, 0.7); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-glass);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.2rem; margin-bottom: 0.75rem; color: white;">Reivindicar Posse deste Objeto</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        Esta ação insere a justificativa no MongoDB, adiciona o ID da solicitação na Fila do Redis (<code>fila:reivindicacoes_pendentes</code>) e invalida a chave de cache.
                    </p>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST">
                            <textarea name="justificativa" class="input-field" style="width: 100%; height: 90px; margin-bottom: 1rem;" placeholder="Descreva detalhes específicos ou números de série para comprovar sua propriedade..." required></textarea>
                            <button type="submit" name="action_reivindicar" class="btn-primary">Enviar Reivindicação</button>
                        </form>
                    <?php else: ?>
                        <div style="background: rgba(16, 185, 129, 0.1); padding: 1rem; border-radius: 8px; font-size: 0.85rem;">
                            Você precisa estar <a href="login.php" style="color: var(--neon-green); font-weight: 700;">conectado (Fazer Login)</a> para reivindicar este objeto.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

    </div>

</body>
</html>
