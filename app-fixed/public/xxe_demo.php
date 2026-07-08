<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$error = '';
$result = null;
$xml_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_input = $_POST['xml_data'] ?? '';
    
    if (empty($xml_input)) {
        $error = 'XML 資料不能為空！';
    } else {
        try {
            // 安全修補：防範 XXE 注入
            // 1. 在 PHP 中，預設情況下只要不使用 LIBXML_NOENT 就不會展開外部實體，從而防禦 XXE。
            // 2. 使用 libxml_use_internal_errors(true) 控制解析錯誤輸出，防止洩漏伺服器路徑結構。
            libxml_use_internal_errors(true);
            
            $dom = new DOMDocument();
            
            // 安全修補：不添加任何不安全參數（如 LIBXML_NOENT 或 LIBXML_DTDLOAD），保持預設安全解析
            if ($dom->loadXML($xml_input)) {
                $student = simplexml_import_dom($dom);
                
                // 安全修補：對輸出資料進行安全處理
                $result = [
                    'name' => (string)$student->name,
                    'email' => (string)$student->email,
                    'phone' => (string)$student->phone,
                ];
            } else {
                $error = 'XML 格式錯誤，解析失敗！';
                libxml_clear_errors();
            }
        } catch (Exception $e) {
            $error = '解析失敗。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📁 XXE XML 外部實體注入演練 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📁 XXE 外部實體注入 (XML External Entity Injection) 安全防護演練 (已修正)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 輸入區 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white font-weight-bold">
                    📝 學生名單 XML 匯入系統 (安全防護)
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        本系統接收 XML 格式的學生資料進行名單解析，後端已關閉外部實體解析。
                    </p>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="xml_data" class="font-weight-bold">請輸入 XML 資料：</label>
                            <textarea name="xml_data" id="xml_data" class="form-control" rows="8" required><?= h($xml_input ?: '<?xml version="1.0" encoding="utf-8"?>
<student>
  <name>王小明</name>
  <email>student01@vulncampus.local</email>
  <phone>0922-111-222</phone>
</student>') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">🚀 解析並匯入 XML</button>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        🛡️ <strong>安全防禦說明：</strong><br>
                        1. <strong>停用實體替換</strong>：後端解析 XML 時，保持預設安全解析組態，不加載外部 DTD 與實體替換（禁用 <code>LIBXML_NOENT</code>），防止解析外部實體 <code>SYSTEM</code> 定義的 URL。<br>
                        2. <strong>隱藏內部錯誤</strong>：啟用 <code>libxml_use_internal_errors(true)</code>，阻止 XML 解析錯誤直接拋出在頁面上造成資訊洩漏。
                    </div>
                </div>
            </div>
        </div>

        <!-- 結果展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white font-weight-bold">
                    📋 XML 解析結果
                </div>
                <div class="card-body bg-light" style="max-height: 550px; overflow-y: auto;">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php elseif ($result): ?>
                        <div class="alert alert-success">XML 解析成功！</div>
                        <table class="table table-bordered table-sm bg-white">
                            <tr><th>學生姓名 (Name)</th><td><?= h($result['name']) ?></td></tr>
                            <tr><th>電子信箱 (Email)</th><td><?= h($result['email']) ?></td></tr>
                            <tr><th>聯絡電話 (Phone)</th><td><?= h($result['phone']) ?></td></tr>
                        </table>
                        
                        <div class="mt-3">
                            <h5>安全過濾輸出：</h5>
                            <div class="p-3 border rounded bg-white">
                                <?= h($result['name']) ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">目前尚未解析任何資料</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
