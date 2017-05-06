<?php
/**
 * Created by PhpStorm.
 * User: xzy
 * Date: 2017/5/6
 * Time: 13:10
 */

ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';

$arr_keywords2 = array();
db::reset_connect(

    array(
        'host'  => 'localhost',
        'port'  => 3306,
        'user'  => 'root',
        'pass'  => 'root',
        'name'  => 'demo',
    )

);


$company_code ='80000243';

$i = 0;


requests::set_useragents(array(
    "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36",

));

requests::set_referer("http://guba.eastmoney.com");
$logfile = '../data/'.$company_code.'_'.date('md').'.log';

$arr_keywords = array(
    'type,zg80000243',
) ;
$arr_keywords2 = array_values(array_unique($arr_keywords));

$i = 0;
mb_regex_encoding("UTF-8");

requests::set_useragents(array(
    "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36",
    //   "Opera/9.80 (Android 3.2.1; Linux; Opera Tablet/ADR-1109081720; U; ja) Presto/2.8.149 Version/11.10",
    //   "Mozilla/5.0 (Android; Linux armv7l; rv:9.0) Gecko/20111216 Firefox/9.0 Fennec/9.0"
));

requests::set_referer("http://fund.eastmoney.com");

requests::set_referer("http://guba.eastmoney.com");
//print_r($arr_keywords) ;
foreach ($arr_keywords2 as $k=>$keyword)  {
    if (empty($keyword))  continue ;
    echo 'keyword is:'.$keyword ;
    echo "\r\n  +++ \r\n" ;

    $i = 0;


    $years = 0;
    $last_date_post = '';

    $has_nextpage = true;


    while ($has_nextpage ) {

        $i++;

        $url='http://guba.eastmoney.com/'.$keyword.',f_'.$i.'.html';
        $has_nextpage = false;
        sleep(10);



        echo $url."\r\n" ;

        $html = requests::get($url);


        if ($i ==1) {
            $stockname = selector::select($html, "//span[contains(@id,'stockname')]//a");
            if ($stockname =='吧' ) {
                error_log("\r\n code is:".$keyword,3,$logfile);


                $url='http://guba.eastmoney.com/'.$keyword.',f_'.$i.'.html';
                echo $url."\r\n" ;
                $has_nextpage = false;
                sleep(10);


                $html = requests::get($url);
                $stockname = selector::select($html, "//span[contains(@id,'stockname')]//a");
                if ($stockname =='吧' ) {
                    error_log("\r\n code is:".$keyword,3,$logfile);
                    continue ;
                }

            }
        }
        $arr_data = selector::select($html, "//div[contains(@id,'articlelistnew')]//div[contains(@class,'article')]");
        $html =  selector::select($html, "//div[contains(@id,'articlelistnew')]//div[contains(@class,'pager')]");

        echo "\r\n total records:".count($arr_data)."\r\n";
        if (count($arr_data) == 0) {
            error_log("\r\n no data ,url  is:".$url,3,$logfile);
            echo "\r\n nodata ";
            continue ;
        }
        if (!is_array($arr_data))  continue ;

        if ($html) {
            $pattern = '@"list,(.*?),f_\|(\d+)\|(\d+)\|(\d+)"@';

            $pageinfo =  selector::select($html, $pattern, "regex");
            $total_items = $pageinfo[1];
            $num_perpage = $pageinfo[2];
            $cur_page = $pageinfo[3];
            //	$keyword = $pageinfo[0] ; //keyword 要换成新的

            $total_pages =  ceil($total_items*1.0/$num_perpage) ;
            if ($cur_page < $total_pages) {
                $has_nextpage = true ;

            }
            if ($cur_page >10) {
                $has_nextpage = false;
                continue ;
            }

        }



        foreach ($arr_data as $v_data) {
            //	error_log("\r\n:".print_r($v_data,true),3,"0303gblist_".$pn.".log");
            //	print_r($v_data);
            $read_num = selector::select($v_data, "span.l1",'css');
            $cmt_num = selector::select($v_data, "span.l2",'css');
            $title = selector::select($v_data, "span.l3",'css');

            $has_settop = selector::select($title, "em",'css');
            if ($has_settop) {
                continue ;
            }

            $author =  selector::select($v_data, "span.l4",'css');

            $pattern = '@<a .*? data-popper="(.*?)" .*?>(.*?)</a>@';

            $arr_author =  selector::select($author, $pattern,"regex");

            if ( $arr_author) {

                $author_uid =$arr_author[0];
                $author= $author_uid.'|'.$arr_author[1];
            }

            $date_update = selector::select($v_data, "span.l5",'css');
            $date_post = selector::select($v_data, "span.l6",'css');

            $title_url = selector::select($title,'@href="(.*?)"@',"regex");
            $title_title = selector::select($title,'//a');



            $str_date =  selector::select($v_data, "span.l6",'css');




            if  (empty($title))  continue ;
            if ($date_post != $last_date_post) {

                //date_post 是倒序排列的，变大，表示进入了12月份
                if ($date_post > $last_date_post) {
                    $years++;
                    //	error_log("\r\n years:".$years.",date is ".$date_post." ,last_datepost:".$last_date_post.
                    //	",url is:".$url,
                    //	3,	 $logfile);

                    if ($years >=5) {

                        //	$has_nextpage = false ;
                        //	error_log("\r\n pass 2014 ",3,$logfile);

                    }



                }
                $last_date_post = $date_post;
            }

            $summary =  $title ;

            $data = array(
                'company'=>$company_code,
                'code' =>  $keyword,
                'title'=> $title_title,
                'link'  =>  $title_url,
                'author'=> $author,
                'date_post'=>'2017-'.$date_post,
                'date_update'=>'2017-'.$date_update,
                'view_num'=>$read_num,
                'cmt_num'=>$cmt_num,
            );

            $rows = db::insert('guba2', $data);
            echo "\r\n affected rows:".$rows."\r\n";


        }
        break;


    }




}
//fclose($fp);
echo 'done';
