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
     * @brief check 检测代理可用性接口,被动调用
     *
     * @param: $ip
     * @param: $port
     *
     * @return: bool
     */
    abstract public function check($ip, $port);

    /**
     * @brief export 将可用的代理资源导出为文本
     *
     * @param: $file_name
     *
     * @return: bool
     */
    abstract public function export($file_name);
}
