<?php


namespace Kompot;

use Bitrix\Main\Loader,
    Bitrix\Sale\Location;

/**
 * Вспомогательные методы
 * Class CHelper
 * @package Kompot
 */
class CHelper
{
    /**
     * Склонение слов для чисел
     *
     * # Возраст
     * declension($number, ['год', 'года', 'лет']);
     * # Сумма
     * declension($number, ['рубль', 'рубля', 'рублей']);
     *
     * @param $number
     * @param array $data
     * @return mixed
     */
    public static function declension ($number, array $data)
    {
        $rest = [$number % 10, $number % 100];
        $suffix = '';

        if ($rest[1] > 10 && $rest[1] < 20) {
            $suffix = $data[2];
        } elseif ($rest[0] > 1 && $rest[0] < 5) {
            $suffix = $data[1];
        } else if ($rest[0] == 1) {
            $suffix = $data[0];
        } else {
            $suffix = $data[2];
        }

        return $number . ' ' . $suffix;
    }

    /**
     * Получение класса для работы с Highload
     * @param $HlBlockId
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getHLEntityDataClass($HlBlockId)
    {
        Loader::IncludeModule('highloadblock');
        if (empty($HlBlockId) || $HlBlockId < 1) {
            return false;
        }
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable ::getById($HlBlockId)->fetch();
        $entity = \Bitrix\Highloadblock\HighloadBlockTable ::compileEntity($hlblock);
        $entity_data_class = $entity->getDataClass();
        return $entity_data_class;
    }

    public static function getHLEntityDataClassByName($highloadName)
    {
        Loader::IncludeModule('highloadblock');
        if (empty($highloadName) && is_string($highloadName)) {
            return false;
        }
        $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
            'filter' => ['=NAME' => $highloadName]
        ])->fetch();
        if (!$hlblock) {
            return false;
        }
        $entityObject = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
        $hlClassName = $entityObject->getDataClass();

        return $hlClassName;
    }

    /**
     * Получение значений Enum список свойства пользователя
     * @param $uf_code
     * @return array
     */
    public static function getUserEnumFieldValues ($uf_code, $xml = false)
    {
        global $USER_FIELD_MANAGER;
        $arResult = [];
        $arFields = $USER_FIELD_MANAGER->GetUserFields("USER");
        $obEnum = new \CUserFieldEnum;
        $rsEnum = $obEnum->GetList([], ["USER_FIELD_ID" => $arFields[$uf_code]["ID"]]);
        while($arEnum = $rsEnum->GetNext()) {
            if ($xml) {
                $arResult[$arEnum['XML_ID']] = $arEnum;
            } else {
                $arResult[$arEnum['ID']] = $arEnum;
            }
        }
        return $arResult;
    }

    /**
     * Поиск города по названию
     * @param $cityName
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function searchCity ($cityName)
    {
        Loader::IncludeModule('sale');

        $cityName = strtolower($cityName);
        $arCity = false;

        $params = array(
            'limit'  => 1,
            'filter' => array(
                '=CODE' => 'CITY'
            )
        );

        $locType = Location\TypeTable::getList($params)->fetch();
        $type    = $locType['ID'];

        $params = array(
            'select' => array('*'),
            'order'  => array('NAME' => 'asc'),
            'limit'  => 1,
            'filter' => array(
                '=PHRASE'           => $cityName,
                '=SITE_ID'          => SITE_ID,
                '=NAME.LANGUAGE_ID' => LANGUAGE_ID,
                '=TYPE_ID'          => $type,
            )
        );

        $arLocation = Location\Search\Finder::find($params, array('USE_INDEX' => false, 'USE_ORM' => false))->fetch();

        $behaviour = array('INVERSE' => true, 'DELIMITER' => ', ', 'LANGUAGE_ID' => LANGUAGE_ID);
        $arCity = array(
            'ID'   => $arLocation['CODE'],
            'NAME' => Location\Admin\LocationHelper::getLocationStringByCode($arLocation['CODE'], $behaviour),
        );
        return $arCity;
    }

    /**
     * Получение название города по коду (ид)
     * @param $cityCode
     * @return string
     * @throws \Bitrix\Main\LoaderException
     */
    public static function getCityName ($cityCode)
    {
        $cityName = "";
        \Bitrix\Main\Loader::IncludeModule('sale');
        $behaviour = array('INVERSE' => true, 'DELIMITER' => ', ', 'LANGUAGE_ID' => LANGUAGE_ID);
        $cityName = Location\Admin\LocationHelper::getLocationStringByCode($cityCode, $behaviour);
        return $cityName;
    }

