<?php
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS",true);
define('BX_NO_ACCELERATOR_RESET', true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define("BX_CRONTAB_SUPPORT", true);
define("BX_CRONTAB", true);
define("CRONTAB_CODE_RUN", true);

$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/../../..");
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

require_once ($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

/* цикл, который сбросит все буферы */
while (ob_get_level()) {
    ob_end_flush();
}

Class AutoCleanerHelper {
    /** @var int Максимальная работа скрипта (в секундах) */
    public $workTimeSecond = 60;
    
    /** @var string Папка работы */
    public $path = '/local/cron/clean_bitrix_cache/';
    
    /** @var string Файл для хранения данных */
    public $pathFileSaved = 'base.dat';
    
    /** @var string Файл лога */
    public $pathFileLog = 'log.txt';
    
    /** @var bool
     *  Если true весь лог выводиться в консоль
     *  Если false весь лог будет записываться в файл (без вывода в консоль)
     */
    private $showLogInOutput = false;
    
    // Служебные свойства
    protected $arData = [];
    protected $fileSaved = '';
    protected $fileLog = '';
    protected $curTime = 0;
    protected $endTime = 0;
    protected $arLogLines = [];
    protected $arInfoWorker = [
        "files_count" => 0,
        "files_size" => 0,
        "deleted_count" => 0,
        "deleted_size" => 0,
    ];
    
    /**
     * Необходимые иницилизации
     * AutoCleanerHelper constructor.
     */
    public function __construct()
    {
        if (!class_exists("CFileCacheCleaner")) {
            require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/cache_files_cleaner.php");
        }
        
        $fullPathSaved = $this->makeFullPath($this->pathFileSaved);
        $this->fileSaved = $fullPathSaved;
        $rawData = file_get_contents($fullPathSaved);
        if (!empty($rawData)) {
            $this->arData = (array)unserialize($rawData);
        }
        $fullPathLog = $this->makeFullPath($this->pathFileLog);
        $this->fileLog = $fullPathLog;
        
        $this->curTime = mktime();
        $this->endTime = $this->curTime + $this->workTimeSecond;
        
        $this->addLog('Старт скрипта');
    }
    
    /**
     * Удаление устаревших кешов
     */
    public function cleanCaches ()
    {
        $start = microtime(true);
        
        // Работаем с устаревшим кешем
        $obCacheCleaner = new \CFileCacheCleaner("expired");
        if (!$obCacheCleaner->InitPath($this->getLastFile())) {
            $this->addLog('Не смогли иницилизировать кеш $obCacheCleaner->InitPath: ' . $this->getLastFile());
            $this->addLog('Попробуем запустить "$obCacheCleaner->InitPath" пустым файлом');
            $obCacheCleaner->InitPath("");
        }
        
        $obCacheCleaner->Start();
        $sWorkedFile = '';
        while ($file = $obCacheCleaner->GetNextFile()) {
            $sWorkedFile = $file;
            if (is_string($file)) {
                $file_size = filesize($file);
                $this->arInfoWorker['files_count']++;
                $this->arInfoWorker['files_size'] += $file_size;
                $date_expire = $obCacheCleaner->GetFileExpiration($file);
                if ($date_expire) {
                    if ($date_expire < $this->curTime) {
                        unlink($file);
                        $this->arInfoWorker['deleted_count']++;
                        $this->arInfoWorker['deleted_size'] += $file_size;
                    }
                }
                if (time() >= $this->endTime) break;
            }
        }
        $this->addLog('Окончания работы кеша, последный файл: ' . $sWorkedFile);
        
        /**
         * Общая информация работы
         */
        $this->addLog("--- Итог ---");
        $this->addLog('Время выполнения удаления: '.round(microtime(true) - $start, 4).' сек.');
        $this->addLog('Общая кол-во обработанных файлов кеша: ' . $this->arInfoWorker['files_count']);
        $this->addLog('Удаленные файлы кеша: ' . $this->arInfoWorker['deleted_count']);
        $this->addLog('Общый размер обработанных файлов кеша: ' . \CFile::FormatSize($this->arInfoWorker['files_size'], 1));
        $this->addLog('Размер удаленных файлов кеша: ' . \CFile::FormatSize($this->arInfoWorker['deleted_size'], 1));
        
        $this->saveLastFile($sWorkedFile);
    }
    
    /**
     * Получим последный обработанный файл
     * @return string
     */
    public function getLastFile ()
    {
        return (string)$this->getRelativePath($this->arData['last_file']);
    }
    
    /**
     * Сохраним последный обработанный файл
     * @param $file
     */
    public function saveLastFile ($file)
    {
        $this->arData['last_file'] = (string)$file;
        $this->saveData();
    }
    
    /**
     * Добавить строки в лог
     * @param string $string
     */
    public function addLog ($string = '')
    {
        if (!empty($string)) {
            $line = date('d.m.Y H:i:s') . ': ' . $string;
            if ($this->showLogInOutput) {
                echo $line . PHP_EOL;
            } else {
                $this->arLogLines[] = $line;
            }
        }
    }
    
    /**
     * Сохранения всех строк лога
     */
    public function saveLogs ()
    {
        if (!$this->showLogInOutput) {
            $sText = implode(PHP_EOL, $this->arLogLines);
            file_put_contents($this->fileLog, $sText);
        }
    }
    
    /**
     * Сохранение данных в файл
     */
    protected function saveData ()
    {
        file_put_contents($this->fileSaved, serialize($this->arData));
    }
    
    /**
     * Полный путь до файла относительно от $this->path
     * @param $fileName
     * @return mixed|string
     */
    protected function makeFullPath ($fileName)
    {
        $fullPath = $_SERVER['DOCUMENT_ROOT'];
        $fullPath .= $this->path;
        if (!is_dir($fullPath)) {
            @mkdir($fullPath, 0775, true);
        }
        $fullPath .= DIRECTORY_SEPARATOR . $fileName;
        return $fullPath;
    }
    
    /**
     * Получаем путь до файла относитально от корна сайта (DOCUMENT_ROOT)
     * @param string $filePath
     * @return string|string[]
     */
    protected function getRelativePath ($filePath = '')
    {
        if (!empty($filePath)) {
            $filePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $filePath);
        }
        return $filePath;
    }
}

/**
 * Запуск
 */
$obCacheWorker = new AutoCleanerHelper();
$obCacheWorker->cleanCaches();
$obCacheWorker->saveLogs();