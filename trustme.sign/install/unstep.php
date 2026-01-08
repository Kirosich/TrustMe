<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__DIR__ . '/index.php');

if (!check_bitrix_sessid()) {
    return;
}

?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">

    <?php CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('TRUSTME_SIGN_UNINSTALL_SUCCESS'),
        'TYPE' => 'OK',
    ]); ?>

    <br>
    <input type="submit" name="" value="<?= Loc::getMessage('TRUSTME_SIGN_BACK') ?>">
</form>

