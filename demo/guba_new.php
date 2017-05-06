<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/6
 * Time: 14:33
 */


ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$company_name = '长信基金';
$company ='80000243';

$url = "http://guba.eastmoney.com/type,zg80000243_1.html";
$html = requests::get($url);


$selector = "//div[contains(@class,'page-header')]//h1/a";
// 提取结果
$title = selector::select($html, $selector);
echo $result;
// #stockname

