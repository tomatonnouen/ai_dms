<?php
/**
 * 資料自動仕分けシステム - データベース接続
 */

// このファイルのディレクトリを取得
$includesDir = dirname(__FILE__);

require_once $includesDir . '/config.php';

/**
 * データベース接続を取得
 * @return PDO
 */
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new Exception('データベース接続に失敗しました');
        }
    }

    return $pdo;
}

/**
 * トランザクション開始
 */
function beginTransaction() {
    return getDB()->beginTransaction();
}

/**
 * コミット
 */
function commit() {
    return getDB()->commit();
}

/**
 * ロールバック
 */
function rollback() {
    return getDB()->rollBack();
}
