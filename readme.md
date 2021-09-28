# Регламент и рекомендации по разработке
## Корпоративные сайты:
Навигация:
 + [Настройка git](#git_settings)
 + [Структура сайта](#site_structure)
 + [Реализация init.php](#init_release)
 + [Подключение своих классов php](#own_classes)
 + [Реализация constants.php](#constantsphp)
 + [Универсальный шаблон сайта](#universal_site_template)
 + [Полезные функции методы для интеграции](#useful_functions)
 + [Рекомендации по интеграции верстки](#recomendation_integrate_design)
 + [Вывод динамических разделов из инфоблока (Новости, товары, услуги и т.д)](#integrate_dynamic_data)
 + [Форма обратной связи](#feedback_form)
 + [Работа с событиями](#events)
 + [Работа с кешом](#cache)
 + [Работа с REST API](#restapi)
 + [Работа с крон](#cron)
### <a name="git_settings">	</a> Настройка git
1. Создать приватную репозиторию в github.com (Обычно Тимлид или курирующий программист уже подготовить)
2. Получить ssh доступы к тестовой площадки для проекта 
3. Скачать и установить битрикс БУС в площадку с помощи 
<a target="_blank" href="https://www.1c-bitrix.ru/download/scripts/bitrixsetup.php">bitrixsetup.php</a>
4. При установке выбрать шаблон для разработчиков http://joxi.ru/nAy593bcaRgVaA
5. Инициализировать репозиторию 
`git init` <br>
6. Подключить репозиторий github к локальной репозиторий <br>
`git remote add origin https://github.com/*имя пользователя*/*имя репозитория*.git` <br>
7. Создадим **.gitignore** файл
```gitignore
# Исключаем настройки PHPStorm, мы в нём работаем
/.idea/
# Ядро
/bitrix/*

# оставляем печатные формы интернет-магазина
#!/bitrix/admin/
#/bitrix/admin/*
#!/bitrix/admin/reports/

# оставляем нестандартные компоненты чуших модулей (Свои компоненты переместить в папку local/components)
#!/bitrix/components/
#/bitrix/components/bitrix/

# сохраняем весь php_interface за редкими исключениями
#!/bitrix/php_interface/
#/bitrix/php_interface/dbconn.ph*
#/bitrix/php_interface/after_connect*
#/bitrix/php_interface/logs/

# сохраняем шаблоны сайта
!/bitrix/templates/

# исключаем логи
/local/php_interface/logs/
/local/logs/

# исключаем загружаемые файлы
/upload/

# различные системные папки и файлы хостингов и операционных систем
/cgi-bin/
/awstats/
/webstat/
.DS_Store
.Spotlight-V100
.Trashes
Thumbs.db
ehthumbs.db

# исключаем все текстовые и подобные ресурсы (на нашей практике они всегда излишни)
*.xml
*.html
*.txt
*.log
*.css.map

# архивы, включая многотомные
*.zip
*.zip*
*.tar
*.tar*
*.enc
*.enc*
*.gz
*.gz*
*.tgz
*.tgz*
*.sql
*.rar
.hg
.ftpconfig
*.doc
*.docx
*.pdf
*.rtf
*.xls
*.xlsx
*.ppt
*.pptx
*.psd
*.psb
*.sketch
core.*

# однако храним robots.txt — он нужен
!/robots.txt

# composer
composer.phar
/vendor/

# исключаем всякое от node.js
node_modules
bower_components
.grunt
.npm
.env

# и логи xhprof напоследок
/xhprof/logs
```
8. Сделаем первый коммит <br>
`git add .` <br>
`git commit -m "initial commit"`<br>
9. Создадим рабочую ветку **dev** и дальше все работу ведем на ней<br>
`git branch -M dev`<br>
10. Запушем ветку **dev** на github<br>
`git push -u origin dev`
11. Дальше будем работать над проектом, изменения отправим на github <br>
`git add .`<br>
`git commit -m "Интегрировал шаблон сайта"`<br>
`git push origin dev`
12. Получения изменений из github<br>
`git pull origin dev`

### <a name="site_structure">	</a> Структура сайта
**Работаем всегда на папке local**<br>
Свои компоненты `/local/components/kompot/`<br>
Свои модули `/local/modules/`<br>
Шаблон сайта `/local/templates/main/`<br>
Свои разработки php_interface `/local/php_interface/kompot/`<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &bull; Константы `/local/php_interface/kompot/constants.php`<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &bull; События `/local/php_interface/kompot/events.php`<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &bull; Функции `/local/php_interface/kompot/functions.php`<br>

### <a name="init_release">	</a> Реализация init.php
```php
// подключение файла с константами
require_once("kompot/constants.php");

// подключение общих функций и методов
require_once("kompot/functions.php");
 
// подключение обработчиков событий
require_once("kompot/events.php");
```

### <a name="own_classes">	</a> Подключение своих классов php
Свои классы хранить в `/local/php_interface/kompot/classes/`
```php
// Подключение через автозагрузчик php
spl_autoload_register(function ($class_name) {
    include_once($_SERVER["DOCUMENT_ROOT"] . "/local/php_interface/classes/" . $class_name . ".php");
});
```
```php
// Подключение через bitrix автозагрузчик
Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Kompot\SomeClass' => '/local/php_interface/classes/kompot/SomeClass.php'
]);
```

```json
# Подключение через composer.json psr-4
{
    "autoload": {
        "psr-4": {"Kompot\\": "/local/php_interface/classes/"}
    }
}
```
```php
// Также не забыть подключить autoload.php в init.php если использовать composer
require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
```

### <a name="constantsphp">	</a> Реализация constants.php
```php
// Свойства раздела
define("IS_INDEX", ($APPLICATION->GetCurPage()==SITE_DIR)?(true):(false));
define("SHOW_LEFT_COL", ($APPLICATION->GetDirProperty("showLeft")=="Y")?(true):(false));
define("SHOW_RIGHT_COL", ($APPLICATION->GetDirProperty("showRight")=="Y")?(true):(false));
define("SHOW_TITLE", ($APPLICATION->GetDirProperty("showTitle")=="Y")?(true):(false));

// Заглушки
define("NO_IMAGE", “/assets/img/no_image.png”);
define("NO_IMAGE_SMALL", “/assets/img/no_image_small.png”);
define("NO_IMAGE_LARGE", “/assets/img/no_image_large.png”);

// Используемые динамичные инфоблоки на проекте
define("CATALOG_IBLOCK_ID", 1);
```

### <a name="universal_site_template">	</a> Универсальный шаблон сайта
```php
<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>
<?
// Новое ядро d7 для работы с стилями и скриптами
use Bitrix\Main\Page\Asset;
// D7 работа с локализацией
use Bitrix\Main\Localization\Loc;

// Подключаем языковой файл для шаблона
Loc::loadMessages(__FILE__);
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET;?>"/>
    <title><?$APPLICATION->SHowTitle();?></title>
    <!-- Подключаем Стили -->
    <?Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/styles/fix.css");?>
    <!-- Подключаем доп. информацию в head -->
    <?Asset::getInstance()->addString("<link href='http://fonts.googleapis.com/css?family=PT+Sans:400&subset=cyrillic' rel='stylesheet' type='text/css'>");?>
    <!-- Вывод head -->
    <?$APPLICATION->ShowHead();?>
</head>
<body>
<!-- Админ панель -->
<?$APPLICATION->ShowPanel();?>

<!-- IS_INDEX только для главной страницы -->
<?if (IS_INDEX):?><?endif?>
<!-- Показать отдельные блоки в верстке -->
<?if (SHOW_LEFT_COL):?><?endif?>
<?if (SHOW_RIGHT_COL):?><?endif?>
<?if (SHOW_TITLE):?><?endif?>

<!-- Меню -->
<?$APPLICATION->IncludeComponent("bitrix:menu", "", array(
    "ROOT_MENU_TYPE" => "top",
    "MENU_CACHE_TYPE" => "N",
    "MENU_CACHE_TIME" => "3600",
    "MENU_CACHE_USE_GROUPS" => "Y",
    "MENU_CACHE_GET_VARS" => "",
    "MAX_LEVEL" => "1",
    "CHILD_MENU_TYPE" => "",
    "USE_EXT" => "Y"
),
    false
);?>

<!-- Авторизация -->
<?$APPLICATION->IncludeComponent("bitrix:system.auth.form", "", Array(
    "REGISTER_URL" => "/",  // Registration page
    "PROFILE_URL" => "/profile/",   // Profile page
    "SHOW_ERRORS" => "N",   // Show errors
),
    false
);?>

<!-- Упрощенная включаемая область -->
<?$APPLICATION->IncludeFile(
    $APPLICATION->GetTemplatePath("include_areas/inc.php"),
    Array(),
    Array("MODE" => "html")
);?>

<!-- Включаемая область -->
<?$APPLICATION->IncludeComponent("bitrix:main.include", ".default", array(
    "AREA_FILE_SHOW" => "sect",
    "AREA_FILE_SUFFIX" => "edit",
    "AREA_FILE_RECURSIVE" => "Y",
    "EDIT_TEMPLATE" => "edit_sect.php"
),
    false,
    array(
        "ACTIVE_COMPONENT" => "Y"
    )
);?>
<!-- Подключаем js -->
<?Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/custom.js");?>
</body>
</html>
```
### <a name="useful_functions">	</a> Полезные функции методы для интеграции
```php
// Получает значение свойства раздела
$APPLICATION->GetDirProperty("");

// Отсекает от строки все символы свыше указанной длины 
TruncateText($val, 150);

// Проверка нахождения в разделе/файле 
CSite::InDir('/about/index.php')

// Отложенные блоки для ставки
$APPLICATION->ShowViewContent('head_block');

$this->SetViewTarget('head_block');
   // Отложенный блок
$this->EndViewTarget(); 
```
### <a name="recomendation_integrate_design">	</a> Рекомендации по интеграции верстки
1. В хедере должны быть хлебные крошки, заголов страницы и боковые блоки.
2. В рабочей области должно быть динамический контент (bitrix:news, bitrix:catalog и т.д)
3. Если по каким то причинам (из-за сложностей на верстке) нужно формировать хлебные крошки и заголовок в рабочей области
для определенных разделов то нужно через свойства раздела скрыть их в хедере и выводить в рабочей области.
```
// Константа для скрытия хлебных крошек для определенных разделов
define("HIDE_BREADCRUMB", ($APPLICATION->GetDirProperty("HIDE_BREADCRUMB")=="Y")?(true):(false));

// В хедере
if (!HIDE_BREADCRUMB) {
    ?>
    <?$APPLICATION->IncludeComponent("bitrix:breadcrumb","",Array(
        "START_FROM" => "0",
        "PATH" => "",
        "SITE_ID" => "s1"
        )
    );?>
    <?
}
```
4. Использовать включаемые области для статики как лого сайта, телефон, емаил, адрес в шапке или в футере, кнопки соц. сетей 

### <a name="integrate_dynamic_data">	</a> Вывод динамических разделов из инфоблока (Новости, товары, услуги и т.д)
Использовать компонент bitrix:news: <br>
По чпу можно выводить <b>Список элементов -> деталька элемента</b><br>
или <b>Список разделов -> Список элементов -> деталька элемента</b>
##### Пример:
/news/index.php -> bitrix:news<br>
Шаблон списка -> news.php -> news.list<br>
Шаблон детальки -> detail.php -> news.detail

##### Пример с разделом
/news/index.php -> bitrix:news<br>
Шаблон разделов -> news.php -> catalog.section.list<br>
Шаблон списка -> section.php -> news.list<br>
Шаблон детальки -> detail.php -> news.detail

### <a name="feedback_form">	</a> Форма обратной связи
Стандартно нужно использовать компонент bitrix:main.feedback, если нужно доп. поля то нужно скопировать данный компонент
и доработать component.php (Добавить поля)<br>
Если редакция битрикса позволяет (стандарт и выше) можно использовать веб формы.

### <a name="events">	</a> Работа с событиями
Разные примеры с событиями:
1. Свои поля для поиска. <br>
При переиндексации можно добавить к элементу любой параметр. В этом примере я добавляю свойство «в архиве».
```php
// В /local/php_interface/kompot/events.php
use Bitrix\Main\EventManager;
EventManager::getInstance()->addEventHandler(
    "search",
    "BeforeIndex",
    "BeforeIndexHandler"
);

// В /local/php_interface/kompot/functions.php
function BeforeIndexHandler($arFields) {
    if ($arFields["MODULE_ID"] == "iblock" && substr($arFields["ITEM_ID"], 0, 1) != "S") {
        $arFields["PARAMS"]["archived"] = "N";
        $db_props = CIBlockElement::GetProperty(NEWS_IBLOCK_ID, $arFields["ITEM_ID"], array("sort" => "asc"), Array("CODE" => "ARCHIVED"));
        if ($ar_props = $db_props->Fetch()) {
            if ($ar_props["VALUE"] != ''){
                $arFields["PARAMS"]["archived"] = "Y";
            }
        }
    }
    return $arFields;
}
```
После этого мы можем использовать его в фильтре компонента bitrix:search.page.
```php
global $addSearchFilter;
$addSearchFilter = ["PARAMS" => ["archived" => "N"]];
 
$arElements = $APPLICATION->IncludeComponent(
	"bitrix:search.page",
	".default",
	Array(
		"RESTART" => $arParams["RESTART"],
		"NO_WORD_LOGIC" => $arParams["NO_WORD_LOGIC"],
		"USE_LANGUAGE_GUESS" => $arParams["USE_LANGUAGE_GUESS"],
		"CHECK_DATES" => $arParams["CHECK_DATES"],
		"arrFILTER" => array("iblock_".$arParams["IBLOCK_TYPE"]),
		"arrFILTER_iblock_".$arParams["IBLOCK_TYPE"] => array($arParams["IBLOCK_ID"]),
		"USE_TITLE_RANK" => "N",
		"DEFAULT_SORT" => "rank",
		"FILTER_NAME" => "addSearchFilter",
		"SHOW_WHERE" => "N",
		"arrWHERE" => array(),
		"SHOW_WHEN" => "N",
		"PAGE_RESULT_COUNT" => 50,
		"DISPLAY_TOP_PAGER" => "N",
		"DISPLAY_BOTTOM_PAGER" => "N",
		"PAGER_TITLE" => "",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => "N",
	),
	$arResult["THEME_COMPONENT"]
);
```
2. Доп. проверки при удаление элементов инфоблока
```php
// В /local/php_interface/kompot/events.php
use Bitrix\Main\EventManager;
EventManager::getInstance()->addEventHandler(
    "iblock",
    "OnBeforeIBlockElementDelete",
    "OnBeforeIBlockElementDeleteHandler"
);

// В /local/php_interface/kompot/functions.php
function OnBeforeIBlockElementDeleteHandler ($ID)
{
    if ($ID == 1)
    {
        global $APPLICATION;
        $APPLICATION->throwException("элемент с ID=1 нельзя удалить.");
        return false;
    }
}
```
### <a name="cache">	</a> Работа с кешом
Нужно постараться использовать компоненты с кешом "Авто + Управляемое" либо "Кешировать на время".<br>
Если нет возможно использовать компонент то все ресурсоемкие обработки и выборки обвернуть в кеш:<br>
```php
$obCache = new CPHPCache();
$cacheLifetime = 86400 * 7; // Время кеша в секундах 
$cacheID = 'AllItemsIDs'; // Уникальный id кеша 
$cachePath = '/'.$cacheID; // Папка для хранения кеша (относительно /bitrix/cache/ если типа кеша файлы)
if ($obCache->InitCache($cacheLifetime, $cacheID, $cachePath)) {
   $vars = $obCache->GetVars();
   extract($vars);
   // или же 
   $arAllItemsIDs = $vars['arAllItemsIDs'];
} elseif ($obCache->StartDataCache()) {
   $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => PRODUCT_IBLOCK_ID], false, false, ['ID']);
   while ($ar = $rs->Fetch()) {
      $arAllItemsIDs[] = $ar['ID'];
   }
   $obCache->EndDataCache(['arAllItemsIDs' => $arAllItemsIDs]);
}
print_r(count($arAllItemsIDs));

// Удаление кеша
CPHPCache::Clean("AllItemsIDs", false, "cache/AllItemsIDs");
```
Чтобы кеш спросивался автоматически при изменении в бд использовать тегированный кеш:
```php
$obCache = new CPHPCache();
$cacheLifetime = 86400 * 7; // Время кеша в секундах 
$cacheID = 'AllItemsIDs'; // Уникальный id кеша 
$cachePath = '/'.$cacheID; // Папка для хранения кеша (относительно /bitrix/cache/ если типа кеша файлы)
if ($obCache->InitCache($cacheLifetime, $cacheID, $cachePath)) {
   $vars = $obCache->GetVars();
   extract($vars);
   // или же 
   $arAllItemsIDs = $vars['arAllItemsIDs'];
} elseif ($obCache->StartDataCache()) {
   global $CACHE_MANAGER;
   $CACHE_MANAGER->StartTagCache($cachePath);
   $CACHE_MANAGER->RegisterTag("iblock_id_" . PRODUCT_IBLOCK_ID);
   $rs = CIBlockElement::GetList([], ['IBLOCK_ID' => PRODUCT_IBLOCK_ID], false, false, ['ID']);
   while ($ar = $rs->Fetch()) {
      $arAllItemsIDs[] = $ar['ID'];
   }
   $CACHE_MANAGER->RegisterTag("iblock_id_new");
   $CACHE_MANAGER->EndTagCache();
   $obCache->EndDataCache(['arAllItemsIDs' => $arAllItemsIDs]);
}
print_r(count($arAllItemsIDs));

// Удаление кеша
global $CACHE_MANAGER;
$CACHE_MANAGER->ClearByTag("iblock_id_" . PRODUCT_IBLOCK_ID);
```
Более подробно о тегированном кеше можно прочитать здесь: https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2978

### <a name="restapi">	</a> Работа с REST API
Обычно для получения данных по рест апи используется cUrl php:
```php
$url = 'https://my.com/rest';
$params = [
    "param1" => "value1",
    "param2" => "value2",
];
$data = http_build_query($params)

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array(
  'APIKEY: 111111111111111111111'
));
curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
$result = curl_exec($curl);
$response = json_decode($result, true);
print_r($response);
```
Иногда для удобства используется этот плагин для разных запросов https://docs.guzzlephp.org/en/stable/

### <a name="cron">	</a> Работа с крон
Сложные вычисления которые могут тормозит сайт нужно перенести в крон скрипт. Чтобы по расписании на фоне делать обработки данных:
```php
$_SERVER['DOCUMENT_ROOT'] = '/home/bitrix/www';

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS',true);
define('BX_CRONTAB', true);
define('BX_NO_ACCELERATOR_RESET', true);
define('SITE_ID', 's1');
define('LANG', 'ru');

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (Bitrix\Main\Loader::IncludeModule('iblock')) {
    /* какие то вычисления */
}
```
Установка скрипта на крон https://mblogm.ru/blog/cron-for-php-scripts/