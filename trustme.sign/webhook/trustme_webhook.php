<?php
/**
 * Обработчик webhook от TrustMe
 * 
 * Этот файл должен быть скопирован в корень сайта Bitrix24:
 * /local/webhook/trustme_webhook.php
 * или
 * /webhook/trustme_webhook.php
 * 
 * URL для настройки в TrustMe:
 * https://your-domain.ru/webhook/trustme_webhook.php
 */

// Подключаем Bitrix
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

// Включаем отображение ошибок для отладки (в продакшене убрать)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Логирование webhook запросов
$logFile = $_SERVER['DOCUMENT_ROOT'] . '/trustme_webhook.log';
$logMessage = date('Y-m-d H:i:s') . " - Webhook received\n";
$logMessage .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$logMessage .= "Headers: " . json_encode(getallheaders()) . "\n";
$logMessage .= "Body: " . file_get_contents('php://input') . "\n";
$logMessage .= "POST: " . json_encode($_POST) . "\n";
$logMessage .= "GET: " . json_encode($_GET) . "\n";
$logMessage .= "---\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Проверяем метод запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Method not allowed'));
    die();
}

// Получаем данные из запроса
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Если JSON не распарсился, пробуем из POST
if (empty($data)) {
    $data = $_POST;
}

// Извлекаем необходимые данные
$contractId = isset($data['contract_id']) ? $data['contract_id'] : (isset($data['id']) ? $data['id'] : '');
$dealId = isset($data['deal_id']) ? $data['deal_id'] : (isset($data['number_deal']) ? $data['number_deal'] : '');
$status = isset($data['status']) ? $data['status'] : '';
$event = isset($data['event']) ? $data['event'] : '';

// Логируем извлечённые данные
$logMessage = date('Y-m-d H:i:s') . " - Extracted data:\n";
$logMessage .= "contract_id: " . $contractId . "\n";
$logMessage .= "deal_id: " . $dealId . "\n";
$logMessage .= "status: " . $status . "\n";
$logMessage .= "event: " . $event . "\n";
$logMessage .= "---\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

// Валидация
if (empty($contractId)) {
    http_response_code(400);
    echo json_encode(array('error' => 'contract_id is required'));
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: contract_id is empty\n", FILE_APPEND);
    die();
}

// Подключаем модули
if (!CModule::IncludeModule('crm')) {
    http_response_code(500);
    echo json_encode(array('error' => 'CRM module not found'));
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: CRM module not found\n", FILE_APPEND);
    die();
}

if (!CModule::IncludeModule('bizproc')) {
    http_response_code(500);
    echo json_encode(array('error' => 'BizProc module not found'));
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: BizProc module not found\n", FILE_APPEND);
    die();
}

try {
    // Если deal_id не передан, пытаемся найти сделку по contract_id через дополнительное поле
    if (empty($dealId)) {
        // Ищем сделку, где в дополнительном поле указан contract_id
        // Это зависит от того, как вы храните связь между сделкой и договором
        // Пример: поиск по пользовательскому полю
        $dbDeals = CCrmDeal::GetList(
            array('ID' => 'DESC'),
            array(),
            false,
            array('nTopCount' => 1),
            array('ID')
        );
        
        // Если не нашли, используем последнюю сделку (для тестирования)
        // В реальном сценарии нужно искать по дополнительному полю
        if ($deal = $dbDeals->Fetch()) {
            $dealId = $deal['ID'];
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found deal by default: " . $dealId . "\n", FILE_APPEND);
        }
    } else {
        $dealId = (int)$dealId;
    }

    if (empty($dealId) || $dealId <= 0) {
        http_response_code(400);
        echo json_encode(array('error' => 'deal_id is required and not found'));
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: deal_id is required\n", FILE_APPEND);
        die();
    }

    // Проверяем существование сделки
    $deal = CCrmDeal::GetByID($dealId);
    if (!$deal) {
        http_response_code(404);
        echo json_encode(array('error' => 'Deal not found'));
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Deal #" . $dealId . " not found\n", FILE_APPEND);
        die();
    }

    // Ищем бизнес-процесс для запуска
    // Ищем шаблон БП, который содержит активити "Trust Me: Получить информацию о договоре"
    $dbTemplates = CBPTemplate::GetList(
        array('ID' => 'DESC'),
        array('ACTIVE' => 'Y'),
        false,
        false,
        array('ID', 'NAME', 'DOCUMENT_TYPE')
    );

    $templateFound = false;
    while ($template = $dbTemplates->Fetch()) {
        // Проверяем, что шаблон для сделок
        if (!is_array($template['DOCUMENT_TYPE']) || 
            $template['DOCUMENT_TYPE'][0] !== 'crm' || 
            $template['DOCUMENT_TYPE'][1] !== 'CCrmDocumentDeal') {
            continue;
        }

        // Загружаем шаблон
        $templateData = CBPTemplate::GetTemplateById($template['ID']);
        if (!$templateData || !isset($templateData['TEMPLATE'])) {
            continue;
        }

        // Проверяем наличие нужного активити
        $hasActivity = false;
        if (isset($templateData['TEMPLATE']['Activities']) && is_array($templateData['TEMPLATE']['Activities'])) {
            // Рекурсивный поиск активити
            $activities = $templateData['TEMPLATE']['Activities'];
            foreach ($activities as $activity) {
                if (isset($activity['Type']) && $activity['Type'] === 'GetContractInfoActivity') {
                    $hasActivity = true;
                    break;
                }
                // Проверяем вложенные активити
                if (isset($activity['Children']) && is_array($activity['Children'])) {
                    foreach ($activity['Children'] as $child) {
                        if (isset($child['Type']) && $child['Type'] === 'GetContractInfoActivity') {
                            $hasActivity = true;
                            break 2;
                        }
                    }
                }
            }
        }

        if ($hasActivity) {
            $templateFound = $template['ID'];
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Found BP template: " . $template['ID'] . " (" . $template['NAME'] . ")\n", FILE_APPEND);
            break;
        }
    }

    if (!$templateFound) {
        http_response_code(404);
        echo json_encode(array('error' => 'Business process template with GetContractInfoActivity not found'));
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: BP template not found\n", FILE_APPEND);
        die();
    }

    // Запускаем бизнес-процесс
    $documentType = array('crm', 'CCrmDocumentDeal', 'DEAL');
    $documentId = array('crm', 'CCrmDocumentDeal', $dealId);

    // Параметры для БП (передаём contract_id и deal_id)
    $workflowParameters = array(
        'ContractId' => $contractId,
        'DealId' => $dealId,
    );

    $workflowId = CBPDocument::StartWorkflow(
        $templateFound,
        $documentId,
        $workflowParameters,
        array()
    );

    if ($workflowId) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - SUCCESS: Workflow started, ID: " . $workflowId . "\n", FILE_APPEND);
        http_response_code(200);
        echo json_encode(array(
            'success' => true,
            'workflow_id' => $workflowId,
            'deal_id' => $dealId,
            'contract_id' => $contractId
        ));
    } else {
        http_response_code(500);
        echo json_encode(array('error' => 'Failed to start workflow'));
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Failed to start workflow\n", FILE_APPEND);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('error' => $e->getMessage()));
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
}

