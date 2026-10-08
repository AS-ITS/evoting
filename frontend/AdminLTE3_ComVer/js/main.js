// 網頁加載後引入
jQuery(function ($)
{
    /* 右上角-深色模式按鈕(官方文檔的程式碼) */
    var toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
    var currentTheme = localStorage.getItem('theme');
    var mainHeader = document.querySelector('.main-header');

    if (currentTheme)
    {
        if (currentTheme === 'dark')
        {
            if (!document.body.classList.contains('dark-mode'))
            {
                document.body.classList.add("dark-mode");
            }
            if (mainHeader.classList.contains('navbar-light'))
            {
                mainHeader.classList.add('navbar-dark');
                mainHeader.classList.remove('navbar-light');
            }
            if (toggleSwitch)
            {
                toggleSwitch.checked = true;
            }
        }
    }

    function switchTheme(e)
    {
        if (e.target.checked)
        {
            if (!document.body.classList.contains('dark-mode'))
            {
                document.body.classList.add("dark-mode");
            }
            if (mainHeader.classList.contains('navbar-light'))
            {
                mainHeader.classList.add('navbar-dark');
                mainHeader.classList.remove('navbar-light');
            }
            localStorage.setItem('theme', 'dark');
        }
        else
        {
            if (document.body.classList.contains('dark-mode'))
            {
                document.body.classList.remove("dark-mode");
            }
            if (mainHeader.classList.contains('navbar-dark'))
            {
                mainHeader.classList.add('navbar-light');
                mainHeader.classList.remove('navbar-dark');
            }
            localStorage.setItem('theme', 'light');
        }
    }

    if (toggleSwitch)
    {
        toggleSwitch.addEventListener('change', switchTheme, false);
    }
});

/* 共用的 function */

/**
 * 從網址更新區塊
 *
 * 範例:
 *
 * ```js
 * changeBlock( '#example', 'https://www.example.com/');
 * ```
 *
 * @param {string} Id  更換區塊的ID
 * @param {string} Url 由網址內容更新區塊
 * @param {string|null} LoadingText 更新區塊前，加載的文字
 * @param {bool} aSync 是否使用異步
 */
function changeBlock( Id, Url, LoadingText=null, aSync=true)
{
    if(!LoadingText)
    {
        LoadingText = '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>';
    }
    $.ajax({
        type: 'get',
        url: Url,
        async: aSync,
        dataType: 'html',
        beforeSend: function() {
            // setting a timeout
            $(Id).html(LoadingText);
        },
        success: function(newPartialView) {
            $(Id).html(newPartialView);
            $('[data-toggle="tooltip"]').tooltip();
        },
        error: function (jqXHR, exception) {
            let msg = '';
            if (jqXHR.status === 0) {
                msg = 'Not connect.<br>Verify Network.';
            } else if (jqXHR.status == 404) {
                msg = 'Requested page not found. [404]';
            } else if (jqXHR.status == 500) {
                msg = 'Internal Server Error [500].';
            } else if (exception === 'parsererror') {
                msg = 'Requested JSON parse failed.';
            } else if (exception === 'timeout') {
                msg = 'Time out error.';
            } else if (exception === 'abort') {
                msg = 'Ajax request aborted.';
            } else {
                msg = 'Uncaught Error.<br>' + jqXHR.responseText;
            }
            let msgAlert = '<div class="alert alert-danger" role="alert">'+msg+'</div>';
            let msgDiv = '<div class="d-flex justify-content-center">'+msgAlert+'</div>';
            $(Id).html(msgDiv);
        },
    });
}

/**
 * 等待多少毫秒(千分之一秒)
 *
 * 範例:
 *
 * ```js
 * sleep(500).then(() => {
 *     console.log("已經過了0.5秒");
 * });
 * ```
 *
 * @param {int} time 休息的號秒
 */
function sleep(time) {
    // sleep time expects milliseconds
    return new Promise((resolve) => setTimeout(resolve, time));
}

/**
 * 更新網址GET參數
 *
 * 範例:
 *
 * ```js
 * console.log(updateURLParameter("https://www.newscan.com.tw/", "aaa", "bbb"));
 * // https://www.newscan.com.tw/?aaa=bbb
 *
 * console.log(updateURLParameter("https://www.newscan.com.tw/?fff=ddd&aaa=bbb", "aaa", "ccc"));
 * // https://www.newscan.com.tw/?fff=ddd&aaa=ccc
 * ```
 *
 * @param {string} url 原始完整的URL
 * @param {string} param GET參數名稱
 * @param {string} paramVal GET參數內容
 *
 * @return {string} 更新GET參數後的URL
 */
function updateURLParameter(url, param, paramVal)
{
    var TheAnchor = null;
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";

    if (additionalURL)
    {
        var tmpAnchor = additionalURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];
        if(TheAnchor)
            additionalURL = TheParams;

        tempArray = additionalURL.split("&");

        for (var i=0; i<tempArray.length; i++)
        {
            if(tempArray[i].split('=')[0] != param)
            {
                newAdditionalURL += temp + tempArray[i];
                temp = "&";
            }
        }
    }
    else
    {
        var tmpAnchor = baseURL.split("#");
        var TheParams = tmpAnchor[0];
            TheAnchor  = tmpAnchor[1];

        if(TheParams)
            baseURL = TheParams;
    }

    if(TheAnchor)
        paramVal += "#" + TheAnchor;

    var rows_txt = temp + "" + param + "=" + paramVal;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}

/**
 * 使用陣列更新網址GET參數
 *
 * 範例:
 *
 * ```js
 * console.log(updateURLParamAry("https://www.newscan.com.tw/", {"aaa": "bbb"}));
 * // https://www.newscan.com.tw/?aaa=bbb
 *
 * console.log(updateURLParamAry("https://www.newscan.com.tw/?fff=ddd&aaa=bbb&hhh=iii", {"aaa": "ccc","fff":"ggg"}));
 * // https://www.newscan.com.tw/?fff=ggg&aaa=ccc&hhh=iii
 * ```
 *
 * @param {string} url 原始完整的URL
 * @param {array} paramAry 要更新的GET參數
 *  - `key`: 參數名稱
 *  - `value`: 參數內容
 *
 * @return {string} 更新GET參數後的URL
 */
function updateURLParamAry(url, paramAry)
{
    for (const [key, value] of Object.entries(paramAry)) {
        url = updateURLParameter(url,key,value);
    }
    return url;
}

/**
 * 取得 get 參數
 */
function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(
        /[?&]+([^=&]+)=([^&]*)/gi,
        function(m,key,value) {
            vars[key] = value;
        }
    );
    return vars;
}