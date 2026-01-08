<?php
/**
 * Страница настройки webhook для TrustMe
 * Доступ: /bitrix/admin/trustme_sign_webhook_setup.php
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$MODULE_ID = 'trustme.sign';
CModule::IncludeModule($MODULE_ID);
CModule::IncludeModule('crm');

$APPLICATION->SetTitle(Loc::getMessage('TRUSTME_WEBHOOK_SETUP_TITLE'));

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php');

// Получаем настройки модуля
$options = new \TrustMe\Sign\Options();
$apiToken = $options->getApiToken();
$testMode = $options->getTestMode();

// Обработка формы
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    if (isset($_POST['set_webhook'])) {
        $webhookUrl = isset($_POST['webhook_url']) ? trim($_POST['webhook_url']) : '';
        $apiTokenInput = isset($_POST['api_token']) ? trim($_POST['api_token']) : $apiToken;
        $testModeInput = isset($_POST['test_mode']) ? 'Y' : 'N';
        
        if (empty($webhookUrl)) {
            $message = Loc::getMessage('TRUSTME_WEBHOOK_ERROR_EMPTY_URL');
            $messageType = 'error';
        } elseif (empty($apiTokenInput)) {
            $message = Loc::getMessage('TRUSTME_WEBHOOK_ERROR_EMPTY_TOKEN');
            $messageType = 'error';
        } else {
            $api = new \TrustMe\Sign\Api($apiTokenInput, $testModeInput === 'Y');
            $result = $api->setHook($webhookUrl);
            
            if ($result) {
                $message = Loc::getMessage('TRUSTME_WEBHOOK_SUCCESS');
                $messageType = 'success';
            } else {
                $error = $api->getLastError();
                $message = Loc::getMessage('TRUSTME_WEBHOOK_ERROR') . ': ' . (isset($error['message']) ? $error['message'] : 'Unknown error');
                $messageType = 'error';
            }
        }
    }
    
    if (isset($_POST['get_webhook_info'])) {
        $apiTokenInput = isset($_POST['api_token']) ? trim($_POST['api_token']) : $apiToken;
        $testModeInput = isset($_POST['test_mode']) ? 'Y' : 'N';
        
        if (empty($apiTokenInput)) {
            $message = Loc::getMessage('TRUSTME_WEBHOOK_ERROR_EMPTY_TOKEN');
            $messageType = 'error';
        } else {
            $api = new \TrustMe\Sign\Api($apiTokenInput, $testModeInput === 'Y');
            $result = $api->getHookInfo();
            
            if ($result) {
                $message = Loc::getMessage('TRUSTME_WEBHOOK_INFO_SUCCESS');
                $messageType = 'success';
                $webhookInfo = $result;
            } else {
                $error = $api->getLastError();
                $message = Loc::getMessage('TRUSTME_WEBHOOK_ERROR') . ': ' . (isset($error['message']) ? $error['message'] : 'Unknown error');
                $messageType = 'error';
            }
        }
    }
}

// Определяем URL webhook
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$webhookUrl = $protocol . '://' . $host . '/webhook/trustme_webhook.php';
$webhookUrlLocal = $protocol . '://' . $host . '/local/webhook/trustme_webhook.php';

?>

<?php if ($message): ?>
    <div class="adm-info-message-wrap">
        <div class="adm-info-message <?= $messageType === 'error' ? 'adm-info-message-red' : 'adm-info-message-green' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    
    <div class="adm-detail-content">
        <div class="adm-detail-content-wrap">
            
            <div class="adm-detail-content-item-block">
                <div class="adm-detail-content-item-block-title">
                    <?= Loc::getMessage('TRUSTME_WEBHOOK_SECTION_SETTINGS') ?>
                </div>
                
                <div class="adm-detail-content-item-block-content">
                    <table class="adm-detail-content-table">
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <?= Loc::getMessage('TRUSTME_WEBHOOK_API_TOKEN') ?>:
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="text" name="api_token" value="<?= htmlspecialchars($apiToken) ?>" size="80">
                            </td>
                        </tr>
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <?= Loc::getMessage('TRUSTME_WEBHOOK_TEST_MODE') ?>:
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="checkbox" name="test_mode" value="Y" <?= $testMode ? 'checked' : '' ?>>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="adm-detail-content-item-block">
                <div class="adm-detail-content-item-block-title">
                    <?= Loc::getMessage('TRUSTME_WEBHOOK_SECTION_WEBHOOK') ?>
                </div>
                
                <div class="adm-detail-content-item-block-content">
                    <table class="adm-detail-content-table">
                        <tr>
                            <td width="40%" class="adm-detail-content-cell-l">
                                <?= Loc::getMessage('TRUSTME_WEBHOOK_URL') ?>:
                            </td>
                            <td width="60%" class="adm-detail-content-cell-r">
                                <input type="text" name="webhook_url" value="<?= htmlspecialchars($webhookUrl) ?>" size="80">
                                <br>
                                <small>
                                    <?= Loc::getMessage('TRUSTME_WEBHOOK_URL_DESC') ?><br>
                                    Альтернативный: <code><?= htmlspecialchars($webhookUrlLocal) ?></code>
                                </small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <?php if (isset($webhookInfo)): ?>
            <div class="adm-detail-content-item-block">
                <div class="adm-detail-content-item-block-title">
                    <?= Loc::getMessage('TRUSTME_WEBHOOK_SECTION_INFO') ?>
                </div>
                
                <div class="adm-detail-content-item-block-content">
                    <pre><?= print_r($webhookInfo, true) ?></pre>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
    
    <div class="adm-detail-toolbar">
        <input type="submit" name="set_webhook" value="<?= Loc::getMessage('TRUSTME_WEBHOOK_BUTTON_SET') ?>" class="adm-btn-save">
        <input type="submit" name="get_webhook_info" value="<?= Loc::getMessage('TRUSTME_WEBHOOK_BUTTON_GET_INFO') ?>" class="adm-btn">
    </div>
</form>

<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php');
?>

