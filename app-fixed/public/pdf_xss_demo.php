<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

// 1. 提供相同的 PoC 下載點以作對照，但安全版使用 Content-Disposition: attachment 強制下載，防禦同源執行
if (isset($_GET['download_poc'])) {
    header('Content-Type: application/pdf');
    // 安全防禦 1：強制下載附件，消除瀏覽器同源分頁直接解析渲染的攻擊面
    header('Content-Disposition: attachment; filename="xss-poc.pdf"');
    
    // 建構一個包含紅框點擊 Link Action (/S /URI) 且使用 javascript: 偽協定的 PDF 格式
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<<\n  /Type /Catalog\n  /Pages 2 0 R\n>>\nendobj\n";
    $pdf .= "2 0 obj\n<<\n  /Type /Pages\n  /Kids [3 0 R]\n  /Count 1\n>>\nendobj\n";
    $pdf .= "3 0 obj\n<<\n  /Type /Page\n  /Parent 2 0 R\n  /Resources <<\n    /Font <<\n      /F1 <<\n        /Type /Font\n        /Subtype /Type1\n        /BaseFont /Helvetica\n      >>\n    >>\n  >>\n  /MediaBox [0 0 595 842]\n  /Contents 4 0 R\n  /Annots [5 0 R]\n>>\nendobj\n";
    $pdf .= "4 0 obj\n<< /Length 135 >>\nstream\nBT\n/F1 22 Tf\n50 750 Td\n(PDF-based XSS Demo PoC File) Tj\n/F1 12 Tf\n0 -40 Td\n(Please click the red border box below to verify Stored XSS:) Tj\nET\nendstream\nendobj\n";
    $js_code = "try { alert('💥 PDF-based XSS 攻擊成功！\\n\\n已成功跨來源竊取父視窗 LocalStorage 的敏感 API Token：\\n' + parent.localStorage.getItem('user_session')); } catch(e) { alert('PDF JavaScript 執行成功！但無法存取父視窗：' + e.message); }";
    $encoded_uri = "javascript:" . rawurlencode($js_code);
    $pdf .= "5 0 obj\n<<\n  /Type /Annot\n  /Subtype /Link\n  /Rect [50 670 450 720]\n  /Border [0 0 1]\n  /C [1 0 0]\n  /A <<\n    /Type /Action\n    /S /URI\n    /URI ({$encoded_uri})\n  >>\n>>\nendobj\n";
    $pdf .= "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000056 00000 n\n0000000111 00000 n\n0000000302 00000 n\n0000000492 00000 n\ntrailer\n<<\n  /Size 6\n  /Root 1 0 R\n>>\nstartxref\n708\n%%EOF\n";
    
    echo $pdf;
    exit;
}

$error = '';
$success = '';
$uploaded_file = '';

// 2. 安全副檔名校驗與上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp  = $_FILES['pdf_file']['tmp_name'];
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        // 安全修補 2：嚴格白名單副檔名檢查
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_ext !== 'pdf') {
            $error = '🚫 上傳失敗：不合法的檔案格式！僅允許上傳 .pdf 檔案。';
        } else {
            // 安全修補 3：安全性隨機檔名重新命名，防止潛在的檔名注入或目錄穿越
            $new_file_name = bin2hex(random_bytes(16)) . '.pdf';
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $success = '✓ PDF 檔案安全上傳成功（已隨機重新命名）！';
                $uploaded_file = '/uploads/' . $new_file_name;
            } else {
                $error = '檔案移動失敗。';
            }
        }
    } else {
        $error = '上傳失敗，錯誤代碼：' . ($_FILES['pdf_file']['error'] ?? '未知');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📄 PDF 嵌入安全防護 (PDF-based XSS 防禦) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📄 PDF 嵌入安全防護 (PDF-based XSS 防禦)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= h($success) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：上傳表單與防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    成果證明 PDF 安全上傳區
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版僅允許上傳合法的 <code>.pdf</code> 檔案，且會自動對檔案進行隨機重新命名。</p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label font-weight-bold">選擇 PDF 檔案：</label>
                            <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept=".pdf" required>
                        </div>
                        <button type="submit" class="btn btn-success text-white font-weight-bold w-100">開始安全上傳</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全防禦對照說明</h5>
                <ol class="ps-3 mb-0 text-muted small">
                    <li class="mb-3">
                        <strong>防禦手段 A (強制下載標頭隔離)</strong>：<br>
                        安全版對敏感的 PoC PDF 下載點傳送了 <code>Content-Disposition: attachment</code> 標頭，強制瀏覽器直接存檔，無法在網域內聯網解析，杜絕 XSS。
                        <br>
                        <a href="?download_poc=1" class="btn btn-sm btn-outline-success font-weight-bold my-2 w-100">📥 測試強制下載 PoC PDF</a>
                    </li>
                    <li class="mb-3">
                        <strong>防禦手段 B (沙盒化 iframe)</strong>：<br>
                        安全預覽使用帶有沙盒限制的 iframe：<br>
                        <code>&lt;iframe sandbox="allow-same-origin"&gt;&lt;/iframe&gt;</code>。
                        <br>
                        透過<strong>不給予 <code>allow-scripts</code> 權限</strong>，強制瀏覽器禁用該 PDF 內部嵌入的所有 JavaScript 代碼與超連結 `javascript:` 執行。即使學員去點選紅框，預覽時也完全不會執行彈窗，100% 阻斷威脅！
                    </li>
                </ol>
            </div>
        </div>

        <!-- 右側：PDF 安全預覽區 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📄 證明文件預覽區域 (安全沙盒 iframe)
                </div>
                <div class="card-body d-flex align-items-center justify-content-center bg-white p-4" style="min-height: 500px;">
                    <?php if ($uploaded_file): ?>
                        <!-- 安全防禦 4：在 iframe 中啟用 sandbox 屬性，且不包含 allow-scripts，強制禁止 PDF 中的 JS 執行 -->
                        <iframe src="<?= h($uploaded_file) ?>" sandbox="allow-same-origin" width="100%" height="500px" style="border: none;"></iframe>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <h4>尚未上傳任何成果 PDF</h4>
                            <p class="small">請先在左方選擇檔案並上傳，此處將自動呈現安全沙盒預覽。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
