<?php
/**
 * @describe: 抓取代理资源,基于 SQLite3 实现接口
 * @author: Jerry Yang(hy0kle@gmail.com)
 * */
include_once('lib/fetchProxyResource.php');

define('FWS_PROXY_STATUS_NEW',          0);
define('FWS_PROXY_STATUS_AVAILABLE',    1);
define('FWS_PROXY_STATUS_TEMPORARY',    2);
define('FWS_PROXY_STATUS_DEAD',         3);
/** 不可用次数,超过后将状态置为永远不可用 */
define('FWS_PROXY_UNAVAILABLE_COUNT',   10);

class fetchWithSqlite extends fetchProxyResource
{
    private $db = NULL;
    private $db_name  = 'data/proxy_resource.db';
    private $db_table = 'proxy_resource';

    public function __construct()
    {
        $this->db = new SQLite3($this->db_name);
        if (! $this->db)
        {
            die("Can NOT use db: {$this->db_name}");
        }

        /**
         * TODO 时间索引
         * */
        $sql  = "CREATE TABLE IF NOT EXISTS `{$this->db_table}`(";
        $sql .= '   `signature` TEXT NOT NULL,';
        $sql .= '   `ip` TEXT NOT NULL,';
        $sql .= '   `port` INTEGER NOT NULL,';
        $sql .= '   `create_time` INTEGER NOT NULL,';
        $sql .= '   `last_check_time` INTEGER NOT NULL,';
        $sql .= '   `status` INTEGER NOT NULL, ';
        $sql .= '   `unavailable_count` INTEGER NOT NULL, ';
        $sql .= '   `ext` TEXT NOT NULL, ';
        $sql .= '   PRIMARY KEY(`signature`)';
        $sql .= ');';
        //echo $sql . "\n";

        $res = $this->db->exec($sql);
        if (! $res)
        {
            die("create table {$this->db_table} is wrong. code: "
                . $db->lastErrorCode() . ' msg: ' . $db->lastErrorMsg());
        }
    }

    /** 实现接口 { */
    public function export($file_name)
    {
        echo __METHOD__ . "\n";
        /** format: {ip}\t{port}\t{proxy_type[默认为 HTTP]}\t{source}\n */
        $sql = sprintf("SELECT * FROM `{$this->db_table}` WHERE status = %d", FWS_PROXY_STATUS_AVAILABLE);
        //debug($sql, __METHOD__ . ' SQL');
        $ret_obj = $this->db->query($sql);
        while ($row = $ret_obj->fetchArray(SQLITE3_ASSOC))
        {
            $ext = json_decode($row['ext'], true);

            $text  = "{$row['ip']}\t{$row['port']}\t";

            $text .= (isset($ext['proxy_type']) ? strtoupper($ext['proxy_type']) : 'HTTP');
            $text .= "\t";

            $text .= (isset($ext['source']) ? $ext['source'] : '');
            $text .= "\n";

            file_put_contents($file_name, $text, FILE_APPEND);
        }

        return;
    }

    protected function write($ip, $port, $ext)
    {
        $signature = $this->createSignature($ip, $port);
        $ext = json_encode($ext);
        $port += 0;
        $time_now = time();

        /** check exist */
        $c_sql  = "SELECT COUNT(`signature`) AS total FROM `{$this->db_table}` ";
        $c_sql .= "WHERE `signature` = '{$signature}'";
        $res_obj = $this->db->query($c_sql);
        $row = $res_obj->fetchArray(SQLITE3_ASSOC);
        if (is_array($row) && isset($row['total']) && $row['total'] > 0)
        {
            /** use logger TODO */
            $log = "{$ip}:{$port} ext='{$ext}' exist already .";
            echo $log . "\n";

            return true;
        }

        $sql  = "INSERT INTO `{$this->db_table}` VALUES(";
        $sql .= "'{$signature}', ";
        $sql .= "'{$ip}', {$port}, ";
        $sql .= "{$time_now}, {$time_now}, ";
        $sql .= sprintf("%d, 0, '{$ext}'", FWS_PROXY_STATUS_NEW);
        $sql .= ');';

        $ret = $this->db->query($sql);
        if (! $ret)
        {
            echo "SQL: [{$sql}] exec has wrong.\n";
        }

        return true;
    }

