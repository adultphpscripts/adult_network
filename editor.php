<?php
error_reporting(-1);
session_start();
define("encryption_method", "AES-128-CBC");
define("key", $_SERVER["HTTP_HOST"]);
class editor {

    const PIN_CODE = '12345';
    private $LIBRARY_FILE = __DIR__ . '/library.dat';
    const _CHR_ = '$';
    public $URL = '';

    public $json = '';
    function encrypt($data) {
        $key = key;
        $plaintext = $data;
        $ivlen = openssl_cipher_iv_length($cipher = encryption_method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        return $ciphertext;
    }
    function decrypt($data) {
        $key = key;
        $c = base64_decode($data);
        $ivlen = openssl_cipher_iv_length($cipher = encryption_method);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        }
    }
    

    function get_code($class, $method) {
        if ($class == null || $method == null) { return ''; }
        $json = json_decode(file_get_contents($this->LIBRARY_FILE), TRUE);
        return $json[$class][$method];
    }
    function get_methods($class) {
        if ($class == null) { return ''; }
        $response = '';
        $json = json_decode(file_get_contents($this->LIBRARY_FILE), TRUE);
        foreach ($json[$class] as $method => $code) {
            $response.='<option value="' . $method . '">';
        }
        return $response;
    }
    
    function get_classes() {
        $response = '';
        $json = json_decode(file_get_contents($this->LIBRARY_FILE), TRUE);
        foreach ($json as $class => $nxt) {
            $response.='<option value="' . $class . '">';
        }
        return $response;
    }

    function save_data() {
        $data = '';
        $json = json_decode(file_get_contents($this->LIBRARY_FILE), TRUE);
        if ($_POST["action"] == "delete") {
          unset($json[$_POST["class"]][$_POST["method"]]);  
        }elseif ($_POST["action"] == "save") {
          $json[$_POST["class"]][$_POST["method"]] = $_POST["code"];
        foreach ($json as $class => $nxt) {
            $data.='class ' . $class . ' extends library { ' . PHP_EOL;
            foreach ($nxt as $method => $code) {
                $data.='function ' . $method . ' (' . self::_CHR_ . 'vars) {' . PHP_EOL;
                $data.=$code . PHP_EOL;
                $data.='}' . PHP_EOL;
            }
            $data.='}' . PHP_EOL;
        }
       // file_put_contents(__DIR__.'/library.code', $data);
        file_put_contents(__DIR__.'/library.dat', json_encode($json, JSON_PRETTY_PRINT));
        }
    }

    function editor() {
        echo '<form action="'.$this->URL.'" method="post">
        <input type="hidden" name="PIN_CODE" value="' . $_POST["PIN_CODE"] . '">
        <table width="100%">
            <tr>
                <td>
                <label for="class">Class</label>
                <input onchange="document.getElementById('."'".'btnSubmit'."'".').click();" list="classes" id="class" autocomplete="off" name="class" value="' . $_POST["class"] . '">
                <datalist id="classes">
                ' . $this->get_classes() . ' 
                </datalist>
                </td>
                <td>
                <label for="class">Function</label>
                <input list="methods" autocomplete="off" id="method" name="method" value="' . $_POST["method"] . '">
                <datalist id="methods">
                ' . $this->get_methods($_POST["class"]) . ' 
                </datalist>
                </td>
                <td>
                    <label for="open">    
                    <input type="radio" id="open" name="action" value="open" checked>
                    Open
                    </label>
                </td>
                <td>
                    <label for="save">    
                    <input type="radio" id="save" name="action" value="save">
                        Save
                    </label>
                </td>
                <td>
                    <label for="delete">    
                    <input type="radio" id="delete" name="action" value="Delete">
                        Delete
                    </label>
                </td>
                <td>
                    <button id="btnSubmit" type="submit">Submit</button>
                </td>
            </tr>
            <tr>
                <td colspan="6">
                    <textarea id="code" name="code" style="width: 100%; height: 500px;">' . htmlspecialchars($this->get_code($_POST["class"], $_POST["method"])) . '</textarea>
                </td>
            </tr>
        </table>
        </form>';
    }

    function signin() {
        if ( $_POST["PIN_CODE"] !==self::PIN_CODE) {
            echo '<form action="' . $this->URL . '" method="post">
            <input type="password" name="PIN_CODE">
            <input type="submit" name="action" value="login">
            </form>';
            return FALSE;
        }else{
            if (isset($_POST["class"]) && isset($_POST["method"]) && isset($_POST["action"])) {
                $this->save_data();
            }
            $this->editor();
            return TRUE;
        }

    }

    function __construct() {
        $this->URL = 'https://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
        $this->json = file_get_contents($this->LIBRARY_FILE);
       // $this->json = file_get_contents($this->LIBRARY_FILE);
        if ($this->signin()==FALSE) { die(); }
    }
}
$editor = new editor();
