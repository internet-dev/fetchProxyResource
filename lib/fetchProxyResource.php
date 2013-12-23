<?php
/**
* @file fetchProxyResource.php
* @brief 代理资源抓取与检测,不考虑性能
* @author hy0kle@gmail.com
* @version 1.0
* @date 2013-12-20
 */

include_once('curlTools.php');
include_once('simple_html_dom.php');

// 抓取重试次数
define('FPR_FETCH_RETRY_TIMES',     3);
// 代理检测的时间间隔, 5天
define('FPR_CHECK_TIME_INTERVAL',   5 * 3600 * 24);
// 完成一个页面后的睡眠时间
define('FPR_FETCH_SLEEP_TIME',      1);
define('FPR_FETCH_HTTP_OK',         200);

/**
 * 抽象类,将公用的代码进行封装
 * 具体的存储只申明接口,实现交给实现者
 * 存储层 1.0 仅提供 sqlite3 版
 * */
abstract class fetchProxyResource
{
    /**
     * 抓取入口
     * */
    public function fetch()
    {
        /** 可抓取,但大部分 ip 不可用 */
        $this->_fetchCnproxy();

        /** 2013.12.23 goodips 网站不可用 */
        //$this->_fetchGoodips();

        /** 可用,貌似数据每天有更新 */
        $this->_fetchXici();
    }

    private function _fetchCnproxy()
    {
        $cnproxy = array(
            'http://www.cnproxy.com/proxy1.html',
            'http://www.cnproxy.com/proxy2.html',
            'http://www.cnproxy.com/proxy3.html',
            'http://www.cnproxy.com/proxy4.html',
            'http://www.cnproxy.com/proxy5.html',
            'http://www.cnproxy.com/proxy6.html',
            'http://www.cnproxy.com/proxy7.html',
            'http://www.cnproxy.com/proxy8.html',
            'http://www.cnproxy.com/proxy9.html',
            'http://www.cnproxy.com/proxy10.html'
        );

        foreach ($cnproxy as $url)
        {
            $curl_tool = new curlTools();
            $html_dom  = new simple_html_dom();
            $html = $curl_tool->get($url);

            $html_dom->load($html);
            $trs = $html_dom->find('table tr');

            $total = count($trs);
            for ($i = 2; $i < $total; $i++)
            {
                $text = $trs[$i]->children(0)->innertext;
                $text = preg_replace('/<script type=text\/javascript>document\.write\(":"\+([+rqvcamlbiw]+)\)<\/script>/', ':$1', $text);
                $text = str_replace('+', '', $text);
                $text = str_replace('r', 8, $text);
                $text = str_replace('q', 0, $text);
                $text = str_replace('v', 3, $text);
                $text = str_replace('c', 1, $text);
                $text = str_replace('a', 2, $text);
                $text = str_replace('m', 4, $text);
                $text = str_replace('l', 9, $text);
                $text = str_replace('b', 5, $text);
                $text = str_replace('i', 7, $text);
                $text = str_replace('w', 6, $text);

                $exp_data = explode(':', $text);
                $ext = array(
                    'source' => 'cnproxy.com',
                );
                $this->write($exp_data[0], $exp_data[1], $ext);
            }

            $html_dom->clear();

            /** 销毁对象 */
            $html_dom  = NULL;
            $curl_tool = NULL;

            /** 为了防止短时间访问太频繁,抓完一个页面后睡眠 */
            sleep(FPR_FETCH_SLEEP_TIME);
        }
    }

    private function _fetchGoodips()
    {
        $goodips = array(
            'http://www.goodips.com/index.html?pageid=1',
            'http://www.goodips.com/index.html?pageid=2',
            'http://www.goodips.com/index.html?pageid=3',
            'http://www.goodips.com/index.html?pageid=4',
            'http://www.goodips.com/index.html?pageid=5',
            'http://www.goodips.com/index.html?pageid=6',
            'http://www.goodips.com/index.html?pageid=7',
            'http://www.goodips.com/index.html?pageid=8',
            'http://www.goodips.com/index.html?pageid=9',
            'http://www.goodips.com/index.html?pageid=10'
        );

        foreach ($goodips as $url)
        {
            $curl_tool = new curlTools();
            $html_dom  = new simple_html_dom();
            $html = $curl_tool->get($url);
            //debug($html, __METHOD__, 1);

            $html_dom->load($html);
            $items = $html_dom->find('table tr');

            $total = count($items);
            for($j = 1; $j <= $total - 1; $j++)
            {
                $ip   = $items[$j]->children(0)->innertext;
                $port = $items[$j]->children(1)->innertext;

                $ext = array(
                    'source' => 'goodips.com',
                );
                $this->write($ip, $port, $ext);
            }

            $html_dom->clear();

            /** 销毁对象 */
            $html_dom  = NULL;
            $curl_tool = NULL;

            /** 为了防止短时间访问太频繁,抓完一个页面后睡眠 */
            sleep(FPR_FETCH_SLEEP_TIME);
        }
    }

