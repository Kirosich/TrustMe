<?php

// Описание Activity для .description.php
$MESS["CBP_TRUSTME_GETINFO_DESCR_NAME"] = "Trust Me: Получить информацию о договоре";
$MESS["CBP_TRUSTME_GETINFO_DESCR_DESCR"] = "Получает информацию о договоре из Trust Me и добавляет товарные позиции в сделку";

// Категория
$MESS["CBP_TRUSTME_CATEGORY_ID"] = "trustme";
$MESS["CBP_TRUSTME_CATEGORY_NAME"] = "Trust Me";

// Возвращаемые значения
$MESS["CBP_TRUSTME_GETINFO_RESULT_SUCCESS"] = "Успешно выполнено";
$MESS["CBP_TRUSTME_GETINFO_RESULT_ERROR"] = "Текст ошибки";
$MESS["CBP_TRUSTME_GETINFO_RESULT_ITEMS_COUNT"] = "Количество добавленных позиций";

// Ошибки
$MESS["CBP_TRUSTME_ERROR_MODULE_NOT_FOUND"] = "Модуль trustme.sign не установлен";
$MESS["CBP_TRUSTME_ERROR_NO_TOKEN"] = "Не указан токен API Trust Me";
$MESS["CBP_TRUSTME_GETINFO_ERROR_NO_CONTRACT_ID"] = "Не указан ID договора";
$MESS["CBP_TRUSTME_GETINFO_ERROR_NO_DEAL_ID"] = "Не указан ID сделки";
$MESS["CBP_TRUSTME_GETINFO_ERROR_PARSE"] = "Ошибка парсинга данных договора";
$MESS["CBP_TRUSTME_GETINFO_ERROR_API"] = "Ошибка API Trust Me: %s";
$MESS["CBP_TRUSTME_GETINFO_ERROR_UNKNOWN"] = "Неизвестная ошибка";
$MESS["CBP_TRUSTME_GETINFO_ERROR_EXCEPTION"] = "Исключение: %s";
$MESS["CBP_TRUSTME_GETINFO_ERROR_INVALID_DEAL_ID"] = "Некорректный ID сделки";
$MESS["CBP_TRUSTME_GETINFO_ERROR_CRM_MODULE"] = "Модуль CRM не установлен";
$MESS["CBP_TRUSTME_GETINFO_ITEM_ERROR"] = "Ошибка добавления товара '%s': %s";

// Сообщения логирования
$MESS["CBP_TRUSTME_GETINFO_START"] = "Запрос информации о договоре: %s";
$MESS["CBP_TRUSTME_GETINFO_SUCCESS"] = "Успешно добавлено товарных позиций: %d из %d";
$MESS["CBP_TRUSTME_GETINFO_NO_ITEMS"] = "Товарные позиции не найдены в договоре";
$MESS["CBP_TRUSTME_GETINFO_ITEM_ADDED"] = "Добавлена позиция: %s (количество: %s, цена: %s)";
$MESS["CBP_TRUSTME_GETINFO_COMMENT_TITLE"] = "Trust Me: Добавлены товарные позиции";
$MESS["CBP_TRUSTME_GETINFO_COMMENT_ADDED"] = "Комментарий добавлен в сделку";
