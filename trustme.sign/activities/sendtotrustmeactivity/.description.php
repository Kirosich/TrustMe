<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Подключаем файл класса активити
require_once(__DIR__ . '/sendtotrustmeactivity.php');

$arActivityDescription = array(
    "NAME" => GetMessage("CBP_TRUSTME_DESCR_NAME"),
    "DESCRIPTION" => GetMessage("CBP_TRUSTME_DESCR_DESCR"),
    "TYPE" => array('activity', 'robot_activity'),
    "CLASS" => "CBPSendToTrustMeActivity",
    "JSCLASS" => "BizProcActivity",
    "CATEGORY" => array(
        'ID' => GetMessage("CBP_TRUSTME_CATEGORY_ID"),
        'OWN_ID' => GetMessage("CBP_TRUSTME_CATEGORY_ID"),
        'OWN_NAME' => GetMessage("CBP_TRUSTME_CATEGORY_NAME"),
    ),
    "PATH" => __FILE__,
    "RETURN" => array(
        "ResultUrl" => array(
            "NAME" => GetMessage("CBP_TRUSTME_RESULT_URL"),
            "TYPE" => "string",
        ),
        "ResultDocumentId" => array(
            "NAME" => GetMessage("CBP_TRUSTME_RESULT_DOC_ID"),
            "TYPE" => "string",
        ),
        "ResultFileName" => array(
            "NAME" => GetMessage("CBP_TRUSTME_RESULT_FILENAME"),
            "TYPE" => "string",
        ),
        "ResultSuccess" => array(
            "NAME" => GetMessage("CBP_TRUSTME_RESULT_SUCCESS"),
            "TYPE" => "bool",
        ),
        "ResultErrorMessage" => array(
            "NAME" => GetMessage("CBP_TRUSTME_RESULT_ERROR"),
            "TYPE" => "string",
        ),
    ),
);
