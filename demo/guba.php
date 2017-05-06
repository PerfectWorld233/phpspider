<?php
/**
 * http://guba.eastmoney.com
 * User: taylor yue
 * Date: 2017/5/6
 * Time: 8:02
 *
 */


//ini_set("memory_limit", "1024M");
//require dirname(__FILE__).'/../core/init.php';
//
///* Do NOT delete this comment */
///* 不要删除这段注释 */
//
////  股吧入口
//$url = 'http://fund.eastmoney.com/company/80000243.html';
//$html = requests::get($url);
//$result['company_code'] ='80000243';
//$result['company_name']= '长信基金';
//$result['company'] = selector::select($html, "td.txt_left","css");
//$result['code'] = selector::remove($result['company'], "a", "css");
////print_r($result['company']);die;
//
//
////长信创新驱动股票吧
////http://guba.eastmoney.com/list,of519935.html
//
//// 获取详情页链接
//$url = 'http://guba.eastmoney.com/list,of519935.html';
//$html = requests::get($url);
//$result['company'] = selector::select($html, "div","css");
//$result['code'] = selector::remove($result['company'], "a", "css");

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
));
requests::set_referer("http://fund.eastmoney.com");
requests::set_referer("http://guba.eastmoney.com");
//print_r($arr_keywords) ;
foreach ($arr_keywords2 as $k=>$keyword)
{
    if (empty($keyword))  continue ;
    echo 'keyword is:'.$keyword ;
    echo "\r\n  +++ \r\n" ;
    $i = 0;
    $years = 0;
    $last_date_post = '';
    $has_nextpage = true;
    while ($has_nextpage )
    {
        $i++;
        $url='http://guba.eastmoney.com/'.$keyword.',f_'.$i.'.html';
        echo $url;die;
        $has_nextpage = false;
        sleep(1);
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
            if ($cur_page < $total_pages)
            {
                $has_nextpage = true ;

            }
            if ($cur_page >10)
            {
                $has_nextpage = false;
                continue ;
            }
        }
        foreach ($arr_data as $v_data)
        {
            //	error_log("\r\n:".print_r($v_data,true),3,"0303gblist_".$pn.".log");
            //	print_r($v_data);
            $read_num = selector::select($v_data, "span.l1",'css');
            $cmt_num = selector::select($v_data, "span.l2",'css');
            $title = selector::select($v_data, "span.l3",'css');
            $has_settop = selector::select($title, "em",'css');
            if ($has_settop)
            {
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
            if ($date_post != $last_date_post)
            {
                //date_post 是倒序排列的，变大，表示进入了12月份
                if ($date_post > $last_date_post)
                {
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
echo 'done';













$configs = array(
    'name' => '长信股吧',
    'tasknum' => 1,
    //'multiserver' => true,
    'log_show' => true,
    //'save_running_state' => false,
    'domains' => array(
        'http://guba.eastmoney.com'
    ),
    'scan_urls' => array(
        "http://guba.eastmoney.com/",
        "http://www.13384.com/xingganmeinv/",
    ),
    'list_url_regexes' => array(
        "http://www.13384.com/qingchunmeinv/index_\d+.html",
    ),
    'content_url_regexes' => array(
        "http://www.13384.com/qingchunmeinv/\d+.html",
        "http://www.13384.com/xingganmeinv/\d+.html",
        "http://www.13384.com/mingxingmeinv/\d+.html",
        "http://www.13384.com/siwameitui/\d+.html",
        "http://www.13384.com/meinvmote/\d+.html",
        "http://www.13384.com/weimeixiezhen/\d+.html",
    ),
    //'export' => array(
    //'type' => 'db',
    //'table' => 'meinv_content',
    //),
    'fields' => array(
        // 标题
        array(
            'name' => "name",
            'selector' => "//div[@id='Article']//h1",
            'required' => true,
        ),
        // 分类
        array(
            'name' => "category",
            'selector' => "//div[contains(@class,'crumbs')]//span//a",
            'required' => true,
        ),
        // 发布时间
        array(
            'name' => "addtime",
            'selector' => "//p[contains(@class,'sub-info')]//span",
            'required' => true,
        ),
        // API URL
        array(
            'name' => "url",
            'selector' => "//p[contains(@class,'sub-info')]//span",
            'required' => true,
        ),
        // 图片
        array(
            'name' => "image",
            'selector' => "//*[@id='big-pic']//a//img",
            'required' => true,
        ),
        // 内容
        array(
            'name' => "content",
            'selector' => "//div[@id='pages']//a//@href",
            'repeated' => true,
            'required' => true,
            'children' => array(
                array(
                    // 抽取出其他分页的url待用
                    'name' => 'content_page_url',
                    'selector' => "//text()"
                ),
                array(
                    // 抽取其他分页的内容
                    'name' => 'page_content',
                    // 发送 attached_url 请求获取其他的分页数据
                    // attached_url 使用了上面抓取的 content_page_url
                    'source_type' => 'attached_url',
                    'attached_url' => 'content_page_url',
                    'selector' => "//*[@id='big-pic']//a//img"
                ),
            ),
        ),
    ),
);

$spider = new phpspider($configs);

$spider->on_extract_field = function($fieldname, $data, $page)
{
    if ($fieldname == 'url')
    {
        $data = $page['request']['url'];
    }
    elseif ($fieldname == 'name')
    {
        $data = trim(preg_replace("#\(.*?\)#", "", $data));
    }
    if ($fieldname == 'addtime')
    {
        $data = strtotime(substr($data, 0, 19));
    }
    elseif ($fieldname == 'content')
    {
        $contents = $data;
        $array = array();
        foreach ($contents as $content)
        {
            $url = $content['page_content'];
            // md5($url) 过滤重复的URL
            $array[md5($url)] = $url;

            //// 以纳秒为单位生成随机数
            //$filename = uniqid().".jpg";
            //// 在data目录下生成图片
            //$filepath = PATH_ROOT."/images/{$filename}";
            //// 用系统自带的下载器wget下载
            //exec("wget -q {$url} -O {$filepath}");
            //$array[] = $filename;
        }
        $data = implode(",", $array);
    }
    return $data;
};

$category = array(
    '丝袜美女' => 'siwameitui',
    '唯美写真' => 'weimeixiezhen',
    '性感美女' => 'xingganmeinv',
    '明星美女' => 'mingxingmeinv',
    '清纯美女' => 'qingchunmeinv',
    '美女模特' => 'meinvmote',
);

$spider->on_extract_page = function($page, $data) use ($category)
{
    if (!isset($category[$data['category']]))
    {
        return false;
    }

    $data['dir'] = $category[$data['category']];
    $data['content'] = $data['image'].','.$data['content'];
    $data['image'] = str_replace("ocnt0imhl.bkt.clouddn.com", "file.13384.com", $data['image']);
    $data['image'] = $data['image']."?imageView2/1/w/320/h/420";
    $data['content'] = str_replace("ocnt0imhl.bkt.clouddn.com", "file.13384.com", $data['content']);
    $sql = "Select Count(*) As `count` From `meinv_content` Where `name`='{$data['name']}'";
    $row = db::get_one($sql);
    if (!$row['count'])
    {
        db::insert("meinv_content", $data);
    }
    print_r($data);die;
    return $data;
};

$spider->start();



