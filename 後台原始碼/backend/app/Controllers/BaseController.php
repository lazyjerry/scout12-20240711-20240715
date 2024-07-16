<?php

namespace App\Controllers;

// use App\Models\SettingsModel;
use App\Libraries\TimeUtils;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * API 文件說明，請點開查看細節.
 *
 * 此為 phpdoc 改寫成作為溝通 restful API 使用，此份文件將放在 git 根目錄下 /docs/Document/index.html 中，開啓即可閱讀。以下說明閱讀規則.
 * <ul>
 *     <li>
 *         標示 API 路徑.<br>
 *         請參照 <b>path</b> 標籤。注意網址路徑皆以陀峰命名（英數大小寫）。
 *     </li>
 *     <li>
 *         request 參數說明.<br>
 *         需要輸入參數的 api 統一在 body 中帶 json-string 參數，例如：<br> 皆以陀峰命名（英數大小寫命名為原則。
 *         <code>
 * {<br>
 *- "username": "hello",<br>
 *- "password": "1qaz@WSX"<br>
 * }<code><br>
於文件 tag 中標記 <b>uses</b> 呈現。如果有需要，也可以以 post form 方法呼叫。
 *     </li>
 *     <li>
 *         response 回覆說明.<br>
 *         回覆統一以 json-string 格式表示，盡量以範例格式提供說明。<br> 除了 data 之內為主要 payload 之外，其他欄位固定存在。<br>
 * {<br>
 *- "status": 201, <small><b>// 這是 http status code 狀態碼，通常會是 200, 201，如果不是 20x 代碼則為錯誤。</small></b><br>
 *- "error": "如果有錯誤，這裡會提供錯誤代碼",<br>
 *- "message": "這是回覆說明文字，如果後端有需要溝通會提供該說明文字",<br>
 *- "messages": [], <small><b>//  這是回覆文字集合，如果有多個說明文字將會列在這裡</small></b><br>
 *- "data": { <small><b>//  這是主要回覆內容 payload 以下以登入訊息回覆做範例</small></b><br>
 *--   "user": {<br>
 *---     "username": "hello",<br>
 *---     "user_slug": "69f2c0f896d3889fac562f48ddb0d251",<br>
 *---     "first_name": "Jerry",<br>
 *---     "last_name": "Lin",<br>
 *---     "user_role": "admin"<br>
 *--   },<br>
 *--   "token": "xxxooooxxxxooooxxx"<br>
 *- },<br>
 *- "responseTime": 1684080951 <small><b>// 回覆時間，可作為快取參考 </small></b><br>
 * }<br>
 * <br> 於文件中說明時，以 data 內的格式為主，於文件 tag 中標記 <b>response</b> 呈現， json 階層中間以 `.` 表示，例如 <small>user.username</small> 表示為 user 中的 username 欄位。 <b>return </b> 標記為該項目的說明。
 *     </li>
 *     <li>
 *     文件中除 <b>uses</b> 與 <b>response</b> 之外，<b>see</b>和 <b>note</b> 為額外參考說明，故名思義為外部參考連結與注意事項。
 *     以下另外提供 example() 方法說明格式。
 *     </li>
 * </ul>
 *
 * @package ReadMeFirst
 *
 */
class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     * @internal
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    protected $pageData = [];

    /**
     * 這是一個範例方法，實際上並不可用.
     * 這是範例方法的描述，該方法實際上並不可用。
     * 下方 Tags 中 <b>uses</b> 顯示為接受的參數型別和說明；<br><b>response</b> 為返回的參數型別與說明。
     * @api
     * @path /api/xxxx/ 這路徑範例，在路徑前面帶上指定的 API url，請注意是否有需要 SSL
     * @uses string username 用戶帳號
     * @uses string password 用戶密碼
     * @response array user 如果 user 是多個元素陣列，將特別標示出來。反之則不會特別提及。
     * @response string user.username 用戶帳號
     * @response string user.user_slug 用戶唯一標記
     * @response string user.first_name 用戶名字
     * @response string user.last_name 用戶姓氏
     * @response string user.user_role 用戶權限，目前唯一是 admin
     * @response string user.token 用戶登入用 JWT token
     * @see https://www.yogihosting.com/jwt-jquery-aspnet-core/ JWT於前端使用方法
     * @note 部分 admin 前端可於 hader 中帶參數 "dev-auth: 0110f346b32a5725f5d71639310dad27" 自動略過 token 登入要求。<b>注意僅只有開發環境適用。</b>
     *
     *
     * @return string 返回Json-string 格式。如果成功返回 200 失敗則返回 4xx 狀態碼
     */
    public function example()
    {
        return "";
    }

    /**
     * Initializer
     * @internal
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        TimeUtils::getNow();
        if (defined('IS_CROSS_DOMAIN') && IS_CROSS_DOMAIN) {

            // header('Access-Control-Allow-Origin: *');
            header("Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        }

        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

    }

}
