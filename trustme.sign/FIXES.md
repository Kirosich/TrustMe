# Исправления структуры модуля

## Исправленные проблемы:

### 1. Файлы .description.php
- ✅ Исправлен синтаксис: используется `require_once(__DIR__ . '/...')` вместо проверки `file_exists`
- ✅ Используется `array()` вместо `[]` для совместимости
- ✅ В CATEGORY используется `GetMessage()` для ID и OWN_ID

### 2. Языковые файлы
- ✅ Добавлен `CBP_TRUSTME_CATEGORY_ID` в языковые файлы
- ✅ Все сообщения определены корректно

### 3. Структура установки
- ✅ Правильное копирование файлов активити
- ✅ Правильное копирование языковых файлов в структуру `/local/activities/{activity}/lang/{lang}/`
- ✅ Использование `ModuleManager::registerModule()` вместо `RegisterModule()`

### 4. include.php
- ✅ Правильная регистрация классов активити через autoload
- ✅ Проверка путей в `/local/activities/` с fallback на модуль

## Структура после установки:

```
/local/activities/
├── sendtotrustmeactivity/
│   ├── sendtotrustmeactivity.php
│   ├── properties_dialog.php
│   ├── .description.php
│   └── lang/
│       └── ru/
│           ├── sendtotrustmeactivity.php
│           ├── properties_dialog.php
│           └── .description.php
└── getcontractinfoactivity/
    ├── getcontractinfoactivity.php
    ├── properties_dialog.php
    ├── .description.php
    └── lang/
        └── ru/
            ├── getcontractinfoactivity.php
            ├── properties_dialog.php
            └── .description.php
```

## Важно после установки:

1. **Очистите кеш Битрикс24:**
   - Настройки → Настройки продукта → Производительность → Очистить кеш
   - Или через консоль: `php bitrix/modules/main/tools/cache.php`

2. **Проверьте права на папки:**
   - `/local/activities/` должна быть доступна для чтения
   - Все файлы должны иметь права 644

3. **Проверьте логи:**
   - Если активити не появляются, проверьте логи ошибок PHP
   - Проверьте, что модуль `bizproc` установлен

## Если активити все еще не появляются:

1. Проверьте, что файлы скопированы в `/local/activities/`
2. Проверьте, что файл `.description.php` существует в каждой папке активити
3. Проверьте, что языковые файлы скопированы в правильную структуру
4. Очистите кеш и перезагрузите страницу
5. Проверьте консоль браузера на наличие JavaScript ошибок

