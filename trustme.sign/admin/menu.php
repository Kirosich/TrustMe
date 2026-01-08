<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Загружаем языковые файлы
Loc::loadMessages(__FILE__);

// Проверяем права доступа
if (!$USER->IsAdmin()) {
    return;
}

// Добавляем пункт меню
$aMenu = array(
    'parent_menu' => 'global_menu_settings',
    'section' => 'trustme_sign',
    'sort' => 100,
    'text' => Loc::getMessage('TRUSTME_SIGN_MENU_TITLE'),
    'title' => Loc::getMessage('TRUSTME_SIGN_MENU_TITLE'),
    'url' => 'trustme_sign_settings.php?lang=' . LANGUAGE_ID,
    'icon' => 'trustme_sign_menu_icon',
    'page_icon' => 'trustme_sign_page_icon',
    'items_id' => 'menu_trustme_sign',
    'items' => array(
        array(
            'text' => Loc::getMessage('TRUSTME_SIGN_MENU_SETTINGS'),
            'title' => Loc::getMessage('TRUSTME_SIGN_MENU_SETTINGS'),
            'url' => 'trustme_sign_settings.php?lang=' . LANGUAGE_ID,
            'more_url' => array(
                'trustme_sign_settings.php',
            ),
        ),
        array(
            'text' => Loc::getMessage('TRUSTME_SIGN_MENU_WEBHOOK'),
            'title' => Loc::getMessage('TRUSTME_SIGN_MENU_WEBHOOK'),
            'url' => 'trustme_sign_webhook_setup.php?lang=' . LANGUAGE_ID,
            'more_url' => array(
                'trustme_sign_webhook_setup.php',
            ),
        ),
    ),
);

return $aMenu;
