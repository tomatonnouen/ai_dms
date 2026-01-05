<?php
/**
 * 資料自動仕分けシステム - ブートストラップファイル
 *
 * このファイルをすべてのPHPファイルの最初にインクルードしてください
 */

// アプリケーションルートディレクトリを定義（web/ディレクトリ）
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// includesディレクトリのパス
if (!defined('INCLUDES_DIR')) {
    define('INCLUDES_DIR', APP_ROOT . '/includes');
}

// 設定ファイルと共通ファイルを読み込み
require_once INCLUDES_DIR . '/config.php';
require_once INCLUDES_DIR . '/db.php';
require_once INCLUDES_DIR . '/functions.php';
