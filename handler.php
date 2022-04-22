<?php
error_reporting(-1);
session_start();


class library {

    public $db = '';
    public $json = array();
    const _CHR_ = '$';
    function exception_handler($exception) {
        echo "Uncaught exception: " , $exception->getMessage(), "\n";
        var_dump($exception);

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
        $this->json = json_decode(file_get_contents('library.dat'), TRUE);
        $this->__autoload();
    }

}

$library = new library();

$html = new html();
$page = new page();
$page->main();