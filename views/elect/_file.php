<?php

use yii\helpers\Url;
use yii\bootstrap5\Html;
use yii\helpers\FileHelper;
use yii\bootstrap5\ActiveForm;
use rmrevin\yii\fontawesome\FAS;

$path = FileHelper::normalizePath(realpath(Yii::getAlias('@filePool').'/candidateFile/'.$voteID));
$files = !empty($path) ? scandir($path) : [];
foreach ($files as $key => $file) {
    if (in_array($file, ['.', '..'])) {
        unset($files[$key]);
    }
}

$this->registerJs(<<<JS
    $('#fileUpload').appendTo('body');
JS
);
?>

<div class="modal fade" id="fileUpload" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="fileUploadLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileUploadLabel">附件上傳</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
            <?php
                $uploadForm = ActiveForm::begin([
                    'action' => ['upload-file', 'voteID' => $voteID],
                    'options' => [
                        'id' => 'upload',
                        'enctype' => 'multipart/form-data',
                    ]
                ]);
            ?>
            <div class="alert alert-warning" role="alert">
                ※附件數量最多為<?=$FormUploadFile::MAX_UPLOAD_COUNT?>個，單個附件最大為6MB
            </div>
            <div class="row mb-3">
                <div class="col-md">
                    <div class="card">
                        <div class="card-header">
                            <?= $uploadForm->field($FormUploadFile, 'files[]')->fileInput(['multiple' => 'true'])->label(false) ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">附件列表</h5>
                            <table class="table table-sm text-center">
                                <thead>
                                    <tr>
                                        <th scope="col">附件名稱</th>
                                        <th scope="col">動作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($files as $key => $file): ?>
                                    <tr>
                                        <td><?=$file?></td>
                                        <td>
                                            <?=
                                                Html::a(FAS::icon('trash', ['class' => 'text-danger']), 
                                                Url::to(['delete-file', 'file' => $key, 'voteID' => $voteID]), 
                                                [
                                                    'id' => 'delete-file',
                                                    'data-bs-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
                                                ])
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">關閉</button>
                <?=Html::submitButton('上傳', ['class' => 'btn btn-primary'])?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>