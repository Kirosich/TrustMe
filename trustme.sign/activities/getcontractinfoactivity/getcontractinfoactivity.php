<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Активити для получения информации о договоре из Trust Me и добавления товарных позиций в сделку
 */
class CBPGetContractInfoActivity extends CBPActivity
{
    /**
     * Конструктор активити
     */
    public function __construct($name)
    {
        parent::__construct($name);

        // Определяем свойства активити
        $this->arProperties = array(
            'Title' => '',
            'ApiToken' => null,           // Токен API Trust Me
            'TestMode' => 'Y',            // Использовать тестовый сервер
            'ContractId' => null,         // ID договора (из сделки)
            'DealId' => null,             // ID сделки (текущая сделка)
        );

        // Устанавливаем типы входных свойств
        $this->SetPropertiesTypes(array(
            'ApiToken' => array(
                'Name' => GetMessage('CBP_TRUSTME_API_TOKEN'),
                'Type' => 'string',
                'Required' => 'Y',
                'Description' => GetMessage('CBP_TRUSTME_API_TOKEN_DESC'),
            ),
            'TestMode' => array(
                'Name' => GetMessage('CBP_TRUSTME_TEST_MODE'),
                'Type' => 'string',
                'Required' => 'N',
            ),
            'ContractId' => array(
                'Name' => GetMessage('CBP_TRUSTME_GETINFO_CONTRACT_ID'),
                'Type' => 'string',
                'Required' => 'Y',
                'Description' => GetMessage('CBP_TRUSTME_GETINFO_CONTRACT_ID_DESC'),
            ),
            'DealId' => array(
                'Name' => GetMessage('CBP_TRUSTME_GETINFO_DEAL_ID'),
                'Type' => 'string',
                'Required' => 'Y',
                'Description' => GetMessage('CBP_TRUSTME_GETINFO_DEAL_ID_DESC'),
            ),
        ));
    }

    /**
     * Основной метод выполнения активити
     */
    public function Execute()
    {
        // Подключаем модуль
        if (!CModule::IncludeModule('trustme.sign')) {
            $errorMessage = GetMessage('CBP_TRUSTME_ERROR_MODULE_NOT_FOUND');
            $this->WriteToTrackingService($errorMessage, 0, CBPTrackingType::Error);
            
            $this->arProperties['ResultSuccess'] = false;
            $this->arProperties['ResultErrorMessage'] = $errorMessage;
            $this->SetVariable('ResultSuccess', false);
            $this->SetVariable('ResultErrorMessage', $errorMessage);
            
            return CBPActivityExecutionStatus::Closed;
        }

        // Получаем значения свойств
        $contractId = $this->ParseValue($this->ContractId);
        $dealId = $this->ParseValue($this->DealId);
        $apiToken = $this->ParseValue($this->ApiToken);
        $testMode = $this->TestMode === 'Y';

        // Валидация
        if (empty($contractId)) {
            return $this->setError(GetMessage('CBP_TRUSTME_GETINFO_ERROR_NO_CONTRACT_ID'));
        }

        if (empty($dealId)) {
            return $this->setError(GetMessage('CBP_TRUSTME_GETINFO_ERROR_NO_DEAL_ID'));
        }

        if (empty($apiToken)) {
            return $this->setError(GetMessage('CBP_TRUSTME_ERROR_NO_TOKEN'));
        }

        try {
            // Логируем начало запроса
            $this->WriteToTrackingService(
                sprintf(GetMessage('CBP_TRUSTME_GETINFO_START'), $contractId),
                0,
                CBPTrackingType::Report
            );

            // Получаем информацию о договоре
            $api = new \TrustMe\Sign\Api($apiToken, $testMode);
            $contractData = $api->getContractInfo($contractId);

            if ($contractData === false) {
                $error = $api->getLastError();
                $errorMessage = isset($error['message']) ? $error['message'] : GetMessage('CBP_TRUSTME_GETINFO_ERROR_UNKNOWN');
                
                $this->WriteToTrackingService(
                    sprintf(GetMessage('CBP_TRUSTME_GETINFO_ERROR_API'), $errorMessage),
                    0,
                    CBPTrackingType::Error
                );
                
                return $this->setError($errorMessage);
            }

            // Парсим contract_data
            $parsedData = $this->parseContractData($contractData);
            
            if ($parsedData === false) {
                $errorMessage = GetMessage('CBP_TRUSTME_GETINFO_ERROR_PARSE');
                $this->WriteToTrackingService($errorMessage, 0, CBPTrackingType::Error);
                return $this->setError($errorMessage);
            }

            // Извлекаем товарные позиции
            $productItems = $this->extractProductItems($parsedData);
            
            if (empty($productItems)) {
                $this->WriteToTrackingService(
                    GetMessage('CBP_TRUSTME_GETINFO_NO_ITEMS'),
                    0,
                    CBPTrackingType::Report
                );
                
                $this->arProperties['ResultSuccess'] = true;
                $this->arProperties['ResultErrorMessage'] = '';
                $this->arProperties['ResultItemsCount'] = 0;
                $this->SetVariable('ResultSuccess', true);
                $this->SetVariable('ResultErrorMessage', '');
                $this->SetVariable('ResultItemsCount', 0);
                
                return CBPActivityExecutionStatus::Closed;
            }

            // Добавляем товарные позиции в сделку
            $addedCount = $this->addProductsToDeal($dealId, $productItems);

            // Записываем результаты
            $this->arProperties['ResultSuccess'] = true;
            $this->arProperties['ResultErrorMessage'] = '';
            $this->arProperties['ResultItemsCount'] = $addedCount;

            $this->SetVariable('ResultSuccess', true);
            $this->SetVariable('ResultErrorMessage', '');
            $this->SetVariable('ResultItemsCount', $addedCount);

            // Логируем успех
            $successMessage = sprintf(
                GetMessage('CBP_TRUSTME_GETINFO_SUCCESS'),
                $addedCount,
                count($productItems)
            );
            
            $this->WriteToTrackingService(
                $successMessage,
                0,
                CBPTrackingType::Report
            );
            
            // Добавляем комментарий в сделку
            $this->addCommentToDeal($dealId, $successMessage);

            return CBPActivityExecutionStatus::Closed;

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $this->WriteToTrackingService(
                sprintf(GetMessage('CBP_TRUSTME_GETINFO_ERROR_EXCEPTION'), $errorMessage),
                0,
                CBPTrackingType::Error
            );
            return $this->setError($errorMessage);
        }
    }

