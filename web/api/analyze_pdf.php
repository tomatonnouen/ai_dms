<?php
/**
 * API: PDF解析（Web用）
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 認証チェック
    if (!isLoggedIn()) {
        jsonError('認証が必要です');
    }

    // JSONペイロード取得
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['pdf_base64'])) {
        jsonError('PDFデータが必要です');
    }

    $pdfBase64 = $input['pdf_base64'];
    $filename = $input['filename'] ?? 'document.pdf';

    // カテゴリ一覧取得
    $db = getDB();
    $stmt = $db->query('SELECT id, name FROM categories ORDER BY display_order ASC');
    $categories = $stmt->fetchAll();

    $categoryList = "【カテゴリ一覧】\n";
    foreach ($categories as $cat) {
        $categoryList .= sprintf("%d. %s\n", $cat['id'], $cat['name']);
    }

    // Gemini APIプロンプト
    $prompt = <<<PROMPT
あなたは農業関連資料の分類専門家です。
以下のPDFファイルを分析し、JSON形式で回答してください。

{$categoryList}

【抽出項目】
- category_id: 最も適切なカテゴリのID（不明な場合はnull）
- category_name: カテゴリ名（不明な場合は"未分類"）
- title: 文書のタイトル（年号を含める）
- summary: 200文字以内の概要
- document_date: 文書内の日付（YYYY-MM-DD形式、不明な場合はnull）
- confidence: 判定の信頼度（0.0-1.0）

【注意事項】
- 文書内に年号が含まれる場合は、タイトルに必ず含めてください
- 例: "総会資料" → "2025年度 総会資料"

回答はJSON形式のみで、説明文は不要です。
PROMPT;

    // Gemini API呼び出し
    $requestData = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => 'application/pdf',
                            'data' => $pdfBase64
                        ]
                    ]
                ]
            ]
        ]
    ];

    $apiUrl = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($requestData),
            'timeout' => 30
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($apiUrl, false, $context);

    if ($response === false) {
        jsonError('Gemini API呼び出しに失敗しました');
    }

    $result = json_decode($response, true);

    // レスポンス解析
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $analysisText = $result['candidates'][0]['content']['parts'][0]['text'];

        // JSONを抽出（マークダウンコードブロックを除去）
        $analysisText = preg_replace('/```json\s*|\s*```/', '', $analysisText);
        $analysisText = trim($analysisText);

        $analysis = json_decode($analysisText, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // カテゴリ名を設定
            if (!empty($analysis['category_id'])) {
                $stmt = $db->prepare('SELECT name FROM categories WHERE id = ?');
                $stmt->execute([$analysis['category_id']]);
                $category = $stmt->fetch();
                if ($category) {
                    $analysis['category_name'] = $category['name'];
                }
            }

            jsonSuccess(['analysis' => $analysis]);
        } else {
            jsonError('解析結果のパースに失敗しました');
        }
    } else {
        jsonError('Gemini APIからの応答が不正です');
    }

} catch (Exception $e) {
    error_log('Analyze PDF error: ' . $e->getMessage());
    jsonError('PDF解析に失敗しました: ' . $e->getMessage());
}
