<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

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
        
        // 安全修補防護 (CWE-643)：移除輸入中的單引號與雙引號，阻止 XPath 邊界閉合
        $safe_username = str_replace(["'", '"'], "", $username);
        
        $query = "/users/user[username/text()='" . $safe_username . "']";
        
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
    <title>🌐 XPath 注入修補 (CWE-643) - VulnCampus</title>
    <!-- 使用 Bootstrap 5 與修正版風格對齊 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; color: #0f172a; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🌐 XPath 注入漏洞安全修補 (CWE-643)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold py-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與安全防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    🛡️ 學員資料查詢 (已進行安全防禦)
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版已過濾使用者輸入中的單引號與雙引號等字元，防止 XPath 邊界被惡意破壞。</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label font-weight-bold">請輸入使用者帳號 (username)：</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="例如: student01" value="<?= htmlspecialchars($username) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success text-white w-100 font-weight-bold">執行安全查詢</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    <strong>為什麼過濾引號可以防範 XPath 注入？</strong>
                    <br>• 在建構 XPath 的查詢節點時，值是被包裹在單引號中的 (<code>username/text()='USER_INPUT'</code>)。
                    <br>• 攻擊者必須透過輸入單引號 <code>'</code> 來提前「閉合」該查詢條件，並加入 <code>or 1=1</code> 以操縱查詢邏輯。
                    <br>• 安全版在後端使用 <code>str_replace(["'", '"'], "", $username)</code>，在進行 XPath 解析前，先將所有引號過濾剔除。這使得惡意注入字串退化成無害的普通字串（例如 <code>or 1=1 or</code> 成了單純的帳號名稱），因此無法逃脫原有的單引號邊界，進而保證了查詢邏輯的安全！
                </p>
            </div>
        </div>

        <!-- 右側：查詢結果輸出與代碼修補對照 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📊 查詢結果顯示
                </div>
                <div class="card-body bg-white p-4" style="min-height: 250px;">
                    <?php if (!empty($results)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-dark">
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

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全權限檢查邏輯 (代碼修補對比)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (直接拼接)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>$query = "/users/user[username/text()='" . $username . "']";</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (剔除引號防止閉合注入)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>// 移除使用者輸入中的單引號與雙引號
$safe_username = str_replace(["'", '"'], "", $username);
$query = "/users/user[username/text()='" . $safe_username . "']";</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
