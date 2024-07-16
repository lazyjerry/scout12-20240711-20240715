<?php

// 過期的方法
function deprecated()
{
    die("deprecated");
}

// 判斷字串開頭的方法(PHP 8.0 以後即有）這是向下相容的方法
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        return substr($haystack, 0, $length) === $needle;
    }
}

// 判斷字串結尾的方法(PHP 8.0 以後即有）這是向下相容的方法
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}

// 判斷鎮列是 array 還是 dict (PHP 8.1 以後即有）這是向下相容的方法
if (!function_exists('array_is_list')) {
    function array_is_list(array $arr)
    {
        if ($arr === []) {
            return true;
        }
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}

// 是合法的 domain name
function isValidDomainName(string $domainName)
{
    return (preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $domainName) //valid chars check
        && preg_match("/^.{1,253}$/", $domainName) //overall length check
        && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domainName)); //length of each label
}

if (!function_exists('getBootstrapColClass')) {
    function getBootstrapColClass($default = 3, $up576px = 3, $up768px = 3, $up992px = 3, $up1200px = 3)
    {
        $arr = array();
        $arr[] = "col-{$default}";
        $arr[] = "col-sm-{$up576px}";
        $arr[] = "col-md-{$up768px}";
        $arr[] = "col-lg-{$up992px}";
        $arr[] = "col-xl-{$up1200px}";
        return implode(" ", $arr);
    }
}

if (!function_exists('getCleanArgs')) {
    // 淨化資料用途，取得一串不會被注入的字串
    function getCleanArgs($arg)
    {
        if (!isset($arg) || empty($arg)) {
            return "";
        }
        return htmlspecialchars(addslashes(trim($arg)));
    }
}

/**
 * Hides Some Characters in Email. Basically Used in Forget Password System
 *
 * @param string $email Email
 *
 * @return string
 *
 */
if (!function_exists('obfuscateEmail')) {

    function obfuscateEmail($email)
    {

        $em = explode("@", $email);
        $name = $em[0];
        $len = mb_strlen($name) / 2;

        return substr($name, 0, $len) . str_repeat('*', $len) . "@" . end($em);

    }
}

// 蛇形命名转换为驼峰命名
function convertSnakeToLowerCamel($value)
{
    $value = ucwords(str_replace(['_', '-'], ' ', $value));
    $value = str_replace(' ', '', $value);
    return lcfirst($value);
}
// 驼峰命名转换为蛇形命名
function convertCamelToSnake($value)
{
    // 以 UTF-8 模式删除空字符
    $value = preg_replace('/\s+/u', '', $value);
    // “?=”为正向预查，在任何开始匹配圆括号内的正则表达式模式的位置来匹配搜索字符串
    // 这里的正则表达式匹配所有大写字符的前一个字符
    $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', "$1_", $value));
    return $value;
}
// 附加一个:蛇形命名转换为大驼峰命名(首字母大写 如: FileName、 LineNumber、 MyFirstProgram)
function convertSnakeToUpperCamel($value)
{
    $value = ucwords(str_replace(['_', '-'], ' ', $value));
    $value = str_replace(' ', '', $value);
    return $value;
}
