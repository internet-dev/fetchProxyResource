<?php
/**
 * @describe:
 * @author: Jerry Yang(hy0kle@gmail.com)
 * */
include_once('lib/fetchProxyResource.php');

define('FWS_PROXY_STATUS_NEW',          0);
define('FWS_PROXY_STATUS_AVAILABLE',    1);
define('FWS_PROXY_STATUS_TEMPORARY',    2);
define('FWS_PROXY_STATUS_DEAD',         3);

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

        /** 没有创建时间索引 */
        $sql  = "CREATE TABLE IF NOT EXISTS `{$this->db_table}`(";
        $sql .= '   `signature` TEXT NOT NULL,';
        $sql .= '   `ip` TEXT NOT NULL,';
        $sql .= '   `port` INTEGER NOT NULL,';
        $sql .= '   `create_time` INTEGER NOT NULL,';
        $sql .= '   `last_check_time` INTEGER NOT NULL,';
        $sql .= '   `status` INTEGER NOT NULL,';
        $sql .= '   `ext` TEXT NOT NULL,';
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

    protected function write($ip, $port, $ext)
    {
        $signature = md5("{$ip}:{$port}");
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
        $sql .= sprintf("%d, '{$ext}'", FWS_PROXY_STATUS_NEW);
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

        //print_r($prepare_resoure);
        return $prepare_resoure;
    }

    protected function updateResource($ip, $port, $check_info)
    {
        echo __METHOD__ . "\n";
    }

    public function export($file_name)
    {
        echo __METHOD__ . "\n";
    }
}

// test case
$fs = new fetchWithSqlite();
//$fs->fetch();
$fs->check();
//$fs->check('127.0.1', '90');
//$fs->export('/tmp');
