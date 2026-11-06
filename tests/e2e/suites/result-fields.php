<?php
/** Result rendering and export generation, using only fixture data and UI forms. */
$admin->section('result-fields');
$vote = 'AnonPartyTest';
$resultUrl = "/count/result?voteID=$vote&hl=zh-TW";
$readFields = function ($url, $ids) use ($admin) {
    $admin->go($url);
    return $admin->js('(() => { const out = {}; for (const id of '.json_encode($ids).') {
        const e = document.getElementById(id); if (!e) throw new Error("Missing field: " + id);
        out[id] = e.value;
    } return out; })()');
};
$saveFields = function ($url, $values) use ($admin) {
    $admin->go($url);
    $admin->js('(() => { for (const [id, value] of Object.entries('.json_encode($values).')) {
        const e = document.getElementById(id); if (!e) throw new Error("Missing field: " + id);
        e.value = value;
    } })()');
    $admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '修改')");
    $admin->waitLoad();
    if (!$admin->waitFor("![...document.querySelectorAll('.invalid-feedback,.help-block')].some(e => e.offsetParent !== null && e.innerText.trim())", 1000)) {
        throw new RuntimeException('Form validation failed: '.implode(' | ', $admin->flashes()));
    }
};
$originalResults = $readFields($resultUrl, ['formresultsconfig-showfieldsort']);
$count = "[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '開票')";
if ($admin->js("!!($count)")) {
    $admin->click($count);
    $admin->waitLoad();
}
$admin->go('/ballot-work/result?voteID='.$vote.'&hl=zh-TW');
$candidateId = $admin->js('document.querySelector("tbody tr[data-key]")?.getAttribute("data-key")');
if (!$candidateId) {
    throw new RuntimeException('No visible result candidate');
}
$manualUrl = "/candi/manual?voteID=$vote&action=update&id=$candidateId&hl=zh-TW";
$admin->go($manualUrl);
$questionId = $admin->js('document.querySelector("#formcandidata-questionid").value');
$configUrl = "/candi/config?voteID=$vote&questionID=$questionId&hl=zh-TW";
$originalConfigs = [];
// Result grids use the first question's labels; candidate lists and print use their own question.
foreach (array_unique([$configUrl, "/candi/config?voteID=$vote&questionID=1&hl=zh-TW"]) as $url) {
    $originalConfigs[$url] = $readFields($url, ['formcandiconfig-showfieldsort', 'formcandiconfig-othercolnamea', 'formcandiconfig-othercolnameae']);
}
$candidateOriginal = null;
try {
    foreach ($originalConfigs as $url => $originalConfig) {
        $fields = json_decode($originalConfig['formcandiconfig-showfieldsort'], true);
        foreach (['otherColA', 'otherColB'] as $field) {
            if (!in_array($field, array_column($fields, 'id'))) {
                $fields[] = ['id' => $field, 'children' => []];
            }
        }
        $saveFields($url, [
            'formcandiconfig-showfieldsort' => json_encode($fields),
            'formcandiconfig-othercolnamea' => 'P16LabelZh<br><b>R&D</b>',
            'formcandiconfig-othercolnameae' => 'P16LabelEn<br><b>R&D</b>',
        ]);
    }
    $candidateOriginal = $readFields($manualUrl, ['formcandidata-othercola', 'formcandidata-othercolae', 'formcandidata-othercolb', 'formcandidata-othercolbe']);
    $saveFields($manualUrl, [
        'formcandidata-othercola' => 'P16Zh<br><b onclick="window.p16attack=1">R&D</b>',
        'formcandidata-othercolae' => 'P16En<br><b onclick="window.p16attack=1">R&D</b>',
        'formcandidata-othercolb' => 'P16TextZh<br>R&D',
        'formcandidata-othercolbe' => 'P16TextEn<br>R&D',
    ]);
    $saved = $readFields($manualUrl, ['formcandidata-othercola', 'formcandidata-othercolb']);
    if (!str_starts_with($saved['formcandidata-othercola'], 'P16Zh') || !str_starts_with($saved['formcandidata-othercolb'], 'P16TextZh')) {
        throw new RuntimeException('Candidate markup was not saved');
    }
    $resultFields = explode(',', $originalResults['formresultsconfig-showfieldsort']);
    $saveFields($resultUrl, ['formresultsconfig-showfieldsort' => implode(',', array_unique(array_merge($resultFields, ['otherColA', 'otherColB'])))]);
    $admin->go($resultUrl);
    $count = "[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.trim() === '開票')";
    if ($admin->js("!!($count)")) {
        $admin->click($count);
        $admin->waitLoad();
    }
    // Administrative login and voter login are independent sessions in the app.
    $admin->go('/site/password?voteID='.$vote);
    $admin->js('document.querySelector("#passwd").value = "testN90"');
    $admin->click("[...document.querySelectorAll('button[type=submit]')].find(e => e.innerText.includes('登入'))");
    $admin->waitLoad();
    foreach (['zh-TW' => 'Zh', 'en-US' => 'En'] as $language => $locale) {
        foreach (['ballot-work/result', 'result/index', 'vote/result', 'vote/vote-detail', 'ballot/edit'] as $page) {
            $extra = $page === 'ballot/edit' ? '&party=0&ballotID=1' : '';
            if ($page === 'vote/vote-detail') {
                $extra .= '&questionID='.$questionId;
            }
            $status = $admin->go('/'.$page.'?voteID='.$vote.$extra.'&hl='.$language);
            $r = $admin->js('(() => {
                const a = [...document.querySelectorAll("td")].find(e => e.textContent.includes("P16'.$locale.'"));
                const b = [...document.querySelectorAll("td")].find(e => e.textContent.includes("P16Text'.$locale.'"));
                const h = [...document.querySelectorAll("th")].find(e => e.textContent.includes("P16Label'.$locale.'"));
                const range = document.createRange(); let lineBreak = false;
                if (a && a.querySelector("br") && a.querySelector("b")) {
                    range.selectNodeContents(a.querySelector("b"));
                    const bottom = range.getBoundingClientRect().top;
                    const text = [...a.childNodes].find(n => n.nodeType === Node.TEXT_NODE && n.textContent.includes("P16"));
                    if (text) { range.selectNodeContents(text); lineBreak = bottom > range.getBoundingClientRect().top; }
                    else { lineBreak = a.innerText.includes("\\n"); }
                }
                return {a: !!a && !!a.querySelector("br") && a.querySelector("b").textContent === "R&D",
                    lineBreak, header: !!h && !!h.querySelector("br") && h.querySelector("b").textContent === "R&D",
                    plain: !!b && !b.querySelector("br") && b.textContent.trim() === "P16Text'.$locale.'<br>R&D",
                    safe: !window.p16attack && ![...document.querySelectorAll("td [onclick]")].some(e => e.getAttribute("onclick").includes("p16attack"))};
            })()');
            $admin->check("$page $language: safe markup and plain text", $status === 200 && !in_array(false, $r, true), json_encode($r));
            $admin->shot('result-fields-'.str_replace('/', '-', $page).'-'.$language);
            if (in_array($page, ['ballot-work/result', 'result/index'])) {
                foreach (['csv' => 'exportTEXT', 'xls' => 'exportEXCEL', 'html' => 'exportHTML'] as $type => $method) {
                    $export = $admin->js('(() => {
                        const link = document.querySelector("a.export-'.$type.'");
                        const plugin = link && jQuery(link).data("gridexport");
                        if (!plugin) throw new Error("Export plugin missing: '.$type.'");
                        let out; const original = plugin.download;
                        try { plugin.download = (type, content) => { out = content; }; plugin.'.$method.'("'.$type.'"); }
                        finally { plugin.download = original; }
                        return out;
                    })()');
                    $admin->check("$page $language: $type export content", is_string($export) && str_contains($export, 'P16'.$locale) && !str_contains($export, 'p16attack') && !str_contains($export, 'R&amp;amp;D') && ($type !== 'csv' || str_contains($export, 'R&D')));
                    $file('result-fields-'.str_replace('/', '-', $page).'-'.$language.'.'.$type, $export ?? '');
                }
            }
        }
    }
    $admin->go('/ballot/print?voteID='.$vote.'&questionID='.$questionId.'&hl=zh-TW');
    $admin->check('Printed ballot keeps both languages and safe breaks', $admin->js('document.body.innerText.includes("P16Zh") && document.body.innerText.includes("P16En") && !document.querySelector("b[onclick*=p16attack]") && !!document.querySelector("td br")'));
    $admin->shot('result-fields-print');
} finally {
    // Restore values before removing the temporary fields from the configuration.
    if ($candidateOriginal !== null) {
        $saveFields($manualUrl, $candidateOriginal);
    }
    $saveFields($resultUrl, $originalResults);
    foreach ($originalConfigs as $url => $originalConfig) {
        $saveFields($url, $originalConfig);
    }
}
