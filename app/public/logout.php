<?php
// =============================================================================
// ACHADOS E PERDIDOS IFMG - LOGOUT (LOGOUT.PHP)
// =============================================================================
session_start();
require_once __DIR__ . '/../config/db.php';

$redis = Database::getRedis();

if (isset($_SESSION['user_id'])) {
    $userIdStr = $_SESSION['user_id'];
    $userName = $_SESSION['user_name'] ?? '';
    $userTipo = $_SESSION['user_tipo'] ?? '';
    
    // Remover chave de sessao no Redis
    if ($redis) {
        try {
            $redis->del("sessao:usuario:" . $userIdStr);
            if (!empty($userName)) {
                $redis->sRem("online:usuarios", $userName . " (" . $userTipo . ")");
            }
        } catch (Exception $e) {}
    }
}

session_unset();
session_destroy();

header("Location: index.php");
exit;
