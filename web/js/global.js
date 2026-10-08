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
 * 這個函式將可排序元素及其巢狀子元素序列化為一個物件陣列
 * @param {*} sortable 
 * @returns 
 */ 
function serialize(sortable) {
    const nestedQuery = '.nested-sortable';
    const identifier = 'id';
    // 初始化一個空陣列以存儲序列化數據
    var serialized = [];
    // 將可排序元素的子元素轉換為一個陣列
    var children = [].slice.call(sortable.children);
    // 遍歷每個子元素
    for (var i in children) {
        // 查找子元素中的嵌套元素
        var nested = children[i].querySelector(nestedQuery);
        // 將一個包含子元素標識符及其嵌套子元素（如果有）的物件推送到序列化陣列中
        serialized.push({
            id: children[i].dataset[identifier],
            children: nested ? serialize(nested) : []
        });
    }
    // 返回序列化陣列
    return serialized;
}

/**
 * 這個函式將可排序元素及其巢狀子元素序列化為一個物件陣列
 * @param {*} sortable 
 * @returns 
 */ 
function serializeSort(sortable) {
    const identifier = 'id';
    // 初始化一個空陣列以存儲序列化數據
    var serialized = [];
    // 將可排序元素的子元素轉換為一個陣列
    var childrens = [].slice.call(sortable.children);
    // 遍歷每個子元素
    for (var i in childrens) {
        // 將一個包含子元素標識符及其嵌套子元素（如果有）的物件推送到序列化陣列中
        serialized.push({
            id: childrens[i].dataset[identifier],
        });
    }
    console.log(serialized);
    // 返回序列化陣列
    return serialized;
}

/* card spin overload */
function spinOn() {
    $(".overlay").css("display", "flex");
}

function spinOff() {
    $(".overlay").hide();
}

// 刪除bootstrap.css中的@media print
function removeBootstrapPrint() {
    var stylesheets = document.getElementsByTagName('link');
    var style;

    for (var i = 0; i < stylesheets.length; i++) {
        if (stylesheets[i].href.indexOf('bootstrap.css') >= 0) {
            style = stylesheets[i].sheet;
            break;
        }
    }

    if (style) {
        [].forEach.call(style.cssRules || [], function (aRule, aIndex) {
            if (aRule.cssText.indexOf("@media print") >= 0) {
                style.deleteRule(aIndex);
            }
        });
    }
}
/**
 * CSRF 同步：登出前刷新 meta；登入後分頁 BroadcastChannel 同步
 * （User::login() 會 getCsrfToken(true)，舊分頁 meta 會過期）
 */
(function (window, document) {
    var CHANNEL = 'voting-csrf';

    function applyCsrf(param, token) {
        if (!param || !token || !window.yii || typeof yii.setCsrfToken !== 'function') {
            return;
        }
        yii.setCsrfToken(param, token);
    }

    function broadcastCsrf(param, token) {
        if (!param || !token || typeof BroadcastChannel === 'undefined') {
            return;
        }
        try {
            var ch = new BroadcastChannel(CHANNEL);
            ch.postMessage({ type: 'csrf-refresh', param: param, token: token });
            ch.close();
        } catch (e) {}
    }

    function listenCsrfBroadcast() {
        if (typeof BroadcastChannel === 'undefined') {
            return;
        }
        try {
            var ch = new BroadcastChannel(CHANNEL);
            ch.onmessage = function (e) {
                if (e.data && e.data.type === 'csrf-refresh') {
                    applyCsrf(e.data.param, e.data.token);
                }
            };
        } catch (e) {}
    }

    function submitLogout(anchor) {
        var href = anchor.getAttribute('href');
        if (!href) {
            return;
        }
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = href;
        form.style.display = 'none';

        if (window.yii && typeof yii.getCsrfParam === 'function') {
            var csrfParam = yii.getCsrfParam();
            var csrfToken = yii.getCsrfToken();
            if (csrfParam && csrfToken) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = csrfParam;
                input.value = csrfToken;
                form.appendChild(input);
            }
        }

        document.body.appendChild(form);
        form.submit();
    }

    function syncThenLogout(anchor) {
        var url = window.votingCsrfTokenUrl;
        if (!url || typeof fetch !== 'function') {
            submitLogout(anchor);
            return;
        }

        fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('csrf-token http ' + res.status);
                }
                return res.json();
            })
            .then(function (data) {
                if (data && data.param && data.token) {
                    applyCsrf(data.param, data.token);
                    broadcastCsrf(data.param, data.token);
                }
            })
            .catch(function () {})
            .then(function () {
                submitLogout(anchor);
            });
    }

    // capture：比 yii.js 的 bubble handler 更早攔截
    document.addEventListener('click', function (e) {
        var anchor = e.target && e.target.closest ? e.target.closest('a.js-csrf-sync-logout') : null;
        if (!anchor) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        syncThenLogout(anchor);
    }, true);

    listenCsrfBroadcast();
})(window, document);
