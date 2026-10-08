<?php

$title = Yii::t('app', '計票單');
$this->title = $title;

echo $this->render('@app/views/count/_count', compact(
    'title', 'model', 'ballotList', 'ballotCountSort', 'ballotCountAry', 
    'passwordList', 'showExportModal'
));