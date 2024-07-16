<?php

/**
 * Finds and return the ipaddres of client user
 *
 * @param array $ipaddress IpAddress
 *
 */
if (!function_exists('ip_address')) {

    function ip_address()
    {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_FORWARDED'])) {
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        } else if (isset($_SERVER['REMOTE_ADDR'])) {
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        } else {
            $ipaddress = 'UNKNOWN';
        }

        return $ipaddress;
    }

}

// 取得所有 IP 並且 json encode
function get_full_ip_address()
{
    $ipaddress = [];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress['HTTP_X_FORWARDED_FOR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress['HTTP_X_FORWARDED'] = $_SERVER['HTTP_X_FORWARDED'];
    }
    if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress['HTTP_FORWARDED_FOR'] = $_SERVER['HTTP_FORWARDED_FOR'];
    }
    if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress['HTTP_FORWARDED'] = $_SERVER['HTTP_FORWARDED'];
    }
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }

    return json_encode($ipaddress);
}

// 簡單限制 ip 位址
function allow_ips($allowed_ips = ['127.0.0.1'])
{
    if (empty($allowed_ips)) {
        return false;
    }
    $now_ip = get_now_ip_address();
    if (in_array($now_ip, $allowed_ips)) {
        return true;
    }
    return false;
}

// 取得現有 ip 位址
function get_now_ip_address()
{
    $ipaddress = "";
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    }

    return (empty($ipaddress)) ? "0.0.0.0" : $ipaddress;
}

// 取得現有 ip 類型的欄位
function get_now_ip_type()
{
    $type = "";
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $$type = "HTTP_X_FORWARDED_FOR";
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $$type = "HTTP_X_FORWARDED";
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $type = "HTTP_FORWARDED_FOR";
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $type = "HTTP_FORWARDED";
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $type = "REMOTE_ADDR";
    }

    return (empty($ipaddress)) ? "unknow" : $ipaddress;
}
