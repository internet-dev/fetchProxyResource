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
        $this->_fetchCnproxy();
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

            for ($i = 2; $i < count($trs); $i++)
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

            // debug
            break;
        }
    }

    /**
     * @brief check 检测代理可用性接口,被动调用
     *
     * @return: viod
     */
    public function check()
    {
        echo __METHOD__ . "\n";
        $this->getPrepareResource();
        $this->updateResource($ip, $port, $check_info);
    }


    /**
     * @brief ping 专 ping 百度,查看返回的状态码
     *
     * @param: $ip
     * @param: $port
     *
     * @return: bool true: 代理可以ping百度; false: 尝了三次仍然失败
     */
    private function _ping($ip, $port)
    {
        $http_conf = array(
            'proxy'      => true,
            'proxy_host' => $ip,
            'proxy_port' => $port,
        );

        $retry = FPR_FETCH_RETRY_TIMES;
        while ($retry > 0)
        {
            $curl_tool = new curlTools($http_conf);
            $ping_url  = 'http://www.baidu.com/';
            $html = $curl_tool->get($ping_url);

            $http_info = $curl_tool->getInfo();

            if (is_array($http_info) && isset($http_conf['after'])
                && isset($http_conf['after']['http_code'])
                && FPR_FETCH_HTTP_OK == $http_conf['after']['http_code'])
            {
                return true;
            }

            sleep(FPR_FETCH_SLEEP_TIME * 1.2 + $retry);
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
     * @return: bool
     */
    abstract protected function updateResource($ip, $port, $check_info);

     /**
     * @brief export 将可用的代理资源导出为文本
     *
     * @param: $file_name
     *
     * @return: bool
     */
    abstract public function export($file_name);
}
