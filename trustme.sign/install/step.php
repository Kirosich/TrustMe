<?php
/**
 * Файл step.php - страница успешной установки модуля
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/index.php');
?>

<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">

    <?php CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('TRUSTME_SIGN_INSTALL_SUCCESS'),
        'TYPE' => 'OK',
    ]); ?>

    <br>
    <input type="submit" name="" value="<?= Loc::getMessage('TRUSTME_SIGN_BACK') ?>">
</form>

