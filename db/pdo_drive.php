<?php

/**
 * 简单的数据库连接类.
 *
 * Class Db.
 */
class Db
{
    /**
     * @var string $dsn 数据库dsn驱动.
     */
    public $dsn;

    /**
     * @var string $db_user 数据库用户名.
     */
    public $db_user;

    /**
     * @var string $db_pass 数据库用户密码.
     */
    public $db_pass;

    /**
     * @var
     */
    public $sth;

    /**
     * @var
     */
    public $dbh;

    /**
     * @var string 日志文件的目录
     */
    public $file_path;

    /**
     * @var int 单个日志文件大小(MB)
     */
    public $file_size = 200;

    /**
     * Db constructor.
     *
     * @param array $_db_params 实例化参数.
     */
    public function __construct($_db_params)
    {
        // 初始化日志记录位置
        $this->file_path = dirname(__FILE__) . '/log/';
        if (isset($_db_params['fileSize'])) {
            $fileSize = (int) $_db_params['fileSize'];
            if ($fileSize > 0) {
                $this->file_size = $fileSize;
            }
        }

        // 初始化数据库连接信息
        $this->dsn = $_db_params['dsn'];
        $this->db_user = $_db_params['user'];
        $this->db_pass = $_db_params['pass'];
        $this->connect();
        $this->dbh->query('SET NAMES ' . $_db_params['char']);
    }

    /**
     * 数据库连接.
     */
    private function connect()
    {
        try {
            $this->dbh = new PDO($this->dsn, $this->db_user, $this->db_pass);
        } catch (PDOException $e) {
            exit('Error:' . $e->getMessage());
        }
    }

    /**
     * 获取最后一条插入ID.
     *
     * @return mixed
     */
    public function getLastID()
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * 获取PDO错误信息.
     */
    private function getPDOError()
    {
        if ($this->dbh->errorCode() != '00000') {
            $error = $this->dbh->errorInfo();
            exit($error[2]);
        }
    }

    /**
     * 查询sql语句.
     *
     * @param string $sql   sql语句.
     * @param string $model 查找多条还是一条.
     *
     * @return mixed
     */
    public function query($sql, $model = 'many')
    {
        $this->sth = $this->dbh->query($sql);
        $this->log($sql);
        $this->getPDOError();
        $this->sth->setFetchMode(PDO::FETCH_ASSOC);
        if ($model == 'many') {
            $result = $this->sth->fetchAll();
        } else {
            $result = $this->sth->fetch();
        }
        $this->sth = null;
        return $result;
    }

    /**
     * 执行sql语句.
     *
     * @param string $sql sql语句.
     *
     * @return mixed
     */
    public function exec($sql)
    {
        $this->log($sql);
        $rtn = $this->dbh->exec($sql);
        $this->getPDOError();
        return $rtn;
    }

    /**
     * 检查表是否存在，若存在，则返回表名；不存在，则返回false
     * @param string $table 表名.
     *
     * @return array|bool
     */
    public function checkTableExsist($table)
    {
        $query = "show tables like '%" . $table . "%'";
        $result = $this->query($query);
        if (is_array($result) && count($result) > 0) {
            return array_map(array($this, 'filterArrayKey'), $result);
        }
        return false;
    }


    public function filterArrayKey($array)
    {
        $val = each($array);
        return $val['value'];
    }

    /**
     * 析构函数.
     */
    public function __destruct()
    {
        $this->dbh = null;
    }

    /**
     * @param string $sql 记录log.
     */
    public function log($sql)
    {
        list($usec, $sec) = explode(' ', microtime());
        $sql = preg_replace("/[\s]+/is", " ", $sql); // 取字符串中的换行和空格、tab
        $msg = date('Y-m-d H:i:s', $sec) . ' [+' . sprintf('%04d', round($usec * 10000)) . '] ' . $sql . PHP_EOL;
        $logDir =  $this->file_path .date('Y-m') .'/';
        if (!is_dir($logDir)) {
            if (!mkdir($logDir, 0777, true)) {
                die("can't create path {$logDir} ");
            }
        }
        // 搜索日志文件，计算个数
        $files = glob($logDir . 'sql-' . date('Y-m-d') . '*.log');
        $defaultLogFile = $logDir.'sql-' . date('Y-m-d') . '.log';
        if (!empty($files)) {
            $fileNum  = count($files);
            if ($fileNum > 1) {
                $currentLogFile = $logDir . 'sql-' . date('Y-m-d') . '-' . ($fileNum-1) . '.log';
            } else {
                $currentLogFile = $logDir.'sql-' . date('Y-m-d') . '.log';
            }
        } else {
            $currentLogFile = $defaultLogFile;
        }

        if (file_exists($currentLogFile)) {
            $fileSize = filesize($currentLogFile) / 1024 /1024; // mb
            if ($fileSize >= $this->file_size) {
                $currentLogFile = $logDir . 'sql-' . date('Y-m-d') . '-' . $fileNum . '.log';
            }
        }
        // 日志写入
        error_log($msg, 3, $currentLogFile);
    }
}

/*
$pdo = new db( array(
            'dsn' => "mysql:host=127.0.0.1;port=3306;dbname=dap;",
            'user' => "root",
            'pass' => "123456",
            'char' => "utf8")
);
$pdo->query($sql);
*/

/*
sql 日志文件位于log目录下，按月份建立目录，按天保存，若记录日志时，文件大小大于200Mb，则新建当天日志文件；
例如：有日志文件 sql-2016-04-11.log, 当该文件大于200Mb(可配置)时, 则日志记录到sql-2016-04-11-1.log
文件中，-x有序递增
*/
?>