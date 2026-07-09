-- ==========================================
-- 寫入 vuln_db 的初始種子資料
-- ==========================================
USE vuln_db;

-- 插入使用者 (弱點版：密碼直接以明文儲存)
INSERT INTO users (id, username, password_hash, role, name, email, phone, student_no, national_id_fake) VALUES
(1, 'admin', 'admin', 'admin', '系統管理員', 'admin@vulncampus.local', '0912-345-678', 'AD001', 'A123456789'),
(2, 'student01', 'password123', 'student', '王小明', 'student01@vulncampus.local', '0922-111-222', 'ST001', 'B198765432'),
(3, 'student02', 'password123', 'student', '李小美', 'student02@vulncampus.local', '0933-222-333', 'ST002', 'F299887766'),
(4, 'teacher01', 'password123', 'teacher', '鄭老師', 'teacher01@vulncampus.local', '0944-333-444', 'TE001', 'H188776655');

-- 插入課程資料
INSERT INTO courses (id, title, teacher_id, description, classroom, credit) VALUES
(1, '網頁安全防護實務', 4, '本課程介紹常見網頁弱點與安全改善，包含 OWASP Top 10 與防禦實作。', '資科館 301', 3),
(2, '資料庫系統設計', 4, '介紹關聯式資料庫設計、SQL 語法與效能最佳化。', '資科館 102', 3),
(3, '計算機網路概論', 4, '介紹網路協定、TCP/IP、路由演算法與基礎網路安全。', '工程館 204', 3);

-- 插入活動資料
INSERT INTO events (id, title, description, quota, price, status) VALUES
(1, '2026 資安挑戰賽 (VulnCTF)', '校園年度資安競技，名額有限，歡迎各路好手報名。', 30, 200, 'active'),
(2, 'OWASP ZAP 實戰工作坊', '手把手操作 OWASP ZAP 進行網站弱點掃描與修補驗證。', 3, 500, 'active'), -- 刻意限制 3 人以測試併發漏洞
(3, 'AI 與自動化防禦研討會', '探討如何利用生成式 AI 加速程式碼安全檢測。', 100, 0, 'active');

-- 插入優惠券
INSERT INTO coupons (id, code, discount_amount, max_uses, used_count) VALUES
(1, 'CAMPUS100', 100, 10, 0),
(2, 'FREE999', 999, 1, 0);

-- 插入下載檔案紀錄
INSERT INTO files (id, owner_user_id, filename, storage_path, is_public) VALUES
(1, 1, '115學年度第一學期行事曆.pdf', 'files/calendar_2026.pdf', 1),
(2, 2, '王小明_學生證影本.jpg', 'files/student01_card.jpg', 0),
(3, 1, '資料庫備份檔.sql', 'db.php.bak', 0); -- 故意將備份檔路徑指向敏感檔案以供測試

-- 插入留言板測試資料
INSERT INTO messages (id, user_id, username_display, title, content) VALUES
(1, 2, '王小明', '請教老師', '老師好，請問下週的資安挑戰賽在哪裡舉行？'),
(2, 4, '鄭老師', '回覆：請教老師', '你好，挑戰賽將在資科館 301 電腦教室舉行。');

-- 插入預設的 API Token (student01 測試用)
INSERT INTO api_tokens (id, user_id, token, expires_at) VALUES
(1, 2, 'student01-api-token-vuln-12345', '2030-12-31 23:59:59');

-- 插入打卡測試資料
INSERT INTO checkins (id, user_id, latitude, longitude, memo) VALUES
(1, 2, '25.046891', '121.516906', '王小明在台北車站打卡'),
(2, 3, '25.033964', '121.564468', '李小美在台北101大樓打卡');

-- 插入專屬儲存型 XSS 留言
INSERT INTO stored_messages (id, name, content) VALUES
(1, '系統管理員', '歡迎來到專屬預存型 XSS 演練頁面！');




-- ==========================================
-- 寫入 fixed_db 的初始種子資料
-- ==========================================
USE fixed_db;