    protected function getPrepareResource()
    {
        $prepare_resoure = array();
        echo __METHOD__ . "\n";
        /** 5 天内可用的不再检查 */
        $sql  = "SELECT * FROM `{$this->db_table}` ";
        $sql .= sprintf('WHERE `status` !=  %d ', FWS_PROXY_STATUS_AVAILABLE);
        $sql .= sprintf('OR (`status` =  %d AND `last_check_time` < %d)',
            FWS_PROXY_STATUS_AVAILABLE,
            time() - FPR_CHECK_TIME_INTERVAL);
        //echo $sql . "\n";

        $res_obj = $this->db->query($sql);
        while ($row = $res_obj->fetchArray(SQLITE3_ASSOC))
        {
            $prepare_resoure[] = $row;
        }

        //print_r($prepare_resoure);exit();
        return $prepare_resoure;
    }

    protected function updateResource($ip, $port, $check_info)
    {
        //debug(func_get_args(), __METHOD__);

        $signature = $this->createSignature($ip, $port);

        $proxy_info = $this->getProxyInfo($signature);
        //debug($proxy_info, 'proxy_info', 1);

        $unavailable_count = $proxy_info['unavailable_count'];
        $status = true === $check_info['status'] ? FWS_PROXY_STATUS_AVAILABLE : FWS_PROXY_STATUS_TEMPORARY;
        if ($unavailable_count + 1 > FWS_PROXY_UNAVAILABLE_COUNT)
        {
            $status = FWS_PROXY_STATUS_DEAD;
        }
        /** 历史不可用记数,偶尔可用不减记数 */
        if (true !== $check_info['status'])
        {
            $unavailable_count++;
        }

        $sql  = "UPDATE `{$this->db_table}` SET ";
        $sql .= sprintf('`last_check_time` = %d, ', $check_info['last_check_time']);
        $sql .= sprintf('`status` = %d, ', $status);
        $sql .= "unavailable_count = {$unavailable_count} ";
        $sql .= "WHERE `signature` = '{$signature}'";
        //debug($sql, 'update sql', 1);
        $this->db->query($sql);

        /** TODO use logger */
        echo "UPDATE for [{$ip}:{$port}] status: {$status}\n";

        return;
    }
    /** 接口实现完成 } */

    private function createSignature($ip, $port)
    {
        return md5("{$ip}:{$port}");
    }

    private function getProxyInfo($signature)
    {
        $info = array();

        $sql = "SELECT * FROM `{$this->db_table}` WHERE signature = '{$signature}'";
        //debug($sql, __METHOD__);
        $ret_obj = $this->db->query($sql);
        $row = $ret_obj->fetchArray(SQLITE3_ASSOC);
        //debug($row, 'fetchArray');
        if (is_array($row) && count($row) > 0)
        {
            $info = $row;
        }

        return $info;
    }
}

// test case
//$fs = new fetchWithSqlite();
//$fs->fetch();
//$fs->check();
//$fs->export('/tmp/p.txt');

/**
$config = array(
    'proxy' => true,
    'proxy_host' => '127.0.0.1',
    'proxy_port' => 8087,
);
$ct = new curlTools($config);
$html = $ct->get('http://www.baidu.com/');
debug($html, 'html');
$http_info = $ct->getInfo();
debug($http_info, 'http_info');
*/

/**
$ch = curl_init('http://www.baidu.com/');
//curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1');
curl_setopt($ch, CURLOPT_PROXY, '177.69.195.4');
//curl_setopt($ch, CURLOPT_PROXYPORT, 8087);
curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 6);
$result = curl_exec ($ch);
curl_close($ch);
debug($result,  'just test');
*/