    /**
     * Парсинг contract_data из ответа API
     */
    private function parseContractData($contractData)
    {
        // Если contract_data уже распарсен
        if (isset($contractData['contract_data_parsed'])) {
            return $contractData['contract_data_parsed'];
        }

        // Если contract_data - строка, парсим её
        if (isset($contractData['contract_data']) && is_string($contractData['contract_data'])) {
            $parsed = json_decode($contractData['contract_data'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $parsed;
            }
        }

        return false;
    }

    /**
     * Извлечение товарных позиций из распарсенных данных
     */
    private function extractProductItems($parsedData)
    {
        $items = array();

        // Массивы для поиска товарных позиций
        $dataArrays = array('data', 'data2', 'data3', 'data4');

        foreach ($dataArrays as $arrayName) {
            if (isset($parsedData[$arrayName]) && is_array($parsedData[$arrayName])) {
                foreach ($parsedData[$arrayName] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    // Проверяем наличие обязательных полей
                    if (!isset($item['label']) || !isset($item['price'])) {
                        continue;
                    }

                    // Извлекаем количество
                    $quantity = 1;
                    if (isset($item['quantity'])) {
                        if (is_numeric($item['quantity'])) {
                            $quantity = (float)$item['quantity'];
                        } elseif (is_array($item['quantity']) && isset($item['quantity'][0]['key'])) {
                            // Для data2 структура quantity может быть массивом
                            // Пропускаем такие элементы, так как количество не определено
                            continue;
                        }
                    }

                    // Добавляем товарную позицию
                    $items[] = array(
                        'name' => $item['label'],
                        'price' => (float)$item['price'],
                        'quantity' => $quantity,
                    );
                }
            }
        }

        return $items;
    }

    /**
     * Добавление товарных позиций в сделку через API Битрикс24
     */
    private function addProductsToDeal($dealId, $productItems)
    {
        if (empty($productItems)) {
            return 0;
        }

        $addedCount = 0;
        $errors = array();

        // Получаем ID сделки
        $dealId = (int)$dealId;
        if ($dealId <= 0) {
            $this->WriteToTrackingService(
                GetMessage('CBP_TRUSTME_GETINFO_ERROR_INVALID_DEAL_ID'),
                0,
                CBPTrackingType::Error
            );
            return 0;
        }

        // Подключаем модуль CRM
        if (!CModule::IncludeModule('crm')) {
            $this->WriteToTrackingService(
                GetMessage('CBP_TRUSTME_GETINFO_ERROR_CRM_MODULE'),
                0,
                CBPTrackingType::Error
            );
            return 0;
        }

        // Получаем текущие товарные позиции сделки
        $existingProducts = array();
        $dbProducts = CCrmProductRow::GetList(
            array(),
            array('OWNER_TYPE' => 'D', 'OWNER_ID' => $dealId),
            false,
            false,
            array('ID', 'PRODUCT_NAME', 'PRICE', 'QUANTITY')
        );
        
        while ($product = $dbProducts->Fetch()) {
            $existingProducts[] = $product;
        }

        // Добавляем новые товарные позиции
        foreach ($productItems as $item) {
            try {
                // Ищем товар в каталоге по названию
                $productId = $this->findProductByName($item['name']);
                
                // Логируем результат поиска
                if ($productId > 0) {
                    $this->WriteToTrackingService(
                        sprintf('Товар "%s" найден в каталоге (ID: %d)', $item['name'], $productId),
                        0,
                        CBPTrackingType::Report
                    );
                } else {
                    $this->WriteToTrackingService(
                        sprintf('Товар "%s" не найден в каталоге, добавляем без PRODUCT_ID', $item['name']),
                        0,
                        CBPTrackingType::Report
                    );
                }
                
                $productRow = new CCrmProductRow();
                
                $fields = array(
                    'OWNER_TYPE' => 'D',
                    'OWNER_ID' => $dealId,
                    'PRODUCT_NAME' => $item['name'],
                    'PRICE' => $item['price'],
                    'QUANTITY' => $item['quantity'],
                );
                
                // Если товар найден в каталоге, добавляем его ID
                if ($productId > 0) {
                    $fields['PRODUCT_ID'] = $productId;
                }

                $result = $productRow->Add($fields);

                if ($result) {
                    $addedCount++;
                    $this->WriteToTrackingService(
                        sprintf(
                            GetMessage('CBP_TRUSTME_GETINFO_ITEM_ADDED'),
                            $item['name'],
                            $item['quantity'],
                            $item['price']
                        ),
                        0,
                        CBPTrackingType::Report
                    );
                } else {
                    // Получаем ошибку через $APPLICATION
                    global $APPLICATION;
                    $error = '';
                    if ($APPLICATION && $APPLICATION->GetException()) {
                        $exception = $APPLICATION->GetException();
                        $error = $exception->GetString();
                    }
                    if (empty($error)) {
                        $error = GetMessage('CBP_TRUSTME_GETINFO_ERROR_UNKNOWN');
                    }
                    
                    // Детальное логирование ошибки
                    $errorMsg = sprintf(
                        'Ошибка добавления товара "%s" (PRODUCT_ID: %s): %s',
                        $item['name'],
                        $productId > 0 ? $productId : 'не указан',
                        $error
                    );
                    $this->WriteToTrackingService($errorMsg, 0, CBPTrackingType::Error);
                    
                    $errors[] = sprintf(
                        GetMessage('CBP_TRUSTME_GETINFO_ITEM_ERROR'),
                        $item['name'],
                        $error
                    );
                }
            } catch (Exception $e) {
                $errors[] = sprintf(
                    GetMessage('CBP_TRUSTME_GETINFO_ITEM_ERROR'),
                    $item['name'],
                    $e->getMessage()
                );
            }
        }

        // Логируем ошибки, если есть
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->WriteToTrackingService($error, 0, CBPTrackingType::Error);
            }
        }