    private function _fetchXici()
    {
        $xici = array(
            'http://www.xici.net.co/nn/1',
            'http://www.xici.net.co/nn/2',
            'http://www.xici.net.co/nn/3'
        );

        foreach ($xici as $url)
        {
            $curl_tool = new curlTools();
            $html_dom  = new simple_html_dom();
            $html = $curl_tool->get($url);
            //debug($html, __METHOD__);

            $html_dom->load($html);
            $items = $html_dom->find('tr');

            $total = count($items);
            for($j = 1; $j <= $total - 1; $j++)
            {
                $ip   = $items[$j]->children(1)->innertext;
                $port = $items[$j]->children(2)->innertext;

                $ext = array(
                    'source' => 'xici.net.co',
                );
                $this->write($ip, $port, $ext);
            }

            $html_dom->clear();

            /** 销毁对象 */
            $html_dom  = NULL;
            $curl_tool = NULL;

            /** 为了防止短时间访问太频繁,抓完一个页面后睡眠 */
            sleep(FPR_FETCH_SLEEP_TIME);
        }
    }

    /**
     * @brief check 检测代理可用性接口,被动调用
     *
     * @return: viod
     */
    public function check()
    {
        $prepare_resoure = $this->getPrepareResource();
        foreach ($prepare_resoure as $source)
        {
            $last_check_time = time();
            $ping_ret = $this->_ping($source['ip'], $source['port'], $source['ext']);

            $check_info = array(
                'last_check_time' => $last_check_time,
                'status' => $ping_ret,
            );
            $this->updateResource($source['ip'], $source['port'], $check_info);
        }

        return;
    }


    /**
     * @brief ping 专 ping 百度,查看返回的状态码
     *
     * @param: $ip
     * @param: $port
     *
     * @return: bool true: 代理可以ping百度; false: 尝了三次仍然失败
     */
    private function _ping($ip, $port, $ext = array())
    {
        $http_conf = array(
            'proxy'      => true,
            'proxy_host' => $ip,
            'proxy_port' => $port,
        );
        /** 如果有 HTTPS 的代理,放入到 $ext 中 TODO */

        $retry = FPR_FETCH_RETRY_TIMES;
        while ($retry > 0)
        {
            $curl_tool = new curlTools($http_conf);
            $ping_url  = 'http://www.baidu.com/';
            $html = $curl_tool->get($ping_url);

            $http_info = $curl_tool->getInfo();
            //print_r($http_info);exit();

            if (is_array($http_info) && isset($http_conf['after'])
                && isset($http_conf['after']['http_code'])
                && FPR_FETCH_HTTP_OK == $http_conf['after']['http_code'])
            {
                return true;
            }

            usleep((int)(FPR_FETCH_SLEEP_TIME * 0.1 + $retry));
            --$retry;
        }

        return false;
    }

    /**
     * @brief write 受保护方法
     *
     * @param: $ip
     * @param: $port
     * @param: $ext 扩展字段
     *
     * @return: void
     */
    abstract protected function write($ip, $port, $ext);

    /**
     * @brief getPrepareResource 取待处理的代理资源
     *
     * @return: array() 返回结果集,每条结果必须包括:
     *      ip: 代理 ip
     *      port: 商品号
     */
    abstract protected function getPrepareResource();

    /**
     * @brief updateResource 更新代理资源的可用状态
     *
     * @param: $ip
     * @param: $port
     * @param: (array)$status
     *      status 检查后的可用状态 1: 可用; 0: 不可用
     *      last_check_time
     *
     * @return: void
     */
    abstract protected function updateResource($ip, $port, $check_info);

     /**
     * @brief export 将可用的代理资源导出为文本
     *
     * @param: $file_name
     *
     * @return: void
     */
    abstract public function export($file_name);
}

$sapi_type = php_sapi_name();
if ('cli' == $sapi_type)
{
    define('LCRT', "\n");
    define('DEBUG_SAPI', 0);
}
else
{
    define('LCRT', '<br />');
    define('DEBUG_SAPI', 1);
}

function debug($arry, $alias = '', $exit = false)
{
    $output = ($alias ? $alias .' =  ' : '') . print_r($arry, true);

    if (DEBUG_SAPI)
    {
        $output = '<pre>' . $output . '</pre>' . LCRT;
    }
    else
    {
        $output .= LCRT;
    }
    echo $output;

    if ($exit)
    {
        exit;
    }
}
