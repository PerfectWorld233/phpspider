<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/5/6
 * Time: 14:33
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
$logfile = '../data/'.$company_code.'_'.date('md').'.log';

$list_url = 'http://guba.eastmoney.com/type,zg80000243_1.html';
$list_html = requests::get($list_url);
//echo $list_html;die;
//总页数
$sum_page = preg_match_all('/<div class="pager">(.*)<span/siU', $list_html, $pp);
$sum_page = preg_match_all('/(\d)+?/siU', $pp[1][0], $pp_vv);
$sum_page = ceil(intval($pp_vv[0][0])/80);
for($ll =1; $ll <= $sum_page; $ll ++)
{
    $list_url = 'http://guba.eastmoney.com/type,zg80000243_'.$ll.'.html';
    $list_html = requests::get($list_url);
//    echo $list_html;die;
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
            $click_num = selector::select($list_vv, "span.l1",'css');
            $comment_num = selector::select($list_vv, "span.l2",'css');
//            $title = selector::select($list_vv, "em + a",'css');
//            echo $title;die;
            $tmp_url = selector::select($list_vv,'#href="(.*?)"#',"regex");
            $link = $tmp_url[0];
            $author = selector::select($list_vv, "span.l4 > a",'css');
            $update_time = selector::select($list_vv, "span.l5",'css');

            //详情页
            $url = 'http://guba.eastmoney.com/'.$link;
            $html = requests::get($url);
            usleep(rand(30, 500));
            //于 2017-05-05 15:06:16
            $publish_time = selector::select($html, "//div[contains(@class, 'zwfbtime')]");
            preg_match_all($publish_time, '/[0-9]{4}/siU', $publish_time_vv);
            print_r($publish_time_vv);die;
            $stock_name = selector::select($html, "//span[contains(@id,'stockname')]//a");
            $stock_code = selector::select($html, "#var OuterCode = \"(.*)\"#", "regex");
            $comment      = selector::select($html, "//div[contains(@class,'zwlitext stockcodec')]"); //评论
            // 检查是否抽取到内容
            $data = array(
                'article_title' => $title,
                'link' => $url,
                'publish_time' => $publish_time,
                'author' => $author,
                'stock_name' => $stock_name,
                'stock_code' => $stock_code,
                'comment' => $comment
            );
            // 查看数据是否正常
            $res = db::insert('guba', $data);
//            var_dump(mysqli_error());
           // die;
        }

    }
    echo "\r\n lise_page_num".$ll."\r\n";

}

/*



*/