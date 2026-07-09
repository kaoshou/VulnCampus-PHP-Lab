<?php
require_once __DIR__ . '/../../src/helpers.php';
header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// 產生 4 位隨機數字
$code = '';
for ($i = 0; $i < 4; $i++) {
    $code .= rand(0, 9);
}
$_SESSION['captcha_answer'] = $code;

// 輸出向量圖 (SVG) 格式驗證碼，不需 GD 延伸模組
echo '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="40" viewBox="0 0 120 40">';
// 繪製背景
echo '<rect width="120" height="40" fill="#f1f5f9" rx="8" stroke="#cbd5e1" stroke-width="1"/>';

// 繪製干擾雜訊線
for ($i = 0; $i < 4; $i++) {
    $x1 = rand(0, 40);
    $y1 = rand(0, 40);
    $x2 = rand(80, 120);
    $y2 = rand(0, 40);
    $colors = ['#94a3b8', '#cbd5e1', '#e2e8f0'];
    $color = $colors[array_rand($colors)];
    echo "<line x1=\"$x1\" y1=\"$y1\" x2=\"$x2\" y2=\"$y2\" stroke=\"$color\" stroke-width=\"1.5\"/>";
}

// 繪製干擾點
for ($i = 0; $i < 30; $i++) {
    $cx = rand(5, 115);
    $cy = rand(5, 35);
    $r = rand(1, 2);
    echo "<circle cx=\"$cx\" cy=\"$cy\" r=\"$r\" fill=\"#cbd5e1\" opacity=\"0.7\"/>";
}

// 寫入隨機扭曲的數字
for ($i = 0; $i < 4; $i++) {
    $char = $code[$i];
    $x = 18 + ($i * 24) + rand(-2, 2);
    $y = 28 + rand(-3, 3);
    $rot = rand(-15, 15);
    $colors = ['#1e3a8a', '#0f766e', '#b91c1c', '#4338ca', '#7c2d12'];
    $color = $colors[array_rand($colors)];
    echo "<text x=\"$x\" y=\"$y\" fill=\"$color\" font-family=\"Courier, monospace\" font-size=\"24\" font-weight=\"bold\" transform=\"rotate($rot $x $y)\">$char</text>";
}

echo '</svg>';
