<?php
/**
 * @describe:
 * @author: Jerry Yang(hy0kle@gmail.com)
 * */
include_once('lib/curlTools.php');
include_once('lib/simple_html_dom.php');

// 前两页手工取完了, 目前共 88 页. @2014.12.25
//create_data_by_page(2);

//for ($page = 3; $page <= 88; $page++)
//{
//    create_data_by_page($page);
//    $usleep_time = mt_rand(500, 5000);
//    echo "usleep: {$usleep_time}\n";
//    usleep($usleep_time);
//}

// 全部拍完 DATE: Thu Dec 25 08:28:08 CST 2014

function create_data_by_page($page)
{
    $api  = sprintf('http://kaijiang.zhcw.com/zhcw/html/ssq/list_%d.html', $page);
    echo $api . PHP_EOL;

    $conf = array(
        'use_cookie' => true,
    );
    $ct  = new curlTools($conf);
    $dom = new simple_html_dom();

    $html = $ct->get($api);
    $dom->load($html);

    $trs = $dom->find('table tr');
    $trs_total = count($trs) - 1; // 略过非数据域

    for ($i = 2; $i < $trs_total; $i++)
    {
        //$text = $trs[$i]->innertext();
        //echo $text . PHP_EOL;
        //exit;

        // 开奖日期
        $date   = $trs[$i]->children(0)->plaintext;
        // 开将期数
        $number = $trs[$i]->children(1)->plaintext;
        //echo "{$date}\t{$number}" . PHP_EOL;

        //$sub_html   = $trs[$i]->children(2)->innertext;
        //echo $sub_html;exit;

        $sub_obj   = $trs[$i]->children(2)->find('em');
        $sub_total = count($sub_obj);
        //echo $sub_total; exit;

        if ($sub_total > 0)
        {
            $dt_str = "{$date}\t{$number}\t";
            $ssq = array();
            foreach ($sub_obj as $obj)
            {
                $ssq[] = $obj->plaintext;
            }
            //var_dump($ssq);exit;

            $dt_str .= implode("\t", $ssq) . "\n";

            file_put_contents('data/ssq.txt', $dt_str, FILE_APPEND);
        }
    }

    $dom->clear();

    $dom = NULL;
    $ct  = NULL;
}
/* vi:set ts=4 sw=4 et fdm=marker: */

