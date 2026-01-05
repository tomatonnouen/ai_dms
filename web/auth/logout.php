<?php
/**
 * 資料自動仕分けシステム - ログアウト
 */

require_once __DIR__ . '/../includes/config.php';

// セッションを破棄
session_destroy();

// ログインページにリダイレクト
header('Location: ' . BASE_PATH . '/auth/login.php');
exit;
