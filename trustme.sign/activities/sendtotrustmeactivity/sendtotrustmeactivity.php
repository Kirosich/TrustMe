<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Активити для отправки документа на подписание в Trust Me
 * @see https://trustmekz.docs.apiary.io/#reference/0
 */
class CBPSendToTrustMeActivity extends CBPActivity
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
            'DocumentUrl' => null,        // URL или путь к документу
            'ContractName' => null,       // Название контракта
            'NumberDial' => null,         // Номер документа
            'SignerFio' => null,          // ФИО подписанта
            'SignerIin' => null,          // ИИН подписанта
            'SignerPhone' => null,        // Телефон подписанта
            'SignerCompany' => null,      // Компания подписанта
            'AdditionalInfo' => null,     // Дополнительная информация
            'KzBmg' => 'N',               // Использовать KZ BMG
            'FaceId' => 'N',              // Использовать Face ID
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
            'DocumentUrl' => array(
                'Name' => GetMessage('CBP_TRUSTME_DOCUMENT_URL'),
                'Type' => 'string',
                'Required' => 'Y',
                'Description' => GetMessage('CBP_TRUSTME_DOCUMENT_URL_DESC'),
            ),
            'ContractName' => array(
                'Name' => GetMessage('CBP_TRUSTME_CONTRACT_NAME'),
                'Type' => 'string',
                'Required' => 'Y',
            ),
            'SignerFio' => array(
                'Name' => GetMessage('CBP_TRUSTME_SIGNER_FIO'),
                'Type' => 'string',
                'Required' => 'Y',
            ),
            'SignerIin' => array(
                'Name' => GetMessage('CBP_TRUSTME_SIGNER_IIN'),
                'Type' => 'string',
                'Required' => 'Y',
            ),
            'SignerPhone' => array(
                'Name' => GetMessage('CBP_TRUSTME_SIGNER_PHONE'),
                'Type' => 'string',
                'Required' => 'Y',
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
        $documentUrl = $this->ParseValue($this->DocumentUrl);
        $contractName = $this->ParseValue($this->ContractName);
        $numberDial = $this->ParseValue($this->NumberDial);
        $signerFio = $this->ParseValue($this->SignerFio);
        $signerIin = $this->ParseValue($this->SignerIin);
        $signerPhone = $this->ParseValue($this->SignerPhone);
        $signerCompany = $this->ParseValue($this->SignerCompany);
        $additionalInfo = $this->ParseValue($this->AdditionalInfo);
        $apiToken = $this->ParseValue($this->ApiToken);
        $testMode = $this->TestMode === 'Y';
        $kzBmg = $this->KzBmg === 'Y';
        $faceId = $this->FaceId === 'Y';

        // Валидация
        if (empty($documentUrl)) {
            return $this->setError(GetMessage('CBP_TRUSTME_ERROR_NO_DOCUMENT'));
        }

        if (empty($signerFio) || empty($signerIin) || empty($signerPhone)) {
            return $this->setError(GetMessage('CBP_TRUSTME_ERROR_NO_SIGNER'));
        }

        if (empty($apiToken)) {
            return $this->setError(GetMessage('CBP_TRUSTME_ERROR_NO_TOKEN'));
        }

        try {
            // Логируем начало отправки
            $this->WriteToTrackingService(
                sprintf(GetMessage('CBP_TRUSTME_START_SEND'), $contractName, $signerFio),
                0,
                CBPTrackingType::Report
            );

            // Подготавливаем данные подписанта
            $requisites = array(
                array(
                    'CompanyName' => $signerCompany ? $signerCompany : $signerFio,
                    'FIO' => $signerFio,
                    'IIN_BIN' => $signerIin,
                    'PhoneNumber' => $signerPhone,
                ),
            );

            // Опции
            $options = array(
                'number_dial' => $numberDial ? $numberDial : $contractName,
                'kz_bmg' => $kzBmg,
                'face_id' => $faceId,
                'additional_info' => $additionalInfo,
            );

            // Отправляем в Trust Me
            $api = new \TrustMe\Sign\Api($apiToken, $testMode);
            $result = $api->sendToSign($documentUrl, $requisites, $contractName, $options);

            if ($result === false) {
                $error = $api->getLastError();
                return $this->setError(isset($error['message']) ? $error['message'] : GetMessage('CBP_TRUSTME_ERROR_UNKNOWN'));
            }

            // Записываем результаты
            $this->arProperties['ResultSuccess'] = true;
            $this->arProperties['ResultUrl'] = $result['url'];
            $this->arProperties['ResultDocumentId'] = $result['document_id'];
            $this->arProperties['ResultFileName'] = $result['file_name'];
            $this->arProperties['ResultErrorMessage'] = '';

            // Устанавливаем переменные
            $this->SetVariable('ResultSuccess', true);
            $this->SetVariable('ResultUrl', $result['url']);
            $this->SetVariable('ResultDocumentId', $result['document_id']);
            $this->SetVariable('ResultFileName', $result['file_name']);
            $this->SetVariable('ResultErrorMessage', '');

            // Сохраняем в глобальные переменные
            $rootActivity = $this->GetRootActivity();
            $rootActivity->SetVariable('TrustMeUrl', $result['url']);
            $rootActivity->SetVariable('TrustMeDocumentId', $result['document_id']);

            // Логируем успех
            $this->WriteToTrackingService(
                sprintf(
                    GetMessage('CBP_TRUSTME_SUCCESS'),
                    $result['url'],
                    $result['document_id']
                ),
                0,
                CBPTrackingType::Report
            );

            return CBPActivityExecutionStatus::Closed;

        } catch (Exception $e) {
            return $this->setError($e->getMessage());
        }
    }

    /**
     * Установить ошибку и завершить выполнение
     */
    private function setError($message)
    {
        $this->arProperties['ResultSuccess'] = false;
        $this->arProperties['ResultErrorMessage'] = $message;
        $this->arProperties['ResultUrl'] = '';
        $this->arProperties['ResultDocumentId'] = '';
        $this->arProperties['ResultFileName'] = '';

        $this->SetVariable('ResultSuccess', false);
        $this->SetVariable('ResultErrorMessage', $message);
        $this->SetVariable('ResultUrl', '');
        $this->SetVariable('ResultDocumentId', '');
        $this->SetVariable('ResultFileName', '');

        $this->WriteToTrackingService($message, 0, CBPTrackingType::Error);

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
            'DocumentUrl' => 'DocumentUrl',
            'ContractName' => 'ContractName',
            'NumberDial' => 'NumberDial',
            'SignerFio' => 'SignerFio',
            'SignerIin' => 'SignerIin',
            'SignerPhone' => 'SignerPhone',
            'SignerCompany' => 'SignerCompany',
            'AdditionalInfo' => 'AdditionalInfo',
            'KzBmg' => 'KzBmg',
            'FaceId' => 'FaceId',
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
            'DocumentUrl' => isset($arCurrentValues['DocumentUrl']) ? $arCurrentValues['DocumentUrl'] : '',
            'ContractName' => isset($arCurrentValues['ContractName']) ? $arCurrentValues['ContractName'] : '',
            'NumberDial' => isset($arCurrentValues['NumberDial']) ? $arCurrentValues['NumberDial'] : '',
            'SignerFio' => isset($arCurrentValues['SignerFio']) ? $arCurrentValues['SignerFio'] : '',
            'SignerIin' => isset($arCurrentValues['SignerIin']) ? $arCurrentValues['SignerIin'] : '',
            'SignerPhone' => isset($arCurrentValues['SignerPhone']) ? $arCurrentValues['SignerPhone'] : '',
            'SignerCompany' => isset($arCurrentValues['SignerCompany']) ? $arCurrentValues['SignerCompany'] : '',
            'AdditionalInfo' => isset($arCurrentValues['AdditionalInfo']) ? $arCurrentValues['AdditionalInfo'] : '',
            'KzBmg' => (isset($arCurrentValues['KzBmg']) ? $arCurrentValues['KzBmg'] : 'N') === 'Y' ? 'Y' : 'N',
            'FaceId' => (isset($arCurrentValues['FaceId']) ? $arCurrentValues['FaceId'] : 'N') === 'Y' ? 'Y' : 'N',
        );

        // Проверяем обязательные поля
        if (empty(trim($arProperties['ApiToken']))) {
            $arErrors[] = array(
                'code' => 'emptyApiToken',
                'message' => GetMessage('CBP_TRUSTME_ERROR_NO_TOKEN'),
            );
        }

        if (empty(trim($arProperties['DocumentUrl']))) {
            $arErrors[] = array(
                'code' => 'emptyDocumentUrl',
                'message' => GetMessage('CBP_TRUSTME_ERROR_NO_DOCUMENT'),
            );
        }

        if (empty(trim($arProperties['SignerFio']))) {
            $arErrors[] = array(
                'code' => 'emptySignerFio',
                'message' => GetMessage('CBP_TRUSTME_ERROR_NO_SIGNER'),
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

