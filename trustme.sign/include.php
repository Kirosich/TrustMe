<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::registerAutoLoadClasses('trustme.sign', array(
    'TrustMe\Sign\Api' => 'lib/Api.php',
    'TrustMe\Sign\Options' => 'lib/Options.php',
));
