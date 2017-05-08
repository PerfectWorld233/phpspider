<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/6
 * Time: 14:33
 */

/*
 CREATE TABLE `guba` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `article_title` text NOT NULL,
  `company_name` text NOT NULL,
  `company_code` text NOT NULL,
  `link` varchar(255) NOT NULL,
  `publish_time` varchar(255) NOT NULL,
  `update_time` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `influence` text NOT NULL,
  `bar_age` text NOT NULL,
  `stock_name` varchar(255) NOT NULL,
  `stock_code` varchar(32) NOT NULL,
  `comment` text NOT NULL,
  `view_num` int(10) NOT NULL,
  `comment_num` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='股吧';

*/
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

db::reset_connect(
    array(
        'host'  => 'localhost',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => 'root',
        'name'  => 'demo',
    )
);
$company_name = '长信基金';
$company_code ='80000243';
$end_time = '2014-01-01 00:00:00';               // 2014-10-1 之前的不抓取
$logfile = '../data/'.$company_code.'_'.date('md').'.log';

$list_url = 'http://guba.eastmoney.com/type,zg80000243_1.html';
$list_html = requests::get($list_url);
//echo $list_html;die;
//总页数
$sum_page = preg_match_all('/<div class="pager">(.*)<span/siU', $list_html, $pp);
$sum_page = preg_match_all('/(\d)+?/siU', $pp[1][0], $pp_vv);
$sum_page = ceil(intval($pp_vv[0][0])/80);

for($ll =4; $ll <= $sum_page; $ll ++)
{
    $list_url = 'http://guba.eastmoney.com/type,zg80000243_'.$ll.'.html';
    $list_html = requests::get($list_url);
    //列表页
    $arr_data = selector::select($list_html, "//div[contains(@class,'article')]");
    foreach($arr_data as $list_kk => $list_vv)
    {

        preg_match_all('/<span class="l3"><a.*>(.*)<\/a><\/span>/siU', $list_vv, $title_vv);
        $title = $title_vv[1][0];
        if(empty($title))
        {
            continue;
        }
        else
        {
            $view_num = selector::select($list_vv, "span.l1",'css');
            $comment_num = selector::select($list_vv, "span.l2",'css');
//            $title = selector::select($list_vv, "em + a",'css');
//            echo $title;die;
            $tmp_url = selector::select($list_vv,'#href="(.*?)"#',"regex");
            $link = $tmp_url[0];
            $author =  selector::select($list_vv, "span.l4",'css');
            $pattern = '@<a .*? data-popper="(.*?)" .*?>(.*?)</a>@';
            $arr_author =  selector::select($author, $pattern,"regex");

            $author_uid =$arr_author[0];
            $author= $author_uid.'|'.$arr_author[1];

            $author_url = 'http://iguba.eastmoney.com/'.$author_uid;
            $author_html = requests::get($author_url);
            usleep(rand(10, 100));
            preg_match_all('/data-influence="(.*)">/siU', $author_html, $inf_vv);
            $influence = $inf_vv[1][0];                                     //  影响力
            preg_match_all('/data-influence.*<span>(.*)<\/span>/siU', $author_html, $age_vv);
            $bar_age = $age_vv[1][0];                                       // 吧龄
            $update_time = selector::select($list_vv, "span.l5",'css');     //更新时间

            //详情页
            $url = 'http://guba.eastmoney.com/'.$link;
            $html = requests::get($url);
            usleep(rand(10, 100));
            $publish_time_tmp = selector::select($html, "//div[contains(@class, 'zwfbtime')]");
            preg_match_all('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',$publish_time_tmp, $publish_time_vv);
            $publish_time = $publish_time_vv[0][0];
            if(!empty($publish_time)&&(strtotime($publish_time) < strtotime($end_time)))
            {
                break 2;
            }
            $stock_name = selector::select($html, "//span[contains(@id,'stockname')]//a");
            $stock_code = selector::select($html, "#var OuterCode = \"(.*)\"#", "regex");
            $comment      = selector::select($html, "//div[contains(@class,'zwlitext stockcodec')]"); //评论
            $comment = implode("||", $comment);

            $data = array(
                'company_name' => $company_name,
                'company_code' => $company_code,
                'article_title' => $title,
                'link' => $url,
                'publish_time' => $publish_time,
                'update_time' => $update_time,
                'author' => $author,
                'influence' => $influence,
                'bar_age' => $bar_age,
                'stock_name' => $stock_name,
                'stock_code' => $stock_code,
                'comment' => $comment,
                'view_num' => $view_num,
                'comment_num' => $comment_num,
            );
            $res = db::insert('guba', $data);
        }

    }
    echo "\r\n lise_page_num".$ll."\r\n";
}