-- 插入使用者 (密碼皆為強密碼 Bcrypt 雜湊以展現安全防禦)
-- Admin: AdminPassword!2026 ($2y$10$f8JfZXPJBFWtyoLuzRWY7.MWUA.wcJdFWFr8t2S61BpKHhvLNJaAO)
-- Student01: Student01Pass!2026 ($2y$10$iu24HTAN.TMAsufok7LCCeanUQQ3MRSWApI0qD7QF7QXTCXDCiYDq)
-- Student02: Student02Pass!2026 ($2y$10$lQXMm9oxxfYKNYJvGHTeRei0kN44kVvTIpqJxnR6bRBVmnIF5zVra)
-- Teacher01: Teacher01Pass!2026 ($2y$10$SqJ.Q/wqmmxiZZdVqjlvdeJhGFDUwicTZlZjNmuR/e8X5jAfdiZam)
INSERT INTO users (id, username, password_hash, role, name, email, phone, student_no, national_id_fake) VALUES
(1, 'admin', '$2y$10$f8JfZXPJBFWtyoLuzRWY7.MWUA.wcJdFWFr8t2S61BpKHhvLNJaAO', 'admin', '系統管理員', 'admin@vulncampus.local', '0912-345-678', 'AD001', 'A123456789'),
(2, 'student01', '$2y$10$iu24HTAN.TMAsufok7LCCeanUQQ3MRSWApI0qD7QF7QXTCXDCiYDq', 'student', '王小明', 'student01@vulncampus.local', '0922-111-222', 'ST001', 'B198765432'),
(3, 'student02', '$2y$10$lQXMm9oxxfYKNYJvGHTeRei0kN44kVvTIpqJxnR6bRBVmnIF5zVra', 'student', '李小美', 'student02@vulncampus.local', '0933-222-333', 'ST002', 'F299887766'),
(4, 'teacher01', '$2y$10$SqJ.Q/wqmmxiZZdVqjlvdeJhGFDUwicTZlZjNmuR/e8X5jAfdiZam', 'teacher', '鄭老師', 'teacher01@vulncampus.local', '0944-333-444', 'TE001', 'H188776655');

-- 插入課程資料
INSERT INTO courses (id, title, teacher_id, description, classroom, credit) VALUES
(1, '網頁安全防護實務', 4, '本課程介紹常見網頁弱點與安全改善，包含 OWASP Top 10 與防禦實作。', '資科館 301', 3),
(2, '資料庫系統設計', 4, '介紹關聯式資料庫設計、SQL 語法與效能最佳化。', '資科館 102', 3),
(3, '計算機網路概論', 4, '介紹網路協定、TCP/IP、路由演算法與基礎網路安全。', '工程館 204', 3);

-- 插入活動資料
INSERT INTO events (id, title, description, quota, price, status) VALUES
(1, '2026 資安挑戰賽 (VulnCTF)', '校園年度資安競技，名額有限，歡迎各路好手報名。', 30, 200, 'active'),
(2, 'OWASP ZAP 實戰工作坊', '手把手操作 OWASP ZAP 進行網站弱點掃描與修補驗證。', 3, 500, 'active'),
(3, 'AI 與自動化防禦研討會', '探討如何利用生成式 AI 加速程式碼安全檢測。', 100, 0, 'active');

-- 插入優惠券
INSERT INTO coupons (id, code, discount_amount, max_uses, used_count) VALUES
(1, 'CAMPUS100', 100, 10, 0),
(2, 'FREE999', 999, 1, 0);

-- 插入下載檔案紀錄
INSERT INTO files (id, owner_user_id, filename, storage_path, is_public) VALUES
(1, 1, '115學年度第一學期行事曆.pdf', 'files/calendar_2026.pdf', 1),
(2, 2, '王小明_學生證影本.jpg', 'files/student01_card.jpg', 0);

-- 插入留言板測試資料
INSERT INTO messages (id, user_id, username_display, title, content) VALUES
(1, 2, '王小明', '請教老師', '老師好，請問下週的資安挑戰賽在哪裡舉行？'),
(2, 4, '鄭老師', '回覆：請教老師', '你好，挑戰賽將在資科館 301 電腦教室舉行。');

-- 插入預設的 API Token (student01 測試用)
INSERT INTO api_tokens (id, user_id, token, expires_at) VALUES
(1, 2, 'student01-api-token-fixed-abcde', '2030-12-31 23:59:59');

-- 插入打卡測試資料
INSERT INTO checkins (id, user_id, latitude, longitude, memo) VALUES
(1, 2, '25.046891', '121.516906', '王小明在台北車站打卡'),
(2, 3, '25.033964', '121.564468', '李小美在台北101大樓打卡');

-- 插入專屬儲存型 XSS 留言
INSERT INTO stored_messages (id, name, content) VALUES
(1, '系統管理員', '歡迎來到專屬預存型 XSS 演練頁面！');


