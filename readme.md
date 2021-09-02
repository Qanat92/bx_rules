# Регламент и рекомендации по разработке
## Корпоративные сайты:
#### Настройка git
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

#### Структура сайта
**Работаем всегда на папке local**<br>
Свои компоненты `/local/components/kompot/`<br>
Свои модули `/local/modules/`<br>
Шаблон сайта `/local/templates/main/`<br>
Свои разработки php_interface `/local/php_interface/kompot/`<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &bull; Константы `/loca/php_interface/kompot/constants.php`<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &bull; Функции `/loca/php_interface/kompot/events.php`<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &bull; События `/loca/php_interface/kompot/functions.php`<br>

#### Реализация init.php
```php
// подключение файла с константами
require_once("kompot/constants.php");

// подключение общих функций и методов
require_once("kompot/functions.php");
 
// подключение обработчиков событий
require_once("kompot/events.php");
```

#### Реализация constants.php
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

#### Универсальный шаблон сайта
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



