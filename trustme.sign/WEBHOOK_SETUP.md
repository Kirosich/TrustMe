# Настройка Webhook для TrustMe

## Шаг 1: Установка модуля

1. Скопируйте папку `trustme.sign` в `/bitrix/modules/`
2. Установите модуль через админку Bitrix24
3. После установки webhook-файл будет скопирован в:
   - `/local/webhook/trustme_webhook.php` (приоритет)
   - `/webhook/trustme_webhook.php` (резервный)

## Шаг 2: Определение URL webhook

URL webhook будет:
```
https://ваш-домен.ru/webhook/trustme_webhook.php
```
или
```
https://ваш-домен.ru/local/webhook/trustme_webhook.php
```

**Важно:** URL должен быть доступен извне (не localhost)!

## Шаг 3: Настройка webhook в TrustMe

### Вариант А: Через API (программно)

Создайте скрипт для настройки webhook:

```php
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

CModule::IncludeModule('trustme.sign');

$apiToken = 'ВАШ_ТОКЕН_API';
$testMode = true; // или false для продакшена
$webhookUrl = 'https://ваш-домен.ru/webhook/trustme_webhook.php';

$api = new \TrustMe\Sign\Api($apiToken, $testMode);
$result = $api->setHook($webhookUrl);

if ($result) {
    echo "Webhook успешно настроен!\n";
    print_r($result);
} else {
    $error = $api->getLastError();
    echo "Ошибка: " . $error['message'] . "\n";
}
?>
```

### Вариант Б: Через админку TrustMe (если доступна)

1. Зайдите в настройки TrustMe
2. Найдите раздел "Webhooks" или "Интеграции"
3. Укажите URL: `https://ваш-домен.ru/webhook/trustme_webhook.php`
4. Сохраните настройки

## Шаг 4: Проверка webhook

### 4.1. Проверка доступности URL

Откройте в браузере:
```
https://ваш-домен.ru/webhook/trustme_webhook.php
```

Должна быть ошибка "Method not allowed" (это нормально, т.к. нужен POST).

### 4.2. Тестирование webhook

Создайте тестовый скрипт `test_webhook.php`:

```php
<?php
$webhookUrl = 'https://ваш-домен.ru/webhook/trustme_webhook.php';

$data = array(
    'contract_id' => 'bZE216Ds', // Тестовый ID договора
    'deal_id' => '123', // ID тестовой сделки
    'status' => 'created',
    'event' => 'contract_created'
);

$ch = curl_init($webhookUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n";
?>
```

### 4.3. Проверка логов

После тестирования проверьте файл:
```
/trustme_webhook.log
```

Там должны быть записи о полученных запросах.

## Шаг 5: Настройка бизнес-процесса

1. Создайте бизнес-процесс для сделок
2. Добавьте активити "Trust Me: Получить информацию о договоре"
3. Настройте параметры:
   - **ApiToken**: ваш токен API
   - **TestMode**: Да/Нет
   - **ContractId**: `{=Document:UF_CONTRACT_ID}` (или другое поле, где хранится ID договора)
   - **DealId**: `{=Document:ID}` (ID текущей сделки)
4. Сохраните шаблон БП

## Шаг 6: Связь сделки с договором

Webhook получает `contract_id` и `deal_id`. Если `deal_id` не передаётся в webhook, нужно:

1. **Вариант А**: Хранить `contract_id` в дополнительном поле сделки
   - Создайте пользовательское поле `UF_CONTRACT_ID` типа "Строка"
   - При создании договора в TrustMe сохраняйте `contract_id` в это поле
   - Webhook будет искать сделку по этому полю

2. **Вариант Б**: TrustMe передаёт `deal_id` в webhook
   - В этом случае webhook сразу найдёт сделку

## Структура webhook запроса от TrustMe

Ожидаемая структура JSON:

```json
{
    "contract_id": "bZE216Ds",
    "deal_id": "123",
    "status": "created",
    "event": "contract_created"
}
```

Или может быть другая структура - проверьте логи после первого webhook.

## Устранение проблем

### Webhook не приходит

1. Проверьте доступность URL извне
2. Проверьте настройки firewall
3. Проверьте логи TrustMe (если доступны)

### Бизнес-процесс не запускается

1. Проверьте файл `/trustme_webhook.log`
2. Убедитесь, что шаблон БП активен
3. Проверьте, что активити "GetContractInfoActivity" есть в шаблоне

### Ошибка "Deal not found"

1. Убедитесь, что `deal_id` передаётся в webhook
2. Или настройте поиск сделки по дополнительному полю с `contract_id`

