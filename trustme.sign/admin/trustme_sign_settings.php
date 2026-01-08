<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(Loc::getMessage('TRUSTME_SIGN_SETTINGS_TITLE'));

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

?>

<div class="adm-detail-content">
    <div class="adm-detail-content-wrap">
        <div class="adm-detail-content-item">
            <div class="adm-detail-content-item-block">
                <h3><?= Loc::getMessage('TRUSTME_SIGN_SETTINGS_INFO') ?></h3>
                <p><?= Loc::getMessage('TRUSTME_SIGN_SETTINGS_DESCRIPTION') ?></p>
                
                <h4><?= Loc::getMessage('TRUSTME_SIGN_SETTINGS_ACTIVITIES') ?></h4>
                <ul>
                    <li><strong>Trust Me: Отправить на подписание</strong> - отправка документа на подписание в Trust Me</li>
                    <li><strong>Trust Me: Получить информацию о договоре</strong> - получение информации о договоре и добавление товарных позиций в сделку</li>
                </ul>
                
                <h4><?= Loc::getMessage('TRUSTME_SIGN_SETTINGS_USAGE') ?></h4>
                <p><?= Loc::getMessage('TRUSTME_SIGN_SETTINGS_USAGE_TEXT') ?></p>
            </div>
        </div>
    </div>
</div>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?>

