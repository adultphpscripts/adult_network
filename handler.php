<?php
error_reporting(-1);
session_start();
/* EDIT MYSQL SERVER SETTINGS */
define(
    'MYSQL',
        ['host' => '172.17.0.2',
        'username' => 'adult_network',
        'password' => 'Adult!123',
        'database' => 'adult_network']);

class library {

    public $url = '';
    public $db = '';
    public $json = array();
    const _CHR_ = '$';
    const CACHE_ON = false;
    const LIBRARY_FILE = __DIR__ . '/library.dat';

    function tidy($buffer) {
        return $buffer;   
    }
    
    function exception_handler($exception) {
        echo 'Error: ' . $exception->getMessage() . ' in '
            . $exception->getFile() . ' on line '
            . $exception->getLine() . ' Code: '
            . $exception->getCode() . '<hr>';

        $file = new SplFileObject($exception->getFile());
        if (!$file->eof()) {
             $file->seek($exception->getLine());
             $contents = $file->current();
             echo $contents;
        }
        echo '<hr>';
         foreach($exception->getTrace() as $k => $v) {
           if (is_array($v)) {
              foreach ($v as $k2 => $v2) {
                 if (!is_array($v2)) { echo  $k2 . ' <b>' . $v2 . '</b>, '; }
               }
           }else{
             echo $v . '<br>';
           }
           echo '<hr>';
         }
    }

    function __autoload() {
        spl_autoload_register(function ($class_name) {
            $data ='class ' . $class_name . ' extends library { ' . PHP_EOL;
            foreach ($this->json[$class_name] as $method => $code) {
                $data.='function ' . $method . ' (' . self::_CHR_ . 'vars = null) {' . PHP_EOL;
                $data.=$code . PHP_EOL;
                $data.='}' . PHP_EOL;
            }
            $data.='}' . PHP_EOL;
            eval($data);
        });

    }

    function __construct() {
        set_exception_handler(array($this, "exception_handler"));
        $this->db = new mysqli(MYSQL['host'], MYSQL['username'], MYSQL['password'], MYSQL['database']);
        if (self::CACHE_ON == true) { 
            ob_start(array($this, "tidy"));
        }
        $this->url = 'https://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $this->json = json_decode(file_get_contents(self::LIBRARY_FILE), TRUE);
        $this->__autoload();
    }

    function __destruct() {
        if (self::CACHE_ON == true) { 
            $cache = ob_get_contents();
            ob_end_flush();
            file_put_contents(__DIR__ . '/cache/' . preg_replace("/[^A-Za-z0-9]/", '', $this->url), $cache);
        }
    }        
}

$library = new library();
$html = new html();
$page = new page();
if (!isset($_GET["path"])) {
    $page->main();
}else{
    $n = $c = $method = $variables = '';
    list($n, $c, $method, $variables) = explode('/', $_GET['path']);
    $class = new $c();
    if ($v !=='') {
        $class->$method($variables);
    }else{
        $class->$method();
    }
}

