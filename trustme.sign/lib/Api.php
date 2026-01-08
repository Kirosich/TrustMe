<?php

namespace TrustMe\Sign;

/**
 * Класс для работы с API Trust Me
 * @see https://trustmekz.docs.apiary.io/
 */
class Api
{
    private $apiToken;
    private $testMode;
    private $baseUrl;
    private $lastError = null;

    /**
     * Конструктор
     * @param string $apiToken Токен API
     * @param bool $testMode Использовать тестовый сервер
     */
    public function __construct($apiToken, $testMode = true)
    {
        $this->apiToken = $apiToken;
        $this->testMode = $testMode;
        $this->baseUrl = $testMode 
            ? 'https://test.trustme.kz' 
            : 'https://trustme.kz';
    }

    /**
     * Отправка документа на подписание
     * @param string $documentUrl URL или путь к документу
     * @param array $requisites Массив данных подписантов
     * @param string $contractName Название контракта
     * @param array $options Дополнительные опции
     * @return array|false Массив с результатом или false в случае ошибки
     */
    public function sendToSign($documentUrl, $requisites, $contractName, $options = array())
    {
        // Реализация существующего метода
        // (если уже была реализация, её нужно сохранить)
        // Пока возвращаем заглушку для совместимости
        $this->lastError = array('message' => 'Method sendToSign not implemented yet');
        return false;
    }

    /**
     * Получение информации о договоре
     * @param string $contractId ID договора
     * @return array|false Массив с данными договора или false в случае ошибки
     */
    public function getContractInfo($contractId)
    {
        if (empty($contractId)) {
            $this->lastError = array('message' => 'Contract ID is required');
            return false;
        }

        if (empty($this->apiToken)) {
            $this->lastError = array('message' => 'API token is required');
            return false;
        }

        $url = $this->baseUrl . '/api/trust_contract_public_apis/GetContractInfo/' . \urlencode($contractId);

        $headers = array(
            'Authorization: ' . $this->apiToken,
            'Accept: application/json',
            'Accept-Language: kz',
            'Content-Language: ru',
            'User-Agent: Bitrix24-TrustMe-Integration/1.0',
        );

        $ch = \curl_init();
        \curl_setopt_array($ch, array(
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_SSL_VERIFYPEER => !$this->testMode, // Отключаем проверку SSL в тестовом режиме
            \CURLOPT_SSL_VERIFYHOST => $this->testMode ? 0 : 2, // Отключаем проверку хоста в тестовом режиме
            \CURLOPT_TIMEOUT => 30,
            \CURLOPT_CONNECTTIMEOUT => 10,
        ));

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $curlError = \curl_error($ch);
        \curl_close($ch);

        if ($response === false) {
            $this->lastError = array(
                'message' => 'CURL error: ' . $curlError,
                'code' => 'curl_error',
            );
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = array(
                'message' => 'HTTP error: ' . $httpCode,
                'code' => 'http_error',
                'http_code' => $httpCode,
            );
            return false;
        }

        $data = \json_decode($response, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            $this->lastError = array(
                'message' => 'JSON decode error: ' . \json_last_error_msg(),
                'code' => 'json_error',
            );
            return false;
        }

        if (!isset($data['status']) || $data['status'] !== 'Ok') {
            $errorText = isset($data['errorText']) ? $data['errorText'] : 'Unknown error';
            $this->lastError = array(
                'message' => $errorText,
                'code' => 'api_error',
                'status' => isset($data['status']) ? $data['status'] : 'Unknown',
            );
            return false;
        }

        if (!isset($data['data'])) {
            $this->lastError = array(
                'message' => 'Response data is missing',
                'code' => 'data_missing',
            );
            return false;
        }

        // Парсим contract_data если это JSON-строка
        if (isset($data['data']['contract_data']) && is_string($data['data']['contract_data'])) {
            $contractData = \json_decode($data['data']['contract_data'], true);
            if (\json_last_error() === \JSON_ERROR_NONE) {
                $data['data']['contract_data_parsed'] = $contractData;
            }
        }

        return $data['data'];
    }

