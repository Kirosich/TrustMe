<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Подключаем файл класса активити
require_once(__DIR__ . '/getcontractinfoactivity.php');

$arActivityDescription = array(
    "NAME" => GetMessage("CBP_TRUSTME_GETINFO_DESCR_NAME"),
    "DESCRIPTION" => GetMessage("CBP_TRUSTME_GETINFO_DESCR_DESCR"),
    "TYPE" => array('activity', 'robot_activity'),
    "CLASS" => "CBPGetContractInfoActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => array(
        'ID' => GetMessage("CBP_TRUSTME_CATEGORY_ID"),
        'OWN_ID' => GetMessage("CBP_TRUSTME_CATEGORY_ID"),
        'OWN_NAME' => GetMessage("CBP_TRUSTME_CATEGORY_NAME"),
    ),
    "PATH" => __FILE__,
    "RETURN" => array(
        "ResultSuccess" => array(
            "NAME" => GetMessage("CBP_TRUSTME_GETINFO_RESULT_SUCCESS"),
            "TYPE" => "bool",
        ),
        "ResultErrorMessage" => array(
            "NAME" => GetMessage("CBP_TRUSTME_GETINFO_RESULT_ERROR"),
            "TYPE" => "string",
        ),
        "ResultItemsCount" => array(
            "NAME" => GetMessage("CBP_TRUSTME_GETINFO_RESULT_ITEMS_COUNT"),
            "TYPE" => "int",
        ),
    ),
);
