/**
 * 資料自動仕分けシステム - 登録アプリ JavaScript
 */

let currentFile = null;
let analysisResult = null;

// DOM要素
const uploadArea = document.getElementById('upload-area');
const fileInput = document.getElementById('file-input');
const analyzingArea = document.getElementById('analyzing');
const previewArea = document.getElementById('preview-area');
const successMessage = document.getElementById('success-message');
const documentForm = document.getElementById('document-form');

// イベントリスナー
uploadArea.addEventListener('click', () => fileInput.click());
uploadArea.addEventListener('dragover', handleDragOver);
uploadArea.addEventListener('dragleave', handleDragLeave);
uploadArea.addEventListener('drop', handleDrop);
fileInput.addEventListener('change', handleFileSelect);
documentForm.addEventListener('submit', handleSubmit);
document.getElementById('cancel-btn').addEventListener('click', resetForm);
document.getElementById('another-btn').addEventListener('click', resetForm);

/**
 * ドラッグオーバー処理
 */
function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.add('dragover');
}

/**
 * ドラッグリーブ処理
 */
function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.remove('dragover');
}

/**
 * ドロップ処理
 */
function handleDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    uploadArea.classList.remove('dragover');

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
}

/**
 * ファイル選択処理
 */
function handleFileSelect(e) {
    const files = e.target.files;
    if (files.length > 0) {
        handleFile(files[0]);
    }
}

/**
 * ファイル処理
 */
function handleFile(file) {
    // PDFチェック
    if (file.type !== 'application/pdf') {
        alert('PDFファイルのみアップロード可能です');
        return;
    }

    // サイズチェック（50MB）
    if (file.size > 50 * 1024 * 1024) {
        alert('ファイルサイズが大きすぎます（最大50MB）');
        return;
    }

    currentFile = file;

    // PDFをBase64に変換
    const reader = new FileReader();
    reader.onload = async (e) => {
        const base64 = e.target.result.split(',')[1];
        await analyzePdf(base64, file.name);
    };
    reader.readAsDataURL(file);

    // UI更新
    uploadArea.style.display = 'none';
    analyzingArea.style.display = 'block';
}

/**
 * PDF解析
 */
async function analyzePdf(base64, filename) {
    try {
        const response = await fetch(BASE_PATH + '/api/analyze_pdf.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                pdf_base64: base64,
                filename: filename
            })
        });

        const result = await response.json();

        if (result.success) {
            analysisResult = result.data.analysis;
            displayAnalysisResult(analysisResult);
        } else {
            throw new Error(result.message || 'PDF解析に失敗しました');
        }
    } catch (error) {
        console.error('Analysis error:', error);
        alert('PDF解析に失敗しました: ' + error.message);
        resetForm();
    }
}

/**
 * 解析結果を表示
 */
function displayAnalysisResult(analysis) {
    // カテゴリ
    if (analysis.category_id) {
        document.getElementById('category').value = analysis.category_id;
    }

    // 信頼度表示
    const confidence = analysis.confidence || 0;
    const confidenceEl = document.getElementById('confidence');
    let confidenceClass = 'low';
    let confidenceText = '信頼度: 低';

    if (confidence >= 0.8) {
        confidenceClass = 'high';
        confidenceText = '信頼度: 高 (' + Math.round(confidence * 100) + '%)';
    } else if (confidence >= 0.5) {
        confidenceClass = 'medium';
        confidenceText = '信頼度: 中 (' + Math.round(confidence * 100) + '%)';
    } else {
        confidenceText = '信頼度: 低 (' + Math.round(confidence * 100) + '%)';
    }

    confidenceEl.textContent = confidenceText;
    confidenceEl.className = 'confidence ' + confidenceClass;

    // タイトル
    document.getElementById('title').value = analysis.title || '';

    // 概要
    document.getElementById('summary').value = analysis.summary || '';

    // 文書日付
    document.getElementById('document_date').value = analysis.document_date || '';

    // ファイル名
    document.getElementById('suggested_filename').value = analysis.suggested_filename || '';

    // UI更新
    analyzingArea.style.display = 'none';
    previewArea.style.display = 'block';
}

/**
 * フォーム送信処理
 */
async function handleSubmit(e) {
    e.preventDefault();

    if (!currentFile) {
        alert('ファイルが選択されていません');
        return;
    }

    const saveBtn = document.getElementById('save-btn');
    saveBtn.disabled = true;
    saveBtn.textContent = '保存中...';

    try {
        const formData = new FormData();
        formData.append('file', currentFile);
        formData.append('category_id', document.getElementById('category').value);
        formData.append('title', document.getElementById('title').value);
        formData.append('summary', document.getElementById('summary').value);
        formData.append('document_date', document.getElementById('document_date').value);
        formData.append('suggested_filename', document.getElementById('suggested_filename').value);

        const response = await fetch(BASE_PATH + '/api/save_document.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // 成功メッセージ表示
            previewArea.style.display = 'none';
            successMessage.style.display = 'block';
        } else {
            throw new Error(result.message || '保存に失敗しました');
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('保存に失敗しました: ' + error.message);
        saveBtn.disabled = false;
        saveBtn.textContent = '保存';
    }
}

/**
 * フォームリセット
 */
function resetForm() {
    currentFile = null;
    analysisResult = null;

    uploadArea.style.display = 'block';
    analyzingArea.style.display = 'none';
    previewArea.style.display = 'none';
    successMessage.style.display = 'none';

    documentForm.reset();
    fileInput.value = '';

    const saveBtn = document.getElementById('save-btn');
    saveBtn.disabled = false;
    saveBtn.textContent = '保存';
}
