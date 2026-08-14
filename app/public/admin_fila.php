<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - GERENCIAMENTO DE FILA DE ATENDIMENTO (ADMIN_FILA.PHP)
// =============================================================================
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once __DIR__ . '/../config/db.php';

$redis = Database::getRedis();
$msgFila = '';

// Processar devolução (Próximo da fila)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_pop_fila'])) {
    $poppedItemId = null;
    
    // Tenta obter do Redis se disponível
    if ($redis) {
        try {
            $poppedItemId = $redis->rPop("fila:reivindicacoes_pendentes");
        } catch (Exception $e) {}
    }
    
    // Se não houver no Redis, busca a reivindicação pendente mais antiga no MySQL
    if (!$poppedItemId) {
        $oldest = dbFetchOne("SELECT item_id FROM reivindicacoes WHERE status_reivindicacao = 'pendente_aprovacao' ORDER BY data_reivindicacao ASC LIMIT 1");
        if ($oldest) {
            $poppedItemId = (int)$oldest['item_id'];
        }
    }
    
    if ($poppedItemId) {
        $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

        // Atualizar item como 'devolvido'
        dbUpdate('itens', ['status' => 'devolvido'], "id = :id", [':id' => $poppedItemId]);

        // Atualizar reivindicação como 'aprovada'
        dbUpdate('reivindicacoes', ['status_reivindicacao' => 'aprovada'], "item_id = :id AND status_reivindicacao = 'pendente_aprovacao'", [':id' => $poppedItemId]);

        // Registrar histórico
        dbInsert('historico_status', [
            'item_id' => $poppedItemId,
            'status' => 'devolvido',
            'usuario_id' => $adminId,
            'observacao' => 'Devolução validada e confirmada via Fila de Atendimento pelo Administrador.',
            'data_registro' => date('Y-m-d H:i:s')
        ]);

        // Invalida cache no Redis se ativo
        if ($redis) {
            try {
                $redis->del("cache:item:" . $poppedItemId);
                $redis->hSet("resumo:item:" . $poppedItemId, "status", "devolvido");
            } catch (Exception $e) {}
        }

        $msgFila = "Item #<code>{$poppedItemId}</code> processado com sucesso e marcado como <strong>DEVOLVIDO</strong> no MySQL!";
    } else {
        $msgFila = "A Fila de Atendimento está vazia no momento. Nenhuma solicitação pendente.";
    }
}

// -----------------------------------------------------------------------------
// CARREGAR ITENS DA FILA DE REIVINDICAÇÕES
// -----------------------------------------------------------------------------
$filaItems = [];

// Busca direta no MySQL para garantir dados completos e consistentes
$sqlFila = "SELECT r.id AS reivindicacao_id, r.justificativa, r.data_reivindicacao, r.status_reivindicacao,
                   i.id, i.titulo, i.categoria, i.status,
                   u.nome AS solicitante_nome, u.email AS solicitante_email, u.matricula
            FROM reivindicacoes r
            JOIN itens i ON r.item_id = i.id
            JOIN usuarios u ON r.usuario_id = u.id
            WHERE r.status_reivindicacao = 'pendente_aprovacao'
            ORDER BY r.data_reivindicacao ASC";

$rawFila = dbFetchAll($sqlFila);

foreach ($rawFila as $item) {
    $filaItems[] = [
        'id' => (string)$item['id'],
        'titulo' => $item['titulo'] ?? '',
        'categoria' => $item['categoria'] ?? '',
        'solicitante_nome' => $item['solicitante_nome'] ?? 'Estudante',
        'solicitante_email' => $item['solicitante_email'] ?? '',
        'justificativa' => $item['justificativa'] ?? 'Sem justificativa',
        'data_reivindicacao' => !empty($item['data_reivindicacao']) ? date('d/m/Y H:i', strtotime($item['data_reivindicacao'])) : 'Recente'
    ];
}

// Usuários online
$onlineUsers = [];
if ($redis) {
    try {
        $onlineUsers = $redis->sMembers("online:usuarios");
    } catch (Exception $e) {}
}

if (empty($onlineUsers)) {
    $recentUsers = dbFetchAll("SELECT nome, tipo FROM usuarios WHERE ativo = 1 LIMIT 4");
    foreach ($recentUsers as $u) {
        $onlineUsers[] = $u['nome'] . " (" . $u['tipo'] . ")";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fila de Atendimento | Achados e Perdidos IFMG</title>
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
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <div class="container">

        <?php if (!empty($msgFila)): ?>
            <div class="cache-banner hit" style="margin-bottom: 2rem;">
                <?= $msgFila ?>
            </div>
        <?php endif; ?>

        <div class="layout-grid">

            <!-- Fila de Atendimento List -->
            <div>
                <div class="hero-section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h2 style="font-family: var(--font-heading); font-size: 1.5rem; color: white;">Fila de Reivindicações Pendentes</h2>
                            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.3rem;">
                                Gerenciamento de solicitações de posse por ordem de chegada (FIFO) com persistência em MySQL.
                            </p>
                        </div>
                        <?php if (!empty($filaItems)): ?>
                            <form method="POST">
                                <button type="submit" name="action_pop_fila" class="btn-primary">
                                    Processar Próximo da Fila
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($filaItems)): ?>
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            <?php foreach ($filaItems as $idx => $item): ?>
                                <div style="background: rgba(5, 12, 8, 0.7); padding: 1.2rem; border-radius: 14px; border: 1px solid var(--border-glass); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                                    <div>
                                        <div style="font-size: 0.75rem; color: var(--neon-green); font-weight: 800;">POSIÇÃO #<?= $idx + 1 ?> NA FILA</div>
                                        <h3 style="font-family: var(--font-heading); font-size: 1.15rem; color: white; margin: 0.2rem 0;"><?= htmlspecialchars($item['titulo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                                        <div style="font-size: 0.82rem; color: var(--neon-green); margin-bottom: 0.3rem;">
                                            Solicitado por: <strong><?= htmlspecialchars($item['solicitante_nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong> (<?= htmlspecialchars($item['solicitante_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>)
                                        </div>
                                        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-top: 0.25rem;">
                                            <strong>Justificativa:</strong> "<?= htmlspecialchars($item['justificativa'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                                        </p>
                                        <small style="color: var(--text-muted);">Solicitado em: <?= $item['data_reivindicacao'] ?></small>
                                    </div>
                                    <a href="item_detalhe.php?id=<?= $item['id'] ?>" class="btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Inspecionar Item</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                            Nenhuma reivindicação pendente na fila no momento.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar: Informações de Conexão e Sessão -->
            <div>
                <div class="sidebar-panel">
                    <h3 class="panel-title">Usuários Ativos:</h3>
                    <ul class="ranking-list">
                        <?php foreach ($onlineUsers as $user): ?>
                            <li class="ranking-item">
                                <span style="font-size: 0.85rem; font-family: var(--font-mono); color: var(--neon-green);"><?= htmlspecialchars($user, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-panel">
                    <h3 class="panel-title">Tabelas Relacionais (MySQL)</h3>
                    <ul style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.8; list-style: square; padding-left: 1.2rem;">
                        <li><code>usuarios</code> (Controle de Acesso)</li>
                        <li><code>locais</code> (Pontos de Atendimento)</li>
                        <li><code>itens</code> (Catálogo de Objetos)</li>
                        <li><code>reivindicacoes</code> (Solicitações de Posse)</li>
                        <li><code>historico_status</code> (Auditoria e Rastreio)</li>
                    </ul>
                </div>
            </div>

        </div>

    </div>

</body>
</html>
