<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$users_xml = <<<XML
<users>
    <user>
        <username>student01</username>
        <name>陳小明</name>
        <role>student</role>
        <phone>0911-222-333</phone>
        <secret>秘密：期末考題目在資料夾 C 中</secret>
    </user>
    <user>
        <username>student02</username>
        <name>林志強</name>
        <role>student</role>
        <phone>0922-333-444</phone>
        <secret>秘密：下週二請病假</secret>
    </user>
    <user>
        <username>teacher01</username>
        <name>黃美玲 老師</name>
        <role>teacher</role>
        <phone>0933-444-555</phone>
        <secret>秘密：已將學生成績寄出</secret>
    </user>
    <user>
        <username>admin</username>
        <name>系統管理員</name>
        <role>admin</role>
        <phone>0955-666-777</phone>
        <secret>秘密：預設資料庫密碼為 db_admin_12345</secret>
    </user>
</users>
XML;

$results = [];
$username = $_POST['username'] ?? '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $username !== '') {
    try {
        $xml = simplexml_load_string($users_xml);
        // 漏洞點 (CWE-643)：直接將使用者輸入拼接至 XPath 查詢中，未對引號做過濾
        $query = "/users/user[username/text()='" . $username . "']";
        
        $xpath_results = $xml->xpath($query);
        if ($xpath_results !== false) {
            $results = $xpath_results;
        }
    } catch (Exception $e) {
        $error = "XPath 執行錯誤：" . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 XPath 注入漏洞 (CWE-643) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🌐 XPath 注入漏洞 (CWE-643) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold py-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    🔍 學員資料查詢 (XML 驅動)
                </div>
                <div class="card-body">
                    <p class="text-muted">本查詢工具會使用 XPath 語句在後端的 XML 結構中檢索指定使用者帳號的身分資訊。</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">請輸入使用者帳號 (username)：</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="例如: student01" value="<?= htmlspecialchars($username) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold">執行 XPath 查詢</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 XPath 注入演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端程式在建構 XML 查詢的 XPath 語法時，未對使用者輸入中的單引號等控制字元進行過濾或轉義，使得攻擊者能閉合原先的字串界定符並注入邏輯判定運算子。</li>
                    <li class="mb-2"><strong>正常測試</strong>：<br>
                        - 輸入 <code>student01</code>，觀察是否僅回顯「陳小明」的公開通訊資訊。<br>
                        - 輸入 <code>admin</code>，觀察是否能查出「系統管理員」的通訊資訊。
                    </li>
                    <li class="mb-2"><strong>漏洞測試 (XPath Injection)</strong>：<br>
                        試著在輸入框中填入：
                        <br><code>' or 1=1 or '</code>
                        <br>並送出查詢。
                    </li>
                    <li class="mb-2">觀察此時右側結果中，是否在不提供正確密鑰與身分下，<strong>直接將 XML 資料庫中的所有用戶隱私秘密（包括老師與管理員的敏感資料）全數拖出！</strong></li>
                </ol>
            </div>
        </div>

        <!-- 右側：查詢結果輸出與代碼審查 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📊 查詢結果顯示
                </div>
                <div class="card-body bg-white" style="min-height: 250px;">
                    <?php if (!empty($results)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>帳號</th>
                                        <th>姓名</th>
                                        <th>角色</th>
                                        <th>電話</th>
                                        <th class="table-danger text-danger">保密資料 (Secret)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $user): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($user->username) ?></code></td>
                                            <td><?= htmlspecialchars($user->name) ?></td>
                                            <td><?= htmlspecialchars($user->role) ?></td>
                                            <td><?= htmlspecialchars($user->phone) ?></td>
                                            <td class="text-danger font-weight-bold"><?= htmlspecialchars($user->secret) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待查詢執行...</h4>
                            <p class="small">請在左方輸入並送出，此處將顯示符合 XPath 節點的 XML 數據。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white font-weight-bold">
                    📝 脆弱 XPath 查詢代碼
                </div>
                <div class="card-body bg-light">
                    <pre style="background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px;" class="small"><code>// 直接拼接變數導致 XML 查詢邊界被破壞
$query = "/users/user[username/text()='" . $username . "']";
$xpath_results = $xml->xpath($query);</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
