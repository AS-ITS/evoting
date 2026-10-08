<?php
/**
 * 這邊可以直接取用 .dore 的變數，因上一層導入 parameterLoader
 * 
 * use: Yii::$app->params[<You Key>]
 */

use app\interfaces\CountInterface;
use app\models\Passwd;

return [
    
    // 環境 (product/test/alpha/ws)
//    'envrmt' => $conf->envRmt,

    /**
     * ========================================
     *        特殊、臨時性(ex:未來不存在)
     * ========================================
     */

    // 可連至外網的 proxy
//    'dmz.proxy' => $conf->proxyIp,
    
    /**
     * ==================================
     *          kartik-v/yii2-grid
     * ==================================
     */
    'bsVersion' => '5.x', // this will set globally `bsVersion` to Bootstrap 5.x for all Krajee Extensions
    'bsDependencyEnabled' => true, // this will not load Bootstrap CSS and JS for all Krajee extensions

    /**
     * ==================================
     *          summernote config
     * ==================================
     */
    'summernote' => [
        'attributes' => [
            'max-height' => '500',
            'toolbar' => [
                ['style', ['style']],
                ['font', ['strikethrough', 'bold', 'underline', 'clear', 'fontsize', 'fontname', 'color']],
                ['height', ['height']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview', 'help']],
            ],
            'fontSizes' => ['8', '9', '10', '11', '12', '14', '16', '18', '20', '22', '24', '26', '28', '36', '48', '64', '72'],
            'styleTags' => ['p', 'div', 'blockquote', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
            'lineHeights' => ['0.3', '0.5', '0.8', '1.0', '1.2', '1.4', '1.5', '2.0', '3.0']
        ],
    ],

    /**
     * ==================================
     *          HtmlPurifier Config
     * ==================================123
     */
    'HtmlPurifier.config' => [
        'HTML.SafeIframe' => true,
        'HTML.TargetBlank' => true,
        'URI.AllowedSchemes' => [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'ftp' => true,
            'nntp' => true,
            'news' => true,
            'tel' => true,
        ],
        'URI.SafeIframeRegexp' => '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%'
    ],

    /**
     * ==================================
     *            Code Table
     * ==================================
     */
    
    'ct.apRoles' => [ // 權限名稱
        'sa' => '系統管理員',
        'va' => '投票活動管理者',
        'ga' => '群組管理者',
        'gm' => '群組成員'
    ],
    'ct.language' => ['英文'=>'en-US','中文'=>'zh-TW'], // 語言
    'ct.yesOrNoAry' => ['0' => '否', '1' => '是'], // 是否選項

    // 組織分組 preset（部署時自訂；與場次 parties 表並行，詳見 SETUP.md）
    // key 需與場次分組代碼／ballot 顯示對齊；各單位可改為科、課、院區等名稱
    'ct.divisionDefAry'=> ['0'=>'def','1'=>'0','3'=>'0','4'=>'1'],
    'ct.division0Ary'  => ['def' => '預設'],
    'ct.division0EAry' => ['def' => 'Default'],
    'ct.division1Ary'  => ['1' => '院本部', '2' => '數理科學組', '3' => '生命科學組', '4' => '人文及社會科學組'],
    'ct.division1EAry' => [
        '院本部' => 'Academia Sinica', 
        '數理科學組' => 'Division of Mathematics and Physical Sciences', 
        '生命科學組' => 'Division of Life Sciences', 
        '人文及社會科學組' => 'Division of Humanities and Social Sciences'
    ], // 英
    'ct.division3Ary'  => ['0' => '工程科學組', '1' => '數理科學組', '2' => '生命科學組', '3' => '人文及社會科學組'],
    'ct.division3EAry' => ['0' => 'aengine', '1' => 'bmath', '2' => 'clife', '3' => 'dhuman'], // 英
    'ct.division4Ary'  => ['1' => '數理科學組', '2' => '生命科學組', '3' => '人文及社會科學組'],
    'ct.division4EAry' => ['1' => 'amath', '2' => 'blife', '3' => 'chuman'], // 英

    // ===== group ======
    'ct.group.routineAry' => ['Y' => '例行投票', 'N' => '非例行投票'],
    'ct.group.writeAry' => ['Y' => '允許編輯群組投票', 'N' => '禁止編輯群組投票'],
    'ct.group.ownerAry' => ['Y' => '允許編輯群組', 'N' => '禁止編輯群組'],

    // ===== vote ======
    'ct.voteType' => ['0' => '表決(無須驗證)', '1' => '匿名(亂數密碼)'], // 投票類型
    'ct.voteProcessAry' => ['0'=>'狀態中止', '1'=>'等待投票', '2'=>'開始投票', '3'=>'等待驗證', '4'=>'開始驗證', '5'=>'等待開票', '6'=>'完成投票', '7' => '補登投票'], // 投票流程
    'ct.activeAry' => ['0' => '等待', '1' => '進行', '2' => '截止', '3' => '補登'], // 投票狀態
    'ct.homeLayoutAry' => ['main' => '公版', 'meeting' => '會議版'], // 首頁樣版
    'ct.patternAry' => ['main' => '公版', 'meeting' => '會議版'], // 投票樣版
    'ct.loginLayoutAry' => ['main' => '公版', 'meeting' => '會議版'], // 登入樣版
    'ct.addiCondAry' => ['n' => '無', 'female' => '女性保留名額'], // 投票畫面的序號顯示
    'ct.finishPage' => ['1' => '首頁', '2' => '投票資訊頁'], // 投票完成跳轉頁面
    'ct.candiConfig' => ['1' => '依投票場次統一', '2' => '依問題個別設定'], // 候選人配置
    'ct.questionsRules' => [
        '1' => '所有問題皆未選擇，所有問題皆視為廢票', 
        '2' => '只要有一個問題廢票，所有問題皆視為廢票',
        '3' => '所有問題皆未選擇或只要有一個問題廢票，所有問題皆視為廢票'
    ], // 特殊規則管理

    // ===== votes ======
    'ct.votes.isByPartyAry' => ['0'=>'所有分組', '1'=>'僅限同分組'], // 是否依組別看選票

    // ===== candi ======
    'ct.votes.NameListAry' => [ // 自然人
        '0' => '投票候選名單', '1' => '投票候選人名單'
    ],
    'ct.candi.jobLctnAry' => ['0'=>'不分地點', '1'=>'國內', '2'=>'國外'],// 工作地點
    'ct.candi.jobLctnEAry' => ['0'=>'any', '1'=>'Domestic', '2'=>'Overseas'],// 工作地點(英文)
    'ct.showNumAry' => ['A' => '顯示自動編號', 'C' => '顯示自定義編號'], // 投票畫面的序號顯示
    'ct.candi.fieldName' => [ // 所有可顯示的候選人欄位名稱，名字/名稱 為特例
        'headerA' => '欄位表頭名稱 1', 'headerB' => '欄位表頭名稱 2', 'headerC' => '欄位表頭名稱 3',
        'num' => '編號', 'party' => '分組', 'questionID' => '問題', 'instName' => '單位名稱',
        'title' => '職稱', 'Name' => '名稱', 'sex' => '性別', 'jobLctn' => '工作地點', 'photo' => '照片',
        'backgroundColor' => '背景顏色',
        'otherColA' => '自定義欄位名稱 1', 'otherColB' => '自定義欄位名稱 2',
        'otherColC' => '自定義欄位名稱 3', 'otherColD' => '自定義欄位名稱 4',
        'otherColE' => '自定義欄位名稱 5', 'otherColF' => '自定義欄位名稱 6',
        'relateParty' => '關聯組別', 'specialHonor' => '特殊榮譽'
    ],
    'ct.candi.fieldSortName' => [ // 所有可排序的候選人欄位名稱，名字/名稱 為特例
        'orderNum' => '名稱(候選名單順序)',
        'otherColA' => '自定義欄位名稱 1', 'otherColB' => '自定義欄位名稱 2',
        'otherColC' => '自定義欄位名稱 3', 'otherColD' => '自定義欄位名稱 4',
        'otherColE' => '自定義欄位名稱 5', 'otherColF' => '自定義欄位名稱 6',
    ],
    'ct.sortAry' => [ // 排序方式
        '1' => '手動新增', 
        '2' => '條件導入'
    ],
    'ct.candi.genModeAry' => [ // 新增候選人模式
        'manual' => '手動新增', 
        'condition' => '條件導入', 
        'csvfile' => '檔案導入',
        'round' => '輪次導入'
    ],
    'ct.candi.sexAry' => ['0' => '女', '1' => '男'], // 性別
    'ct.candi.sexEAry' => ['0' => 'Female', '1' => 'Male'], // 性別
    'ct.candi.width' => ['1' => '窄', '2' => '寬'], // 寬度

    // ===== ballots ======
    'ct.ballots.onJobAry'  => ['0' => '離職', '1' => '在職'],
    'ct.ballots.selectNum' => ['0' => '廢票', '1' => '有效票'],

    // ===== passwd ======
    'ct.passwd.dtrackAry'  => ['0'=>'紙本投票', '1'=>'網路投票'],
    'ct.passwd.validAry' => ['0' => '未啟用', '1' => '啟用'],
    'ct.passwd.typeAry' => [
        'int' => '數字', 
        'en'  => '英文(含大小寫)',
        'mixLower' => '英數混合(英文只有小寫), 但排除 b、o、l、0、1',
        'mixUpper' => '英數混合(英文只有大寫), 但排除 I、O、0、1',
        'mix' => '英數混合(英文含大小寫)',
        'mixExcl' => '英數混合(英文含大小寫), 但排除 b、o、l、I、O、0、1',
    ],
    // ===== count ======

    // ===== result ======
    'ct.result.electedAry'  => [
        'E' => '當選',// Elected
        'W' => '遞補',// Wait
        'LE' => '未當選',// Lost an Elected
        'CE' => '待確認當選', // To be confirmed elected
        'CW' => '待確認遞補', // To be confirmed wait
    ],
    'ct.result.electedSortAry'  => [
        'E' => '當選',// Elected
        'CE' => '待確認當選', // To be confirmed elected
        'W' => '遞補',// Wait
        'CW' => '待確認遞補', // To be confirmed wait
        'LE' => '未當選',// Lost an Elected
    ],
    'ct.result.sortAry'  => [
        'N' => '依得票高低排序',  // Number of ballots
        'L' => '依字首筆劃排序',  // Last name strokes
        'I' => '依候選名單排序'
    ],
    'ct.result.sortAryE'  => [
        'N' => 'ordered by the vote count',  // Number of ballots
        'L' => 'ordered by the number of strokes',  // Last name strokes
        'I' => 'ordered by the candidate list'
    ],
    // 依院士會議需求: 依候選名單排序文字改為依姓氏筆劃排序
    'ct.result.sortPrintText'  => [
        'N' => '依得票高低排序',  // Number of ballots
        'L' => '依字首筆劃排序',  // Last name strokes
        'I' => '依姓氏筆劃排序'
    ],
    'ct.result.fieldName' => [ // 所有可顯示的候選人欄位名稱，名字/名稱 為特例
        'rank' => '排序', 'ballotCounts' => '得票數', 'elected' => '當選與否', 'comment' => '備註',
    ],

    // ===== log ======
    'log.type' => [
        '100' => '投票建立',
        '101' => '投票建立失敗',
        '102' => '投票編輯',
        '103' => '投票編輯失敗',
        '104' => '投票另存',
        '105' => '投票另存失敗',
        '106' => '投票重啟',
        '107' => '投票重啟失敗',
        '108' => '投票刪除',
        '110' => '投票基本資料編輯',
        '111' => '投票基本資料編輯失敗',
        '112' => '投票短網址更新',
        '113' => '投票短網址更新失敗',
        '120' => '投票分組編輯',
        '121' => '投票分組編輯失敗',
        '130' => '投票者限制編輯',
        '131' => '投票者限制編輯失敗',
        '140' => '投票附件上傳',
        '141' => '投票附件上傳失敗',
        '142' => '投票附件刪除',
        '143' => '投票附件刪除失敗',
        '150' => '候選人配置建立',
        '151' => '候選人配置建立失敗',
        '152' => '候選人配置編輯',
        '153' => '候選人配置編輯失敗',
        '154' => '候選人手動新增',
        '155' => '候選人手動新增失敗',
        '156' => '候選人編輯',
        '157' => '候選人編輯失敗',
        '158' => '候選人條件方式新增',
        '159' => '候選人條件方式新增失敗',
        '15A' => '候選人匯入方式新增',
        '15B' => '候選人匯入方式新增失敗',
        '15C' => '候選人刪除',
        '15D' => '候選人刪除失敗',
        '15E' => '候選人輪次導入',
        '15F' => '候選人輪次導入失敗',
        '160' => '密碼產生',
        '161' => '密碼產生失敗',
        '162' => '密碼刪除',
        '163' => '密碼刪除失敗',
        '164' => '密碼設定狀態',
        '165' => '密碼設定狀態失敗',
        '166' => '密碼匯出',
        '167' => '密碼列表明文解鎖',
        '168' => '密碼列表明文鎖定',
        '170' => '選票建立',
        '171' => '選票建立失敗',
        '172' => '選票編輯',
        '173' => '選票編輯失敗',
        '174' => '選票刪除',
        '175' => '選票刪除失敗',
        '176' => '選票匯出',
        '177' => '選票匯入',
        '178' => '選票匯入失敗',
        '180' => '開票設定建立',
        '181' => '開票設定建立失敗',
        '182' => '開票設定編輯',
        '183' => '開票設定編輯失敗',
        '184' => '計票單匯出',
        '190' => '開票',
        '191' => '開票失敗',
        '192' => '重新開票',
        '193' => '重新開票失敗',
        '194' => '刪除開票',
        '195' => '刪除開票失敗',
        '200' => '問題新增',
        '201' => '問題新增失敗',
        '202' => '問題編輯',
        '203' => '問題編輯失敗',
        '204' => '問題刪除',
        '205' => '問題刪除失敗',
        '206' => '問題全部刪除',
        '207' => '問題匯入',
        '208' => '問題匯入失敗',
        '300' => '群組新增',
        '301' => '群組新增失敗',
        '302' => '群組編輯',
        '303' => '群組編輯失敗',
        '310' => '群組成員建立',
        '311' => '群組成員建立失敗',
        '312' => '群組成員編輯',
        '313' => '群組成員編輯失敗',
        '314' => '群組成員刪除',
        '315' => '群組成員刪除失敗',
        '400' => '網站設定修改',
        '401' => '網站設定修改失敗',
        '410' => '使用者建立',
        '411' => '使用者建立失敗',
        '412' => '使用者編輯',
        '413' => '使用者編輯失敗',
        '414' => '使用者刪除',
        '415' => '使用者刪除失敗',
        '416' => '管理員登入',
        '417' => '管理員登入失敗',
        '418' => '管理員修改密碼',
        '419' => 'SQL 查詢',
        '420' => 'Console 危險命令已阻擋',
        '421' => 'Console 危險命令覆寫允許',
        '422' => '管理員啟用 TOTP',
        '423' => '管理員停用 TOTP',
        '424' => '系統管理員同步 RBAC 權限',
        '500' => '匿名密碼登入',
        '501' => '匿名密碼登入失敗',
        '502' => '記名密碼登入',
        '503' => '記名密碼登入失敗',
        '601' => '建立新一輪投票',
        '602' => '建立新一輪投票失敗',
        '603' => '輪次編輯',
        '604' => '輪次編輯失敗',
        '605' => '輪次切換',
        '606' => '輪次切換失敗',
        '701' => '問題特殊規則新增',
        '702' => '問題特殊規則新增失敗',
        '703' => '問題特殊規則修改',
        '704' => '問題特殊規則修改失敗',
        '801' => '樣板新增',
        '802' => '樣板新增失敗',
        '803' => '樣板修改',
        '804' => '樣板修改失敗',
        '805' => '樣板刪除',
        '806' => '樣板刪除失敗',
    ],
];