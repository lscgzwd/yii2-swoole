<?php
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

$this->title = '用户登录';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="k-edit-form-container">
    <h1><?=Html::encode($this->title)?></h1>
    <p>请输入用户名及密码登录</p>
    <div class="clearfix">
        <?php $form = ActiveForm::begin(['id' => 'login-form']);?>
            <?=$form->field($model, 'name')?>
            <?=$form->field($model, 'password')->passwordInput()?>
            <div class="form-group">
                <label>&nbsp;</label>
                <?=Html::submitButton('<span class="k-icon k-update"></span> 登录', ['class' => 'k-button k-button-icontext k-primary k-grid-update', 'name' => 'login-button'])?>
            </div>
        <?php ActiveForm::end();?>
    </div>
</div>