    /**
     * Очистка эмоджи
     * @param $string
     * @return string|string[]|null
     */
    public static function clearEmoji ($string)
    {
        // Match Enclosed Alphanumeric Supplement
        $regex_alphanumeric = '/[\x{1F100}-\x{1F1FF}]/u';
        $clear_string = preg_replace($regex_alphanumeric, '', $string);

        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $clear_string);

        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);

        // Match Supplemental Symbols and Pictographs
        $regex_supplemental = '/[\x{1F900}-\x{1F9FF}]/u';
        $clear_string = preg_replace($regex_supplemental, '', $clear_string);

        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);

        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);

        return $clear_string;
    }

    /**
     * Очистка Текстов
     * @param $html
     * @return string
     */
    public static function clearOtherHtmlTags ($html)
    {
        static $search1 = array(
            "'<!doctype([\s\S]+?)>'is",
            "'<script[^>]*?>.*?</script>'is",
            "'<div[^>]*?>'is",
            "'</div>'is",
            "'<p[^>]*?>'is",
            "'</p>'is",
            "'<a[^>]*?>'is",
            "'</a>'is",
            "'<img[^>]*?>'is",
            "'<style[^>]*?>.*?</style>'is",
            "'<select[^>]*?>.*?</select>'is",
            "'<link[^>]*?>.*?</link>'is",
            "'<table[^>]*?>.*?</table>'is",
            "'<embed[^>]*?>.*?</embed>'is",
            "'<object[^>]*?>.*?</object>'is",
            "'<form[^>]*?>.*?</form>'is",
            "'<iframe[^>]*?>.*?</iframe>'is",
            "'<head[^>]*?>.*?</head>'is",
            "'<font[^>]*?>.*?</font>'is",
            "'<title[^>]*?>.*?</title>'is",
            "'<!--.*?-->'is",
        );
        $str = preg_replace($search1, "\r", $html);

        $str = preg_replace('/<\/?(html|body|meta|header|span|section|p|h1)[^>]*>/is', "", $str);

        return trim($str);
    }

    /**
     * Замена стандартного транслита
     * @param $text
     * @return string
     */
    public static function translite ($text)
    {
        $cyr = ['Љ', 'Њ', 'Џ', 'џ', 'ш', 'ђ', 'ч', 'ћ', 'ж', 'љ', 'њ', 'Ш', 'Ђ', 'Ч', 'Ћ', 'Ж','Ц','ц', 'а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п', 'р','с','т','у','ф','х','ц','ч','ш','щ','ъ','ы','ь','э','ю','я', 'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П', 'Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ы','Ь','Э','Ю','Я'];
        $lat = ['Lj', 'Nj', 'Dž', 'dž', 'š', 'đ', 'č', 'ć', 'ž', 'lj', 'nj', 'Š', 'Đ', 'Č', 'Ć', 'Ž','C','c', 'a','b','v','g','d','e','io','zh','z','i','y','k','l','m','n','o','p', 'r','s','t','u','f','h','ts','ch','sh','sht','a','i','y','e','yu','ya', 'A','B','V','G','D','E','Io','Zh','Z','I','Y','K','L','M','N','O','P', 'R','S','T','U','F','H','Ts','Ch','Sh','Sht','A','I','Y','e','Yu','Ya'];
        $textcyr = str_replace($cyr, $lat, $text);
        return \CUtil::translit($textcyr, "RU", ["replace_space"=>"","replace_other"=>""]);
    }

    /**
     * Проверка урл
     * @param $url
     * @return bool
     */
    public static function validUrl ($url)
    {
        $find = preg_match('/[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,7}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*)/', $url);
        return boolval($find);
    }

    /**
     * Получение домена из урл
     * @param $url
     * @return bool|mixed
     */
    public static function getDomainNameFromUrl ($url)
    {
        if (preg_match('/^(?:https?:\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n?]+)/i', $url, $match)) {
            return $match[1];
        } else {
            return false;
        }
    }
}