        // Товары добавляются автоматически, обновление сделки не требуется
        // Bitrix24 автоматически обновит сделку после добавления товарных позиций

        return $addedCount;
    }

    /**
     * Поиск товара в каталоге по названию
     * @param string $productName Название товара
     * @return int ID товара или 0 если не найден
     */
    private function findProductByName($productName)
    {
        if (empty($productName)) {
            return 0;
        }

        // Подключаем модуль CRM
        if (!CModule::IncludeModule('crm')) {
            return 0;
        }

        try {
            // Ищем товар через инфоблоки (основной способ в Bitrix24)
            if (CModule::IncludeModule('iblock')) {
                // Получаем ID каталога товаров
                $catalogId = CCrmCatalog::GetDefaultID();
                if ($catalogId > 0) {
                    // Способ 1: Точное совпадение названия
                    $dbElements = CIBlockElement::GetList(
                        array('ID' => 'ASC'),
                        array(
                            'IBLOCK_ID' => $catalogId,
                            'NAME' => $productName,
                            'ACTIVE' => 'Y',
                        ),
                        false,
                        array('nTopCount' => 1),
                        array('ID', 'NAME')
                    );

                    if ($element = $dbElements->Fetch()) {
                        return (int)$element['ID'];
                    }

                    // Способ 2: Частичное совпадение (если точное не найдено)
                    $dbElements = CIBlockElement::GetList(
                        array('ID' => 'ASC'),
                        array(
                            'IBLOCK_ID' => $catalogId,
                            '%NAME' => $productName,
                            'ACTIVE' => 'Y',
                        ),
                        false,
                        array('nTopCount' => 1),
                        array('ID', 'NAME')
                    );

                    if ($element = $dbElements->Fetch()) {
                        return (int)$element['ID'];
                    }
                }
            }

            // Альтернативный способ: через CCrmProduct (если доступен)
            if (class_exists('CCrmProduct')) {
                // Используем статический метод GetList правильно
                $arFilter = array('NAME' => $productName);
                $dbProducts = CCrmProduct::GetList(
                    array('ID' => 'ASC'),
                    $arFilter,
                    false,
                    false,
                    array('ID', 'NAME')
                );

                // Проверяем что это объект, а не строка
                if (is_object($dbProducts) && method_exists($dbProducts, 'Fetch')) {
                    if ($product = $dbProducts->Fetch()) {
                        return (int)$product['ID'];
                    }
                }
            }
        } catch (Exception $e) {
            // Игнорируем ошибки поиска
        }

        return 0;
    }

    /**
     * Добавление комментария в сделку
     */
    private function addCommentToDeal($dealId, $message)
    {
        if (empty($dealId) || $dealId <= 0) {
            return;
        }

        // Подключаем модуль CRM
        if (!CModule::IncludeModule('crm')) {
            return;
        }

        try {
            global $USER;
            $userId = $USER && $USER->GetID() ? (int)$USER->GetID() : 1;
            $dealId = (int)$dealId;

            // Создаём активность типа "Заметка" (Note) - она будет видна в сделке
            // Используем тип 5 (Activity/Note) вместо 4 (Task), чтобы избежать проблем с датами
            $now = date('Y-m-d H:i:s');
            
            $activity = new CCrmActivity();
            $activityFields = array(
                'TYPE_ID' => 5, // Activity/Note (не требует END_TIME)
                'SUBJECT' => GetMessage('CBP_TRUSTME_GETINFO_COMMENT_TITLE'),
                'DESCRIPTION' => $message,
                'START_TIME' => $now,
                'COMPLETED' => 'Y',
                'RESPONSIBLE_ID' => $userId,
                'OWNER_TYPE_ID' => 2, // Deal
                'OWNER_ID' => $dealId,
                'DIRECTION' => 1, // Incoming
            );

            $activityId = $activity->Add($activityFields, false, array('REGISTER_SONET_EVENT' => true));
            
            if ($activityId) {
                $this->WriteToTrackingService(
                    GetMessage('CBP_TRUSTME_GETINFO_COMMENT_ADDED') . ' (ID: ' . $activityId . ')',
                    0,
                    CBPTrackingType::Report
                );
            } else {
                // Логируем ошибку, если Add вернул false
                $errorMsg = 'Не удалось создать комментарий';
                // Проверяем через глобальную переменную APPLICATION
                global $APPLICATION;
                if ($APPLICATION && $APPLICATION->GetException()) {
                    $exception = $APPLICATION->GetException();
                    $errorMsg .= ': ' . $exception->GetString();
                }
                $this->WriteToTrackingService(
                    $errorMsg,
                    0,
                    CBPTrackingType::Error
                );
            }
        } catch (Exception $e) {
            // Игнорируем ошибки при создании комментария, но логируем
            $this->WriteToTrackingService(
                'Ошибка создания комментария: ' . $e->getMessage(),
                0,
                CBPTrackingType::Error
            );
        } catch (\Exception $e) {
            // Игнорируем ошибки при создании комментария
        }
    }

    /**
     * Установить ошибку и завершить выполнение
     */
    private function setError($message)
    {
        $this->arProperties['ResultSuccess'] = false;
        $this->arProperties['ResultErrorMessage'] = $message;
        $this->arProperties['ResultItemsCount'] = 0;

        $this->SetVariable('ResultSuccess', false);
        $this->SetVariable('ResultErrorMessage', $message);
        $this->SetVariable('ResultItemsCount', 0);

        $this->WriteToTrackingService($message, 0, CBPTrackingType::Error);

        // Добавляем комментарий об ошибке в сделку, если есть DealId
        $dealId = $this->ParseValue($this->DealId);
        if (!empty($dealId) && $dealId > 0) {
            $this->addCommentToDeal($dealId, 'Ошибка: ' . $message);
        }

        return CBPActivityExecutionStatus::Closed;
    }


    /**
     * Получение диалога свойств для редактора БП
     */
    public static function GetPropertiesDialog(
        $documentType,
        $activityName,
        $arWorkflowTemplate,
        $arWorkflowParameters,
        $arWorkflowVariables,
        $arCurrentValues = null,
        $formName = ''
    ) {
        // Загружаем языковые файлы для активити
        \Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

        $runtime = CBPRuntime::GetRuntime();

        $arMap = array(
            'ApiToken' => 'ApiToken',
            'TestMode' => 'TestMode',
            'ContractId' => 'ContractId',
            'DealId' => 'DealId',
        );

        if (!is_array($arCurrentValues)) {
            $arCurrentValues = array();

            $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
            if (is_array($arCurrentActivity['Properties'])) {
                foreach ($arMap as $key => $value) {
                    if (array_key_exists($value, $arCurrentActivity['Properties'])) {
                        $arCurrentValues[$key] = $arCurrentActivity['Properties'][$value];
                    }
                }
            }
        }

        return $runtime->ExecuteResourceFile(
            __FILE__,
            'properties_dialog.php',
            array(
                'arCurrentValues' => $arCurrentValues,
                'formName' => $formName,
                'arWorkflowParameters' => $arWorkflowParameters,
                'arWorkflowVariables' => $arWorkflowVariables,
                'documentType' => $documentType,
            )
        );
    }

    /**
     * Обработка данных из диалога свойств
     */
    public static function GetPropertiesDialogValues(
        $documentType,
        $activityName,
        &$arWorkflowTemplate,
        &$arWorkflowParameters,
        &$arWorkflowVariables,
        $arCurrentValues,
        &$arErrors
    ) {
        $arErrors = array();

        $arProperties = array(
            'ApiToken' => isset($arCurrentValues['ApiToken']) ? $arCurrentValues['ApiToken'] : '',
            'TestMode' => (isset($arCurrentValues['TestMode']) ? $arCurrentValues['TestMode'] : 'Y') === 'Y' ? 'Y' : 'N',
            'ContractId' => isset($arCurrentValues['ContractId']) ? $arCurrentValues['ContractId'] : '',
            'DealId' => isset($arCurrentValues['DealId']) ? $arCurrentValues['DealId'] : '',
        );

        // Проверяем обязательные поля
        if (empty(trim($arProperties['ApiToken']))) {
            $arErrors[] = array(
                'code' => 'emptyApiToken',
                'message' => GetMessage('CBP_TRUSTME_ERROR_NO_TOKEN'),
            );
        }

        if (empty(trim($arProperties['ContractId']))) {
            $arErrors[] = array(
                'code' => 'emptyContractId',
                'message' => GetMessage('CBP_TRUSTME_GETINFO_ERROR_NO_CONTRACT_ID'),
            );
        }

        if (empty(trim($arProperties['DealId']))) {
            $arErrors[] = array(
                'code' => 'emptyDealId',
                'message' => GetMessage('CBP_TRUSTME_GETINFO_ERROR_NO_DEAL_ID'),
            );
        }

        if (count($arErrors) > 0) {
            return false;
        }

        $arCurrentActivity = &CBPWorkflowTemplateLoader::FindActivityByName($arWorkflowTemplate, $activityName);
        $arCurrentActivity['Properties'] = $arProperties;

        return true;
    }
}