    /**
     * Настройка webhook для получения уведомлений
     * @param string $webhookUrl URL для отправки webhook (например, https://your-domain.ru/webhook/trustme_webhook.php)
     * @return array|false Массив с результатом или false в случае ошибки
     */
    public function setHook($webhookUrl)
    {
        if (empty($webhookUrl)) {
            $this->lastError = array('message' => 'Webhook URL is required');
            return false;
        }

        if (empty($this->apiToken)) {
            $this->lastError = array('message' => 'API token is required');
            return false;
        }

        $url = $this->baseUrl . '/api/trust_contract_public_apis/SetHook';

        $headers = array(
            'Authorization: ' . $this->apiToken,
            'Accept: application/json',
            'Content-Type: application/json',
            'Accept-Language: kz',
            'Content-Language: ru',
            'User-Agent: Bitrix24-TrustMe-Integration/1.0',
        );

        $data = array(
            'url' => $webhookUrl,
        );

        $ch = \curl_init();
        \curl_setopt_array($ch, array(
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_POST => true,
            \CURLOPT_POSTFIELDS => json_encode($data),
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_SSL_VERIFYPEER => !$this->testMode,
            \CURLOPT_SSL_VERIFYHOST => $this->testMode ? 0 : 2,
            \CURLOPT_TIMEOUT => 30,
            \CURLOPT_CONNECTTIMEOUT => 10,
        ));

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $curlError = \curl_error($ch);
        \curl_close($ch);

        if ($response === false) {
            $this->lastError = array(
                'message' => 'CURL error: ' . $curlError,
                'code' => 'curl_error',
            );
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = array(
                'message' => 'HTTP error: ' . $httpCode,
                'code' => 'http_error',
                'http_code' => $httpCode,
            );
            return false;
        }

        $result = \json_decode($response, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            $this->lastError = array(
                'message' => 'JSON decode error: ' . \json_last_error_msg(),
                'code' => 'json_error',
            );
            return false;
        }

        if (!isset($result['status']) || $result['status'] !== 'Ok') {
            $errorText = isset($result['errorText']) ? $result['errorText'] : 'Unknown error';
            $this->lastError = array(
                'message' => $errorText,
                'code' => 'api_error',
                'status' => isset($result['status']) ? $result['status'] : 'Unknown',
            );
            return false;
        }

        return $result;
    }

    /**
     * Получение информации о настроенном webhook
     * @return array|false Массив с информацией о webhook или false в случае ошибки
     */
    public function getHookInfo()
    {
        if (empty($this->apiToken)) {
            $this->lastError = array('message' => 'API token is required');
            return false;
        }

        $url = $this->baseUrl . '/api/trust_contract_public_apis/HookInfo';

        $headers = array(
            'Authorization: ' . $this->apiToken,
            'Accept: application/json',
            'Accept-Language: kz',
            'Content-Language: ru',
            'User-Agent: Bitrix24-TrustMe-Integration/1.0',
        );

        $ch = \curl_init();
        \curl_setopt_array($ch, array(
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_SSL_VERIFYPEER => !$this->testMode,
            \CURLOPT_SSL_VERIFYHOST => $this->testMode ? 0 : 2,
            \CURLOPT_TIMEOUT => 30,
            \CURLOPT_CONNECTTIMEOUT => 10,
        ));

        $response = \curl_exec($ch);
        $httpCode = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        $curlError = \curl_error($ch);
        \curl_close($ch);

        if ($response === false) {
            $this->lastError = array(
                'message' => 'CURL error: ' . $curlError,
                'code' => 'curl_error',
            );
            return false;
        }

        if ($httpCode !== 200) {
            $this->lastError = array(
                'message' => 'HTTP error: ' . $httpCode,
                'code' => 'http_error',
                'http_code' => $httpCode,
            );
            return false;
        }

        $result = \json_decode($response, true);

        if (\json_last_error() !== \JSON_ERROR_NONE) {
            $this->lastError = array(
                'message' => 'JSON decode error: ' . \json_last_error_msg(),
                'code' => 'json_error',
            );
            return false;
        }

        return $result;
    }

    /**
     * Получение последней ошибки
     * @return array|null Массив с информацией об ошибке
     */
    public function getLastError()
    {
        return $this->lastError;
    }
}
