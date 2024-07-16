<?php

namespace App\Libraries;

class MathUtils
{
    // bcadd — 两个任意精度数字的加法计算
    // bccomp — 比较两个任意精度的数字
    // bcdiv — 两个任意精度的数字除法计算
    // bcmod — 任意精度数字取模
    // bcmul — 两个任意精度数字乘法计算
    // bcpow — 任意精度数字的乘方
    // bcpowmod — Raise an arbitrary precision number to another, reduced by a specified modulus
    // bcscale — 设置/获取所有 bc math 函数的默认小数点保留位数
    // bcsqrt — 任意精度数字的二次方根
    // bcsub — 两个任意精度数字的减法

/**
 * 精确加法
 * @param [type] $a [description]
 * @param [type] $b [description]
 */
    public static function add($a, $b, $scale = '2')
    {
        return bcadd($a, $b, $scale);
    }

/**
 * 精确减法
 * @param [type] $a [description]
 * @param [type] $b [description]
 */
    public static function sub($a, $b, $scale = '2')
    {
        return bcsub($a, $b, $scale);
    }

/**
 * 精确乘法
 * @param [type] $a [description]
 * @param [type] $b [description]
 */
    public static function mul($a, $b, $scale = '2')
    {
        return bcmul($a, $b, $scale);
    }

/**
 * 精确除法
 * @param [type] $a [description]
 * @param [type] $b [description]
 */
    public static function div($a, $b, $scale = '2')
    {
        return bcdiv($a, $b, $scale);
    }

/**
 * 精确求余/取模
 * @param [type] $a [description]
 * @param [type] $b [description]
 */
    public static function mod($a, $b)
    {
        return bcmod($a, $b);
    }

/**
 * 比较大小
 * @param [type] $a [description]
 * @param [type] $b [description]
 * 大于 返回 1 等于返回 0 小于返回 -1
 */
    public static function comp($a, $b, $scale = '5')
    {
        return bccomp($a, $b, $scale); // 比较到小数点位数
    }
}
