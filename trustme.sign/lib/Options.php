<?php

namespace TrustMe\Sign;

/**
 * Класс для работы с настройками модуля Trust Me Sign
 */
class Options
{
    const MODULE_ID = 'trustme.sign';
    const OPTION_API_TOKEN = 'api_token';
    const OPTION_TEST_MODE = 'test_mode';

    /**
     * Получить токен API
     * @return string
     */
    public function getApiToken()
    {
        return \COption::GetOptionString(self::MODULE_ID, self::OPTION_API_TOKEN, '');
    }

    /**
     * Установить токен API
     * @param string $token
     * @return void
     */
    public function setApiToken($token)
    {
        \COption::SetOptionString(self::MODULE_ID, self::OPTION_API_TOKEN, $token);
    }

    /**
     * Получить режим тестирования
     * @return bool
     */
    public function getTestMode()
    {
        $value = \COption::GetOptionString(self::MODULE_ID, self::OPTION_TEST_MODE, 'N');
        return $value === 'Y' || $value === '1' || $value === true;
    }

    /**
     * Установить режим тестирования
     * @param bool $testMode
     * @return void
     */
    public function setTestMode($testMode)
    {
        $value = ($testMode === true || $testMode === 'Y' || $testMode === '1') ? 'Y' : 'N';
        \COption::SetOptionString(self::MODULE_ID, self::OPTION_TEST_MODE, $value);
    }

    /**
     * Получить все настройки модуля
     * @return array
     */
    public function getAll()
    {
        return array(
            'api_token' => $this->getApiToken(),
            'test_mode' => $this->getTestMode(),
        );
    }
}
