這是全 12 使用的簽到系統原始碼和文件，謹限給對軟體設計有興趣的童軍伙伴。

願軟體設計與數位技術，再為童軍運動往前進步 20 年。



相關文件連結：

[API 文件](https://github.com/lazyjerry/scout12-20240711-2024075/blob/main/%E6%96%87%E4%BB%B6/%E5%85%A8%2012%20API%20%E6%96%87%E4%BB%B6.docx) | [工作坊頁面操作說明，感謝沈校長製作](https://github.com/lazyjerry/scout12-20240711-2024075/blob/main/%E6%96%87%E4%BB%B6/%E5%B7%A5%E4%BD%9C%E5%9D%8A%E7%B0%BD%E5%88%B0%E7%B0%BD%E9%80%80%E7%B3%BB%E7%B5%B1%E6%93%8D%E4%BD%9C%E8%AA%AA%E6%98%8E%EF%BC%88%E6%84%9F%E8%AC%9D%E6%B2%88%E4%BF%8A%E9%81%94%E6%B2%88%E6%A0%A1%E9%95%B7%E8%A3%BD%E4%BD%9C%E6%96%87%E4%BB%B6%EF%BC%89.pdf) | [活動單項計畫書](https://github.com/lazyjerry/scout12-20240711-2024075/blob/main/%E6%96%87%E4%BB%B6/%E7%B0%BD%E5%88%B0%E7%B0%BD%E5%87%BA%E5%96%AE%E9%A0%85%E8%A8%88%E7%95%AB%E6%9B%B8.docx) | [工作坊截圖](https://github.com/lazyjerry/scout12-20240711-2024075/tree/main/%E6%96%87%E4%BB%B6/%E5%B7%A5%E4%BD%9C%E5%9D%8A%E6%88%AA%E5%9C%96) | [後台截圖](https://github.com/lazyjerry/scout12-20240711-2024075/tree/main/%E6%96%87%E4%BB%B6/%E5%BE%8C%E5%8F%B0%E6%88%AA%E5%9C%96)

# 技術規格

結構概述：

1. Cloudfalre Worker 作為快取服務使用。
2. 後台定時拉取 Cloudflare 簽到簽出資料。
3. 工作坊於後台和 KV 上互相同步，作為工作人員登入使用。

![系統結構概述](https://raw.githubusercontent.com/lazyjerry/scout12/main/%E6%96%87%E4%BB%B6/%E7%B3%BB%E7%B5%B1%E7%B5%90%E6%A7%8B%E6%A6%82%E8%BF%B0.png "系統結構概述")


## 一、前端

1. vue3.js + vite
2. 使用模版。購買連結：<https://themeforest.net/item/hud-vue-3-bootstrap-5-admin-template/36852198>
3. 工作坊前台、組本部後台前端皆部署於 Cloudfalre Page Service

### 應用場景

1. 工作坊前端：實作簽到、簽退與列表。
2. 組本部後台：
    1. 添加/編輯/列表/匯入工作坊
    2. 添加/編輯/列表/匯入學員
    3. 添加/編輯/列表/匯入簽到紀錄。
3. 組本部後台同步網頁。實作同步呼叫同步簽到簽退紀錄動作。

## 二、第三方服務

1. Cloudflare
    1. Workers Service, KV Service, Pages Service
    2. 目的為處理高併發與大流量需求
2. Open Weather Map API
    1. 提供展示面板用途天氣資料
3. 展示工作坊簽到紀錄網頁
4. Gitlab (選用 gitlab 版控，主要配合 cloudflare 整合部署)

### 實作

1. Cloudflare Workers 與 Pages 同步工作坊資料、實作工作坊登入，與實作顯示紀錄。
2. Cloudflare KV 儲存工作坊簽到簽退行為。
3. 展示工作坊簽到紀錄網頁前端畫面包含美術由其他伙伴處理，故不列於系統內。
4. Gitlab 實作版本控管與整合 Page 服務使用。

## 三、後端

1. LEMP 環境 PHP 8.2
2. Codeigniter4.x

### 實作

1. 同步 Clodflare KV 工作坊資料、簽到簽退紀錄。
2. 組本部前端各功能：
    1. 添加/編輯/列表/匯入工作坊。
    2. 添加/編輯/列表/匯入學員。
    3. 添加/編輯/列表/匯入簽到紀錄。
3. 提供展示頁面 API：
    1. 工作坊清單。
    2. 各工作坊簽到且尚未簽退紀錄。
    3. 天氣 API （需要 api.openweathermap.org 的 API）
4. 實作開發用私密 API（應於活動開始前註解掉 or 關閉）：
    1. 快速匯入與同步工作坊（json to mysql）
    2. 快速匯入學員資料(json to mysql)
    3. 創建工作坊、簽到簽退紀錄、學員 demo 資料。
    4. 刪除/同步工作坊、簽到紀錄、學員資料。

![用例圖](https://raw.githubusercontent.com/lazyjerry/scout12/main/%E6%96%87%E4%BB%B6/%E7%94%A8%E4%BE%8B%E5%9C%96.png "用例圖")

# 優化清單

作為可能得以優化的地方，列點出來，有些項目可能會互相衝突，需考慮如何取捨配合。

## 一、技術規劃

### 資訊安全

1. 工作坊前台、組本部後台登入驗證需加強 SPA 的 token 、驗證、限制和 CSRF 避免被嘗試密碼、 XSS 或是各種竊取 token 的惡意行為。
2. 加強阻擋機器人驗證行為。

### 第三方服務 / 儲存結構

1. Cloudflare 的 KV / Worker 亦有其他廠商可替代（e.g. google sheet API, AWS），請比較使用限制、付費標準與開發成本等作為參考使用。
    1. 如需自架設服務，可將前端 endpoint 更換為自架設的服務。
2. 如預算允許，儲存設備也可以統一使用 Cloudflare 的服務。
    1. 優點：
        1. 透過 worker 全部都用 javascript 語言寫成。
        2. 直接存取紀錄，不會需要同步行為。
    2. 缺點：
        1. D1 服務需要收費。
        2. 如果使用 KV 有其寫入技術限制（ 1 request / 1 second）。存在循序的問題。
3. 調整 KV 儲存結構規劃（或是更改為其他[儲存服務](https://www.cloudflare.com/zh-tw/plans/developer-platform/)），以加速簽到簽退的動作效率。
    1. 工作坊簽到簽退行為，會有 KV 寫入限制，現今儲存結構的規劃內，如果同一個工作坊多個裝置登入，同時寫入的狀況下，已知會堵塞，會有循序的問題。
    2. 當前結構 KV 單一鍵值有儲存數量上限(25 MiB)，其字串數限制，導致學員數量有其上限。
    3. 考慮規劃 KV 紀錄細至學員層級（key: sign_工作坊_場次_學員編號 ），擷取資料時使用 list 的模式取得。但須考慮如果簽錯場次、工作坊等修改問題的流程與同步方法。
    4. 考慮使用 Cloudflare D1 服務。
4. KV 於 cloudflare 後台操作體驗不優，如預算允許建議設置 KV 刪修存讀用途的 Worker API 。

### 同步動作

同步動作是因為使用 Cloudflare KV 作為快取，組本部伺服器需抓取快取資料同步至本地做統計/修改/匯出行為。使用 pull 行為。

1. 擷取同步頁面需要優化
    1. 現場狀況來看，會有場次重疊的問題，同時需要多開不同場次的擷取行為。
    2. 是否可以改成後端處理？但須確保組本部方便監控查看。
    3. 如果不使用快取服務的處理，即不需要擷取同步。 但需要有更多硬體成本處理高併發問題。

## 二、活動流程

1. 增加後台「刪除」的行為。
    1. 刪除實作：工作坊、學員、簽到簽退紀錄功能。
    2. 請注意如果需要刪除簽到簽退紀錄，需實作雙向同步功能。
2. 組本部後台，學員頁面查詢條件顯示資料：
    1. 新增顯示欄位：該學員完成的工作坊/場次。
    2. 快速跳轉該學員的參與工作坊場次紀錄（驗證用）
3. 如有需要，實作組本部後台的用戶管理功能。
    1. 刪修存讀管理員資料、管理員列表。
4. 如有需要，實作組本部後台批次匯入學員資料功能。
    1. 一般操作頻率很低，如果有該功能，則不需要開發人員操作。
5. 時間問題：
    1. 同步簽到簽退資料時，可能存在工作坊活動超時的問題，需要同步前一個場次。
    2. 前一場工作坊太晚結束，操作簽退/簽到錯誤，可能導致誤判場次。
    3. 場次過早開始，操作簽到/簽退錯誤，導致誤判場次。
    4. 也許場次間添加緩衝時間能夠解決。實作上強制緩衝時間過後簽到都會換場。
6. 有很多人不願意簽退。
    1. 簽退的需求需要考慮，是否真的需要簽退？可能會因為場次選擇錯誤，導致人數統計落差。
    2. 簽退是否需要綁定「必須先簽到」才能簽退？ 這樣避免簽退時選錯場次。
    3. 如有紙本蓋章（報到證明）的機制，需考慮一起規劃至系統之中。
        1. 學員電子蓋章？（自己的簽到紀錄視覺化）
7. 規劃場次可能會選錯的問題。
    1. 擬移除簽退動作，簽退操作的需求可能不高，可能產生錯誤的機會太高了。
    2. 是否場次可以自動判斷？ 與時間綁定會有活動時間重疊的問題。
    3. 同時間問題，是否添加緩衝時間？這樣也許不用選場次？？
8. 需要新增一個登記錯誤工作坊、錯誤場次的修復流程。
9. 後台搜尋時間範圍的欄位，要新增搜尋「簽到時間」的欄位，如有需要也增加「簽出時間」。
10. 系統需要更多的應用需求。雖然這會增加規劃/開發/測試的時間和難度，但是這應該是目前推廣數位化童軍運動的重要方向。例如：
    1. 提供給營報/媒體做為公關素材。
    2. 提供給工作坊鼓勵（需要創造適合的 KPI）

## 三、Bug 待修

1. 組本部後台首頁資料雜亂，需要重新規劃首頁想要長什麼樣子。
    1. 擬改成擷取頁面的開啟按鈕（依照各場次分類顯示按鈕，點擊另開）

## 四、人員規劃

1. 如果人力與預算允許，能組建建置較為完整的資訊團隊。包含企劃、美術、前端與後端，產品會更加完善，企劃也能更好規劃出測試流程給其他伙伴配合。
2. 操作後台的人員規劃 2 名左右，儘量維持至少一名在線上，一名作代理。
    1. 不過需注意後台網址、帳號密碼不應外流。

# 部署與開發須知

列出現有系統部署與開發的時候需要用到的技術，以及簡述一下部署流程。

## 一、需要熟悉的服務

除基本 Web Application 技能之外，提供以下參考：

1. Cloudflare Service （javascipt for node.js）
    1. <https://developers.cloudflare.com/pages/>
    2. <https://developers.cloudflare.com/workers/>
    3. <https://developers.cloudflare.com/kv/>
2. Vue 3 + Vite + Pinia Store … (javascript for vue 3)
    1. <https://themeforest.net/item/hud-vue-3-bootstrap-5-admin-template/36852198>
    2. <https://vuejs.org/guide/introduction.html>
3. PHP for Codeigniter4.x
    1. <https://codeigniter.com/user_guide/intro/index.html>
4. Git for Gitlab
    1. <https://about.gitlab.com/>
5. Weater API
    1. <https://openweathermap.org/api>

## 二、需要熟悉語言

1. Node.js for Cloudfalre Worker （部分 API 與 nodejs 官方不同)
2. Typescript / javascript for Vue 3 前端開發 + BootStrap 5 CSS 框架（詳情參考模版內容）
3. PHP 8.2 / Codeigniter4 框架（如果需要可實作相同後端功能替換）

## 三、部署流程

### CloudFlare 後端服務（KV、Workers）

1. Workers 使用 CLI 部署，參考：<https://developers.cloudflare.com/workers/get-started/guide/>
2. KV 部署於相同專案，參考：<https://developers.cloudflare.com/kv/get-started/>
3. KV 的 ID 等資料於 wrangler.toml 中設定。
4. 按照安裝提示，如需測試 執行 npm run dev
5. 如需部署至雲端，執行 npm run deploy
6. 確認 Cloudfalre 的 Workers and Pages 頁面，有沒有正確顯示 KV 和 Workers 服務
7. 點擊進入 Workers 服務能變更指派（子）網域。紀錄下來給前端服務設定。
8. 請確認服務是否有需要付費，參考：<https://www.cloudflare.com/zh-tw/plans/developer-platform/>

### 組本部後端服務

1. 請先架設 LEMP 服務，使用規格 PHP8.2 / MariaDB 10.11 / Nginx 1.18 / Linux CentOs 7.9
2. 使用 git 或是上傳檔案至 web 資料夾
3. Nginx 設定請參考：<https://forum.codeigniter.com/post-387807.html>
    1. 記得需排除 cros domain 問題。
4. 更新資料庫 localhost.sql 資料庫。
5. 請記得自行更換密碼。驗證密碼位置於：
    1. 後台原始碼/backend/www/app/Controllers/Auth.php:72
6. 更新 .env 檔案
    1. 複製 .env.sample
    2. 更改 顯示 XXX 的訊息以及數字。
        1. DEV_AUTH_KEY 為前端開發用密鑰，參考位置：後台原始碼/backend/www/app/Filters/AuthFilter.php:40
        2. JWT_SECRET 為登入密鑰、也用於 Cloudfalre API 驗證密鑰。
        3. CLOUDFLARE_API_URL 為 cloudfalre endpoint.
        4. WEATER_KEY 為天氣 API 密鑰。
        5. JWT_ 開頭變數為 JWT 使用，作為後台登入操作。
7. 將 JWT_SECRET md5 得到變數，進入 Cloudfalre KV 頁面中，手動添加 key-value pair
    1. key:’admin’
    2. value: JWT_SECRET 的 md5 變數
    3. md5 生成請參考：<https://www.md5hashgenerator.com/>
8. 架設 nginx 與指派網域，紀錄下來給前端服務設定。

### 前端畫面（組本部後台、工作坊使用頁面）

1. Page 部署使用 Git 發布，參考：<https://developers.cloudflare.com/pages/get-started/git-integration/>
2. 請確認目標資料夾需要是指向 vite 部署的資料夾（目前設定為 dist/ ）。
3. 請確認 page 服務的網址，可自己指定或使用預設網址。
4. 請確認後端與 Workers 網址，更改 .env 檔案
    1. 複製 .env.sample
    2. 組本部後台 的 VITE_API_URL 為後台網址
    3. 工作坊使用頁面 的 VITE_API_URL 為 Workers 網址
    4. VITE_IS_DEMO 保持 false 如有修改需要可應用。
5. 於本地開發完之後，執行 npm run build 建立 dist 資料夾。
6. git 更新，稍等幾分鐘開啟 pages 設定網址即可。

--

### 最後
[老闆面板，也就是展示在組本部的頁面](https://yun0615.github.io/myproject/) 這是宗翰委託別人開發的前端頁面。

[這是全12官網](https://www.nj12.scout.org.tw/)

[活動紀錄](https://hipster.crazyjerry.studio/post/%e5%85%a8-12-%e6%b4%bb%e5%8b%95%e7%b4%80%e9%8c%84/)

勘誤：原 repo 標記 0714，應標記為 0715 表示使用該系統的活動日期，不過應該沒人發現吧？ＸＤ
