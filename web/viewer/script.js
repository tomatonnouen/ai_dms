/**
 * 資料自動仕分けシステム - 閲覧アプリ JavaScript
 */

let currentPage = 0;
let currentLimit = 20;
let currentFilters = {};
let currentDocument = null;

// DOM要素
const categoryFilter = document.getElementById('category-filter');
const keywordSearch = document.getElementById('keyword-search');
const dateFrom = document.getElementById('date-from');
const dateTo = document.getElementById('date-to');
const searchBtn = document.getElementById('search-btn');
const resetBtn = document.getElementById('reset-btn');
const documentList = document.getElementById('document-list');
const totalCount = document.getElementById('total-count');
const displayCount = document.getElementById('display-count');
const pagination = document.getElementById('pagination');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const pageInfo = document.getElementById('page-info');
const pdfModal = document.getElementById('pdf-modal');
const modalTitle = document.getElementById('modal-title');
const pdfViewer = document.getElementById('pdf-viewer');
const modalClose = document.getElementById('modal-close');
const modalEdit = document.getElementById('modal-edit');
const modalDelete = document.getElementById('modal-delete');

// イベントリスナー
searchBtn.addEventListener('click', handleSearch);
resetBtn.addEventListener('click', handleReset);
keywordSearch.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        handleSearch();
    }
});
categoryFilter.addEventListener('change', handleSearch);
prevBtn.addEventListener('click', () => handlePageChange(currentPage - 1));
nextBtn.addEventListener('click', () => handlePageChange(currentPage + 1));
modalClose.addEventListener('click', closeModal);
modalEdit.addEventListener('click', handleEdit);
modalDelete.addEventListener('click', handleDelete);

// 初期読み込み
loadDocuments();

/**
 * 検索処理
 */
function handleSearch() {
    currentPage = 0;
    currentFilters = {
        category_id: categoryFilter.value,
        keyword: keywordSearch.value.trim(),
        date_from: dateFrom.value,
        date_to: dateTo.value
    };
    loadDocuments();
}

/**
 * リセット処理
 */
function handleReset() {
    categoryFilter.value = '';
    keywordSearch.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    currentPage = 0;
    currentFilters = {};
    loadDocuments();
}

/**
 * ページ変更処理
 */
function handlePageChange(page) {
    currentPage = page;
    loadDocuments();
}

/**
 * ドキュメント読み込み
 */
async function loadDocuments() {
    try {
        // ローディング表示
        documentList.innerHTML = '<div class="loading">読み込み中...</div>';

        // パラメータ構築
        const params = new URLSearchParams({
            limit: currentLimit,
            offset: currentPage * currentLimit
        });

        if (currentFilters.category_id) {
            params.append('category_id', currentFilters.category_id);
        }
        if (currentFilters.keyword) {
            params.append('keyword', currentFilters.keyword);
        }
        if (currentFilters.date_from) {
            params.append('date_from', currentFilters.date_from);
        }
        if (currentFilters.date_to) {
            params.append('date_to', currentFilters.date_to);
        }

        // API呼び出し
        const response = await fetch(BASE_PATH + '/api/search_documents.php?' + params);
        const result = await response.json();

        if (result.success) {
            displayDocuments(result.data);
        } else {
            throw new Error(result.message || 'ドキュメントの読み込みに失敗しました');
        }
    } catch (error) {
        console.error('Load documents error:', error);
        documentList.innerHTML = '<div class="loading">エラーが発生しました</div>';
    }
}

/**
 * ドキュメント表示
 */
function displayDocuments(data) {
    const { total, documents } = data;

    // 統計情報更新
    totalCount.textContent = total;
    displayCount.textContent = documents.length;

    // ドキュメントがない場合
    if (documents.length === 0) {
        documentList.innerHTML = '<div class="loading">ドキュメントがありません</div>';
        pagination.style.display = 'none';
        return;
    }

    // ドキュメント一覧表示
    const grid = document.createElement('div');
    grid.className = 'document-grid';

    documents.forEach(doc => {
        const card = createDocumentCard(doc);
        grid.appendChild(card);
    });

    documentList.innerHTML = '';
    documentList.appendChild(grid);

    // ページネーション
    updatePagination(total);
}

/**
 * ドキュメントカード作成
 */
function createDocumentCard(doc) {
    const card = document.createElement('div');
    card.className = 'document-card';
    card.onclick = () => openDocument(doc);

    const category = doc.category_name || '未分類';
    const categoryClass = doc.category_name ? '' : ' uncategorized';

    const title = doc.title || '(タイトルなし)';
    const summary = doc.summary || '';
    const date = doc.document_date ? formatDate(doc.document_date) : '-';
    const uploadDate = formatDate(doc.upload_date);
    const pages = doc.page_count || '-';
    const size = formatFileSize(doc.file_size);

    card.innerHTML = `
        <div class="document-category${categoryClass}">${escapeHtml(category)}</div>
        <div class="document-title">${escapeHtml(title)}</div>
        <div class="document-summary">${escapeHtml(summary)}</div>
        <div class="document-meta">
            <div class="document-meta-item">📅 ${date}</div>
            <div class="document-meta-item">📄 ${pages}ページ</div>
            <div class="document-meta-item">💾 ${size}</div>
        </div>
    `;

    return card;
}

/**
 * ページネーション更新
 */
function updatePagination(total) {
    const totalPages = Math.ceil(total / currentLimit);

    if (totalPages <= 1) {
        pagination.style.display = 'none';
        return;
    }

    pagination.style.display = 'flex';
    pageInfo.textContent = `${currentPage + 1} / ${totalPages} ページ`;

    prevBtn.disabled = currentPage === 0;
    nextBtn.disabled = currentPage >= totalPages - 1;
}

/**
 * ドキュメントを開く
 */
function openDocument(doc) {
    currentDocument = doc;

    modalTitle.textContent = doc.title || '(タイトルなし)';
    pdfViewer.src = BASE_PATH + '/uploads/' + doc.file_path;
    pdfModal.style.display = 'flex';
}

/**
 * モーダルを閉じる
 */
function closeModal() {
    pdfModal.style.display = 'none';
    pdfViewer.src = '';
    currentDocument = null;
}

/**
 * 編集処理
 */
function handleEdit() {
    if (currentDocument) {
        window.location.href = BASE_PATH + '/viewer/edit.php?id=' + currentDocument.id;
    }
}

/**
 * 削除処理
 */
async function handleDelete() {
    if (!currentDocument) {
        return;
    }

    if (!confirm('本当に削除しますか？')) {
        return;
    }

    try {
        const response = await fetch(BASE_PATH + '/api/delete_document.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: currentDocument.id
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('削除しました');
            closeModal();
            loadDocuments();
        } else {
            throw new Error(result.message || '削除に失敗しました');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('削除に失敗しました: ' + error.message);
    }
}

/**
 * 日付フォーマット
 */
function formatDate(dateStr) {
    if (!dateStr) {
        return '-';
    }

    const date = new Date(dateStr);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}/${month}/${day}`;
}

/**
 * ファイルサイズフォーマット
 */
function formatFileSize(bytes) {
    if (!bytes) {
        return '-';
    }

    if (bytes >= 1073741824) {
        return (bytes / 1073741824).toFixed(2) + ' GB';
    } else if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB';
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    } else {
        return bytes + ' bytes';
    }
}

/**
 * HTMLエスケープ
 */
function escapeHtml(str) {
    if (!str) {
        return '';
    }

    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
