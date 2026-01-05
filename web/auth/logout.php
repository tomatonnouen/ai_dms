<?php
/**
 * 資料自動仕分けシステム - ログアウト
 */

require_once __DIR__ . '/../includes/config.php';

// セッションを破棄
session_destroy();

// ログインページにリダイレクト
header('Location: /web/auth/login.php');
exit;
