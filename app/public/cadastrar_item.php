<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - CADASTRO DE ITEM (CADASTRAR_ITEM.PHP)
// =============================================================================
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once __DIR__ . '/../config/db.php';

$redis = Database::getRedis();
$msgSucesso = '';
$msgErro = '';

// Carregar locais ativos do MySQL
$locais = dbFetchAll("SELECT id, nome, bloco FROM locais WHERE ativo = 1 ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $categoria = $_POST['categoria'] ?? 'Outros';
    $valorEstimado = (float)($_POST['valor_estimado'] ?? 0);
    $localId = (int)($_POST['local_id'] ?? 1);
    $cor = trim($_POST['cor'] ?? '');
    $marca = trim($_POST['marca'] ?? '');
    
    // Obter o ID do usuário cadastrador (da sessão ou usuário padrão)
    $usuarioId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    
    if (!empty($titulo) && !empty($descricao)) {
        try {
            $tags = strtolower(implode(',', array_filter([$titulo, $categoria, $cor, $marca])));

            // Inserir item no MySQL
            $itemData = [
                'titulo' => $titulo,
                'descricao' => $descricao,
                'categoria' => $categoria,
                'status' => 'encontrado',
                'valor_estimado' => $valorEstimado,
                'local_id' => $localId,
                'cadastrado_por_usuario_id' => $usuarioId,
                'cor' => !empty($cor) ? $cor : null,
                'marca' => !empty($marca) ? $marca : null,
                'tags' => $tags,
                'desativado' => 0,
                'data_registro' => date('Y-m-d H:i:s')
            ];

            $newId = dbInsert('itens', $itemData);

            // Inserir histórico de status inicial
            dbInsert('historico_status', [
                'item_id' => $newId,
                'status' => 'encontrado',
                'usuario_id' => $usuarioId,
                'observacao' => 'Item cadastrado via formulário web PHP no MySQL.',
                'data_registro' => date('Y-m-d H:i:s')
            ]);

            // Buscar nome do local para atualização no Redis
            $localNome = 'Campus IFMG';
            foreach ($locais as $loc) {
                if ($loc['id'] == $localId) {
                    $localNome = $loc['nome'];
                    break;
                }
            }

            // Atualização no Redis se disponível
            if ($redis) {
                try {
                    $redis->zIncrBy("ranking:locais_perdas", 1, $localNome);
                    $redis->hMSet("resumo:item:" . $newId, [
                        'titulo' => $titulo,
                        'categoria' => $categoria,
                        'status' => 'encontrado',
                        'local' => $localNome,
                        'valor' => $valorEstimado
                    ]);
                } catch (Exception $e) {}
            }

            $msgSucesso = "Item #{$newId} - '{$titulo}' cadastrado com sucesso no MySQL!";
        } catch (Exception $e) {
            $msgErro = "Erro ao cadastrar item: " . $e->getMessage();
        }
    } else {
        $msgErro = "Por favor, preencha os campos obrigatórios (Título e Descrição).";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Objeto | Achados e Perdidos IFMG</title>
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

    <div class="container" style="max-width: 750px;">

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

        <div class="hero-section">
            <h1 class="hero-title" style="font-size: 1.8rem; margin-bottom: 1.5rem;">Cadastrar Novo Objeto Encontrado</h1>
            
            <form method="POST">
                <div style="margin-bottom: 1.25rem;">
                    <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Título do Objeto *</label>
                    <input type="text" name="titulo" class="input-field" style="width: 100%;" placeholder="Ex: Mochila JanSport Azul" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Categoria *</label>
                        <select name="categoria" class="input-field" style="width: 100%;" required>
                            <option value="Eletrônicos">Eletrônicos</option>
                            <option value="Documentos">Documentos</option>
                            <option value="Acessórios">Acessórios</option>
                            <option value="Material Escolar">Material Escolar</option>
                            <option value="Chaves">Chaves</option>
                            <option value="Vestuário">Vestuário</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Local de Guarda / Encontrado *</label>
                        <select name="local_id" class="input-field" style="width: 100%;" required>
                            <?php foreach ($locais as $loc): ?>
                                <option value="<?= $loc['id'] ?>">
                                    <?= htmlspecialchars($loc['nome'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Valor Estimado (R$)</label>
                        <input type="number" step="0.01" name="valor_estimado" class="input-field" style="width: 100%;" placeholder="150.00">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Marca</label>
                        <input type="text" name="marca" class="input-field" style="width: 100%;" placeholder="Ex: Dell, Nike">
                    </div>
                    <div>
                        <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Cor Predominante</label>
                        <input type="text" name="cor" class="input-field" style="width: 100%;" placeholder="Ex: Preto">
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.4rem;">Descrição Detalhada *</label>
                    <textarea name="descricao" class="input-field" style="width: 100%; height: 100px;" placeholder="Informe detalhes que facilitem a identificação pelo proprietário..." required></textarea>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.8rem;">
                    Cadastrar Objeto no MySQL
                </button>
            </form>
        </div>

    </div>

</body>
</html>
