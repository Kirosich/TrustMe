<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;

Loc::loadMessages(__FILE__);

class trustme_sign extends CModule
{
    public $MODULE_ID = "trustme.sign";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__ . "/version.php");

        if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        }

        $this->MODULE_NAME = Loc::getMessage('TRUSTME_SIGN_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('TRUSTME_SIGN_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('TRUSTME_SIGN_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('TRUSTME_SIGN_PARTNER_URI');
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (CheckVersion(ModuleManager::getVersion("main"), "14.00.00")) {
            $this->InstallFiles();
            $this->InstallDB();
            $this->InstallEvents();
            $this->InstallTasks();

            ModuleManager::registerModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage("TRUSTME_SIGN_INSTALL_TITLE"),
                __DIR__ . "/step.php"
            );
        } else {
            $APPLICATION->ThrowException(
                Loc::getMessage("TRUSTME_SIGN_INSTALL_ERROR_VERSION")
            );
        }
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        $this->UnInstallTasks();
        $this->UnInstallEvents();
        $this->UnInstallDB();
        $this->UnInstallFiles();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("TRUSTME_SIGN_UNINSTALL_TITLE"),
            __DIR__ . "/unstep.php"
        );
    }

    public function InstallDB()
    {
        return true;
    }

    public function UnInstallDB()
    {
        return true;
    }

    public function InstallEvents()
    {
        return true;
    }

    public function UnInstallEvents()
    {
        return true;
    }

    public function InstallTasks()
    {
        return true;
    }

    public function UnInstallTasks()
    {
        return true;
    }

    public function InstallFiles()
    {
        // Копируем Activity в /local/activities/
        $this->registerActivities();
        
        // Копируем webhook обработчик
        $this->installWebhook();
        
        // Копируем admin файлы
        CopyDirFiles(
            __DIR__ . '/../admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles()
    {
        // Удаляем Activity из /local/activities/
        $this->unregisterActivities();
        
        // Удаляем webhook обработчик
        $this->uninstallWebhook();
        
        // Удаляем admin файлы
        DeleteDirFilesEx('/bitrix/admin/trustme_sign_settings.php');
        DeleteDirFilesEx('/bitrix/admin/trustme_sign_webhook_setup.php');

        return true;
    }

    /**
     * Регистрация Activity в /local/activities/
     */
    protected function registerActivities()
    {
        if (!ModuleManager::isModuleInstalled('bizproc')) {
            return;
        }
        
        // Путь для пользовательских активити согласно документации Битрикс
        $customActivitiesDir = $_SERVER['DOCUMENT_ROOT'] . '/local/activities';
        
        // Определяем путь к модулю
        $moduleDir = dirname(__DIR__);
        $sourceActivitiesDir = $moduleDir . '/activities';
        $sourceLangDir = $moduleDir . '/lang';
        
        // Проверка существования исходных директорий
        if (!is_dir($sourceActivitiesDir)) {
            return;
        }
        
        // Создаем базовую директорию /local/activities если её нет
        if (!is_dir($customActivitiesDir)) {
            mkdir($customActivitiesDir, 0755, true);
        }
        
        // Список активити для установки
        $activities = array(
            'sendtotrustmeactivity',
            'getcontractinfoactivity',
        );
        
        // Копируем каждый активити в отдельную папку
        foreach ($activities as $activityName) {
            $sourceActivityDir = $sourceActivitiesDir . '/' . $activityName;
            $targetActivityDir = $customActivitiesDir . '/' . $activityName;
            
            // Копируем файлы активити (PHP файлы без lang)
            if (is_dir($sourceActivityDir)) {
                $this->copyActivitiesFiles($sourceActivityDir, $targetActivityDir);
            }
            
            // Копируем языковые файлы для активити
            if (is_dir($sourceLangDir)) {
                $this->copyActivityLanguageFiles($sourceLangDir, $targetActivityDir, $activityName);
            }
        }
    }

    /**
     * Копирование файлов активити (исключая папку lang)
     */
    private function copyActivitiesFiles($source, $destination)
    {
        if (!is_dir($source)) {
            return;
        }

        // Создаем целевую директорию если её нет
        if (!is_dir($destination)) {
            @mkdir($destination, 0755, true);
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                $subPath = $iterator->getSubPathName();
                
                // Пропускаем папку lang и её содержимое (языковые файлы копируются отдельно)
                if (strpos($subPath, 'lang' . DIRECTORY_SEPARATOR) === 0 || $subPath === 'lang') {
                    continue;
                }
                
                $targetPath = $destination . DIRECTORY_SEPARATOR . $subPath;
                
                if ($item->isDir()) {
                    if (!is_dir($targetPath)) {
                        @mkdir($targetPath, 0755, true);
                    }
                } else {
                    $targetDir = dirname($targetPath);
                    if (!is_dir($targetDir)) {
                        @mkdir($targetDir, 0755, true);
                    }
                    @copy($item->getPathname(), $targetPath);
                    @chmod($targetPath, 0644);
                }
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки копирования
        }
    }

    /**
     * Копирование языковых файлов активити
     */
    private function copyActivityLanguageFiles($sourceLangDir, $targetActivityDir, $activityName)
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/trustme_install.log';
        file_put_contents($logFile, "\n=== copyActivityLanguageFiles ===\n", FILE_APPEND);
        file_put_contents($logFile, "sourceLangDir: $sourceLangDir\n", FILE_APPEND);
        file_put_contents($logFile, "targetActivityDir: $targetActivityDir\n", FILE_APPEND);
        file_put_contents($logFile, "activityName: $activityName\n", FILE_APPEND);
        
        if (!is_dir($sourceLangDir)) {
            file_put_contents($logFile, "ERROR: sourceLangDir не существует\n", FILE_APPEND);
            return;
        }

        // Сканируем все языковые папки (ru, en, kz и т.д.)
        $languages = scandir($sourceLangDir);
        if ($languages === false) {
            file_put_contents($logFile, "ERROR: scandir($sourceLangDir) вернул false\n", FILE_APPEND);
            return;
        }
        
        $languages = array_diff($languages, array('.', '..'));
        file_put_contents($logFile, "Найдено языков: " . implode(', ', $languages) . "\n", FILE_APPEND);
        
        foreach ($languages as $lang) {
            $langPath = $sourceLangDir . DIRECTORY_SEPARATOR . $lang;
            
            // Проверяем что это действительно директория языка
            if (!is_dir($langPath)) {
                file_put_contents($logFile, "SKIP: $lang не директория\n", FILE_APPEND);
                continue;
            }
            
            // Путь к языковым файлам активити в модуле:
            // /bitrix/modules/trustme.sign/lang/ru/activities/getcontractinfoactivity/
            $sourceActivityLangDir = $langPath . DIRECTORY_SEPARATOR . 'activities' . DIRECTORY_SEPARATOR . $activityName;
            file_put_contents($logFile, "Проверка: $sourceActivityLangDir\n", FILE_APPEND);
            
            // Если для этого активити нет переводов на данный язык - пропускаем
            if (!is_dir($sourceActivityLangDir)) {
                file_put_contents($logFile, "SKIP: $sourceActivityLangDir не существует\n", FILE_APPEND);
                continue;
            }
            
            // Целевая директория для языковых файлов:
            // /local/activities/getcontractinfoactivity/lang/ru/
            $targetLangDir = $targetActivityDir . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang;
            file_put_contents($logFile, "Целевая папка: $targetLangDir\n", FILE_APPEND);
            
            // Создаем целевую директорию если её нет
            if (!is_dir($targetLangDir)) {
                $mkdirResult = mkdir($targetLangDir, 0755, true);
                file_put_contents($logFile, "mkdir($targetLangDir): " . ($mkdirResult ? 'OK' : 'FAIL') . "\n", FILE_APPEND);
            }
            
            // Копируем все файлы переводов
            $langFiles = scandir($sourceActivityLangDir);
            if ($langFiles === false) {
                file_put_contents($logFile, "ERROR: scandir($sourceActivityLangDir) вернул false\n", FILE_APPEND);
                continue;
            }
            
            $langFiles = array_diff($langFiles, array('.', '..'));
            file_put_contents($logFile, "Файлы для копирования: " . implode(', ', $langFiles) . "\n", FILE_APPEND);
            
            foreach ($langFiles as $file) {
                $sourceFile = $sourceActivityLangDir . DIRECTORY_SEPARATOR . $file;
                $targetFile = $targetLangDir . DIRECTORY_SEPARATOR . $file;
                
                // Копируем только файлы (не директории)
                if (is_file($sourceFile)) {
                    $copyResult = copy($sourceFile, $targetFile);
                    file_put_contents($logFile, "copy($file): " . ($copyResult ? 'OK' : 'FAIL') . "\n", FILE_APPEND);
                    if ($copyResult) {
                        chmod($targetFile, 0644);
                    }
                }
            }
        }
    }

    /**
     * Удаление Activity из /local/activities/
     */
    protected function unregisterActivities()
    {
        if (!ModuleManager::isModuleInstalled('bizproc')) {
            return;
        }
        
        $customActivitiesDir = $_SERVER['DOCUMENT_ROOT'] . '/local/activities';
        
        // Список активити для удаления
        $activities = array(
            'sendtotrustmeactivity',
            'getcontractinfoactivity',
        );
        
        // Удаляем каждый активити
        foreach ($activities as $activityName) {
            $targetActivityDir = $customActivitiesDir . '/' . $activityName;
            if (is_dir($targetActivityDir)) {
                $this->deleteDirectory($targetActivityDir);
            }
        }
    }

    /**
     * Рекурсивное удаление директории
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }
        
        return rmdir($dir);
    }

    /**
     * Установка webhook обработчика
     */
    protected function installWebhook()
    {
        $moduleDir = dirname(__DIR__);
        $sourceWebhookFile = $moduleDir . '/webhook/trustme_webhook.php';
        
        // Копируем в /local/webhook/ (приоритет) или /webhook/
        $targetWebhookDir1 = $_SERVER['DOCUMENT_ROOT'] . '/local/webhook';
        $targetWebhookDir2 = $_SERVER['DOCUMENT_ROOT'] . '/webhook';
        
        if (!file_exists($sourceWebhookFile)) {
            return;
        }

        // Пробуем скопировать в /local/webhook/ сначала
        if (!is_dir($targetWebhookDir1)) {
            @mkdir($targetWebhookDir1, 0755, true);
        }
        
        $targetFile1 = $targetWebhookDir1 . '/trustme_webhook.php';
        if (is_dir($targetWebhookDir1)) {
            @copy($sourceWebhookFile, $targetFile1);
            @chmod($targetFile1, 0644);
        }
        
        // Также копируем в /webhook/ для совместимости
        if (!is_dir($targetWebhookDir2)) {
            @mkdir($targetWebhookDir2, 0755, true);
        }
        
        $targetFile2 = $targetWebhookDir2 . '/trustme_webhook.php';
        if (is_dir($targetWebhookDir2)) {
            @copy($sourceWebhookFile, $targetFile2);
            @chmod($targetFile2, 0644);
        }
    }

    /**
     * Удаление webhook обработчика
     */
    protected function uninstallWebhook()
    {
        $webhookFiles = array(
            $_SERVER['DOCUMENT_ROOT'] . '/local/webhook/trustme_webhook.php',
            $_SERVER['DOCUMENT_ROOT'] . '/webhook/trustme_webhook.php',
        );
        
        foreach ($webhookFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
