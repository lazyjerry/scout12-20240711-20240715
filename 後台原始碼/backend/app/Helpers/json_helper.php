<?php

// 判斷是不是 json
function isJson($string)
{
    if (!is_string($string)) {
        return false;
    }

    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

// 中文的 json 編碼
function jsonEncodeChinese($array)
{
    return json_encode($array, JSON_UNESCAPED_UNICODE);
}

function jsonDecode($str)
{
    if (is_null($str)) {
        return null;
    } else if (is_object($str)) {
        return json_decode(json_encode($str), true);
    } else if (is_array($str)) {
        return $str;
    }

    return json_decode($str, true);
}

// 多個內涵 json 的 Array 編碼
function jsonEncodeMutiJson($array)
{
    $new_arr = [];
    foreach ($array as $key => $value) {
        if (is_json($value)) {
            $value = json_decode($value, true);
            if (is_string($key)) {
                $new_arr[$key] = $value;
            } else {
                $new_arr[] = $value;
            }
        } else if (is_array($value)) {
            $new_arr[] = $value;
        } else if (!empty($value)) {
            $new_arr[$key] = $value;
        }
    }
    return json_encode_chinese($new_arr);
}

function getJsonBody()
{
    $str = file_get_contents('php://input');
    if (empty($str)) {
        return [];
    }
    $is_json = is_json($str);
    if (!$is_json) {
        return [];
    }
    return json_decode($str, true);
}
