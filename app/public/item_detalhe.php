<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - DETALHES DO ITEM (ITEM_DETALHE.PHP)
// =============================================================================
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once __DIR__ . '/../config/db.php';

$itemId = (int)($_GET['id'] ?? 0);
if ($itemId <= 0) {
    header("Location: index.php");
    exit;
}

$redis = Database::getRedis();
$cacheKey = "cache:item:" . $itemId;
$itemData = null;
$msgSucesso = '';
$msgErro = '';

// Processar solicitação de Reivindicação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reivindicar'])) {
    $justificativa = trim($_POST['justificativa'] ?? '');
    $reivindicanteId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 2;
    $userName = $_SESSION['user_name'] ?? 'Estudante';
    
    if (!empty($justificativa)) {
        try {
            // 1. Atualiza o status do item para 'reivindicado'
            dbUpdate('itens', ['status' => 'reivindicado'], "id = :id", [':id' => $itemId]);

            // 2. Insere a solicitação na tabela de reivindicações
            dbInsert('reivindicacoes', [
                'item_id' => $itemId,
                'usuario_id' => $reivindicanteId,
                'justificativa' => $justificativa,
                'status_reivindicacao' => 'pendente_aprovacao',
                'data_reivindicacao' => date('Y-m-d H:i:s')
            ]);

            // 3. Registra no histórico do item
            dbInsert('historico_status', [
                'item_id' => $itemId,
                'status' => 'reivindicado',
                'usuario_id' => $reivindicanteId,
                'observacao' => "Solicitação de reivindicação registrada por {$userName}.",
                'data_registro' => date('Y-m-d H:i:s')
            ]);

            // 4. Integração com Redis (se ativo)
            if ($redis) {
                try {
                    $redis->lPush("fila:reivindicacoes_pendentes", (string)$itemId);
                    $redis->hSet("resumo:item:" . $itemId, "status", "reivindicado");
                    $redis->del($cacheKey);
                } catch (Exception $e) {}
            }

            $msgSucesso = "Solicitação de reivindicação enviada com sucesso! O item aguarda aprovação na fila de devoluções.";
        } catch (Exception $e) {
            $msgErro = "Erro ao processar reivindicação: " . $e->getMessage();
        }
    } else {
        $msgErro = "Por favor, forneça uma justificativa com detalhes comprobatórios.";
    }
}

// -----------------------------------------------------------------------------
// CONSULTA DOS DADOS DO ITEM COM CACHE REDIS OU MYSQL
// -----------------------------------------------------------------------------
if ($redis && $redis->exists($cacheKey)) {
    $itemData = json_decode($redis->get($cacheKey), true);
} else {
    $sql = "SELECT i.*, l.nome AS local_nome, l.bloco, l.responsavel, u.nome AS cadastrado_por_nome 
            FROM itens i 
            LEFT JOIN locais l ON i.local_id = l.id 
            LEFT JOIN usuarios u ON i.cadastrado_por_usuario_id = u.id 
            WHERE i.id = :id";
    
    $row = dbFetchOne($sql, [':id' => $itemId]);
    
    if ($row) {
        $itemData = [
            'id' => (string)$row['id'],
            'titulo' => $row['titulo'] ?? '',
            'descricao' => $row['descricao'] ?? '',
            'categoria' => $row['categoria'] ?? '',
            'status' => $row['status'] ?? 'encontrado',
            'valor_estimado' => (float)($row['valor_estimado'] ?? 0),
            'cor' => !empty($row['cor']) ? $row['cor'] : 'Não Informada',
            'marca' => !empty($row['marca']) ? $row['marca'] : 'Não Informada',
            'numero_serie' => !empty($row['numero_serie']) ? $row['numero_serie'] : 'Não Informado',
            'tamanho' => !empty($row['tamanho']) ? $row['tamanho'] : null,
            'local_nome' => $row['local_nome'] ?? 'Campus IFMG',
            'bloco' => $row['bloco'] ?? '',
            'cadastrado_por_nome' => $row['cadastrado_por_nome'] ?? 'Servidor do Campus',
            'data_registro' => !empty($row['data_registro']) ? date('d/m/Y H:i', strtotime($row['data_registro'])) : 'Recente'
        ];

        if ($redis) {
            try {
                $redis->setex($cacheKey, 300, json_encode($itemData));
            } catch (Exception $e) {}
        }
    }
}

if (!$itemData) {
    die("Item não encontrado no banco de dados MySQL.");
}

// Buscar histórico de status do item
$historico = dbFetchAll("SELECT h.*, u.nome AS usuario_nome 
                         FROM historico_status h 
                         LEFT JOIN usuarios u ON h.usuario_id = u.id 
                         WHERE h.item_id = :id 
                         ORDER BY h.data_registro ASC", [':id' => $itemId]);
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

        <?php if (!empty($msgErro)): ?>
            <div class="cache-banner miss" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($msgErro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
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
                    <span style="color: var(--text-muted); font-size: 0.85rem;">Local de Guarda:</span><br>
                    <strong style="color: white;"><?= htmlspecialchars($itemData['local_nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong>
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

            <!-- Histórico de Status -->
            <?php if (!empty($historico)): ?>
                <div style="background: rgba(5, 12, 8, 0.5); padding: 1.25rem; border-radius: 12px; border: 1px solid var(--border-glass); margin-bottom: 1.5rem;">
                    <h3 style="font-family: var(--font-heading); font-size: 1.1rem; color: white; margin-bottom: 0.8rem;">Histórico do Objeto</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.6rem;">
                        <?php foreach ($historico as $h): ?>
                            <div style="font-size: 0.85rem; color: var(--text-secondary); border-left: 2px solid var(--neon-green); padding-left: 0.8rem;">
                                <strong style="color: white;"><?= date('d/m/Y H:i', strtotime($h['data_registro'])) ?></strong> - 
                                <span class="badge-status status-<?= $h['status'] ?>" style="font-size: 0.75rem; padding: 0.15rem 0.4rem;"><?= strtoupper($h['status']) ?></span>: 
                                <?= htmlspecialchars($h['observacao'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                <small style="color: var(--text-muted);">(Registrado por: <?= htmlspecialchars($h['usuario_nome'] ?? 'Sistema', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Reivindicar Form -->
            <?php if ($itemData['status'] === 'encontrado'): ?>
                <div style="background: rgba(5, 12, 8, 0.7); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border-glass);">
                    <h3 style="font-family: var(--font-heading); font-size: 1.2rem; margin-bottom: 0.75rem; color: white;">Reivindicar Posse deste Objeto</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                        Esta ação insere a justificativa no MySQL, atualiza o status para <strong>reivindicado</strong> e adiciona a solicitação na fila de atendimento para validação.
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
