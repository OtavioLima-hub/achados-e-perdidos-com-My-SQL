<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - SISTEMA DE LOGIN (LOGIN.PHP)
// =============================================================================
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once __DIR__ . '/../config/db.php';

$redis = Database::getRedis();
$msgErro = '';
$msgSucesso = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (!empty($email)) {
        // Consultar usuario no MongoDB pelo email
        $userDoc = getMongoCollection('usuarios', ['email' => $email]);
        
        if (!empty($userDoc)) {
            $user = $userDoc[0];
            $userIdStr = (string)$user->_id;
            
            // Gravar sessao no PHP
            $_SESSION['user_id'] = $userIdStr;
            $_SESSION['user_name'] = $user->nome ?? 'Usuário';
            $_SESSION['user_email'] = $user->email ?? $email;
            $_SESSION['user_tipo'] = $user->tipo ?? 'estudante';
            
            // Gravar sessao no Redis com TTL de 3600 segundos (1 hora)
            if ($redis) {
                try {
                    $tokenSessao = "session_token_" . bin2hex(random_bytes(16));
                    $redis->setex("sessao:usuario:" . $userIdStr, 3600, json_encode([
                        'user_id' => $userIdStr,
                        'nome' => $user->nome ?? '',
                        'email' => $user->email ?? '',
                        'tipo' => $user->tipo ?? '',
                        'token' => $tokenSessao,
                        'login_at' => date('Y-m-d H:i:s')
                    ]));
                    
                    // Adicionar ao Set de usuarios online
                    $redis->sAdd("online:usuarios", $user->nome . " (" . $user->tipo . ")");
                } catch (Exception $e) {}
            }
            
            header("Location: index.php");
            exit;
        } else {
            $msgErro = "Usuário não encontrado com o e-mail informado.";
        }
    } else {
        $msgErro = "Por favor, informe o e-mail de acesso.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Achados e Perdidos IFMG</title>
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
            </ul>
        </div>
    </header>

    <div class="container" style="max-width: 480px; margin-top: 4rem;">

        <?php if (!empty($msgErro)): ?>
            <div class="cache-banner miss" style="margin-bottom: 1.5rem;">
                <?= htmlspecialchars($msgErro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <div class="hero-section">
            <h1 class="hero-title" style="font-size: 1.8rem; margin-bottom: 0.5rem; text-align: center;">Acesso ao Sistema</h1>
            <p style="font-size: 0.85rem; color: var(--text-secondary); text-align: center; margin-bottom: 2rem;">
                Sessão gerenciada em memória no Redis sob a chave <code>sessao:usuario:{id}</code> (TTL: 3600s).
            </p>

            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem;">E-mail Cadastrado *</label>
                    <input type="email" name="email" class="input-field" style="width: 100%;" placeholder="ana.souza@aluno.uf.edu.br" required>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 0.8rem; margin-bottom: 1.5rem;">
                    Entrar no Sistema
                </button>
            </form>

            <div style="background: rgba(5, 12, 8, 0.6); padding: 1rem; border-radius: 10px; border: 1px solid var(--border-glass);">
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 700; margin-bottom: 0.5rem;">E-mails Rápidos para Teste:</p>
                <ul style="font-size: 0.78rem; color: var(--text-secondary); line-height: 1.6; list-style: none;">
                    <li><strong>Estudante:</strong> <code>ana.souza@aluno.uf.edu.br</code></li>
                    <li><strong>Estudante:</strong> <code>lucas.martins@aluno.uf.edu.br</code></li>
                    <li><strong>Administrador:</strong> <code>carlos.admin@uf.edu.br</code></li>
                    <li><strong>Servidor:</strong> <code>mariana.costa@uf.edu.br</code></li>
                </ul>
            </div>
        </div>

    </div>

</body>
</html>
