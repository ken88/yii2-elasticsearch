<?php


namespace common\models;

use Yii;

class SqlServer
{
    /**
     * @var \PDO
     */
    private static $link = null;
    private $config = [];
    private $isSqlSrv = false;
    private $addLastInsertId;

    public function __construct()
    {
        $this->_loadConfig();

        if(!self::$link){
            if (extension_loaded('pdo_sqlsrv')) {
                $this->isSqlSrv = true;
                $this->_pdo_connect();
            } else {
                $this->_mssql_connect();
            }
        }
    }


    public function getLink(){
        return self::$link;
    }

    public function select($sql, $isStoredProcedure = false){
        $data = [];
        $result = $this->query($sql);
        if($this->isSqlSrv && $isStoredProcedure){
            for ($i = 0; $i < 30; $i++){
                $result->nextRowset();
                if($result->columnCount()){
                    break;
                }
            }
        }

        //取得所有的表名
        while($row = $this->fetch($result)){
            $data[] = $row;
        }
        return $data;
    }

    private function _loadConfig()
    {
        $this->config = Yii::$app->params['pyDB'];
    }

    private function _mssql_connect()
    {
        self::$link = mssql_connect("{$this->config['DB_HOST']}:{$this->config['DB_PORT']}", $this->config['DB_USER'], $this->config['DB_PWD']);

        if (!self::$link || !mssql_select_db($this->config['DB_NAME'], self::$link)) {

            die('Error connecting to SQL Server');
        }
    }

    private function _pdo_connect()
    {
        try {
            self::$link = new \PDO("sqlsrv:Server={$this->config['DB_HOST']},{$this->config['DB_PORT']};Database={$this->config['DB_NAME']}", $this->config['DB_USER'], $this->config['DB_PWD']);
            self::$link->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8);
        } catch (\PDOException $e) {
            echo $e->getMessage();
            echo "<br>";
            die("Error connecting to SQL Server");
        }
    }

    public function getLastInsertId(){
        return $this->addLastInsertId;
    }

    public function insertOne($sql){
        if (extension_loaded('pdo_sqlsrv')) {
            $queryResource = self::$link->query($sql);

            if (!$queryResource) {
                echo '<pre>';
                print_r(self::$link->errorInfo());
                exit;
            } else {
                $this->addLastInsertId = self::$link->lastInsertId();
                return $queryResource;
            }
        } else {
            $sql = trim($sql,";") . ';select @@identity;';
            $result = mssql_query($sql, self::$link);
            $row = $this->fetch($result);
            $this->addLastInsertId = $row['computed'];
            return $result;
        }
    }

    public function query($sql)
    {
        if (extension_loaded('pdo_sqlsrv')) {
            $queryResource = self::$link->query($sql);

            if (!$queryResource) {
                echo '<pre>';
                print_r(self::$link->errorInfo());
                exit;
            } else {
                return $queryResource;
            }
        } else {
            $result = mssql_query($sql, self::$link);
            return $result;
        }
    }

    public function fetch($queryRes)
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return $queryRes->fetch(\PDO::FETCH_ASSOC);
        } else {
            return mssql_fetch_assoc($queryRes);
        }
    }

    private function beginTransaction()
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return self::$link->beginTransaction();
        } else {
            return $this->query("BEGIN TRANSACTION crSave");
        }
    }

    private function rollback()
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return self::$link->rollback();
        } else {
            return $this->query("ROLLBACK TRANSACTION crSave");
        }
    }

    private function commit()
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return self::$link->commit();
        } else {
            return $this->query("COMMIT TRANSACTION crSave");
        }
    }

    private function convert($string)
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return $string;
        } else {
            return mb_convert_encoding($string, 'GBK', 'UTF-8');
        }
    }

    private function insert($table, $data)
    {
        $sql = $this->implode($data);
        $cmd = 'INSERT INTO';
        return $this->query("$cmd $table $sql");
    }

    public function update($table, $data, $condition)
    {

        $sql = $this->updateImplode($data);
        if (empty($sql)) {
            return false;
        }
        $cmd = "UPDATE ";

        $res = $this->query("$cmd $table SET $sql WHERE $condition");
        return $res;
    }

    private function updateImplode($array, $glue = ',')
    {
        $fileds = [];
        foreach ($array as $k => $v) {

            if (is_null($v)) {
                $value = ' NULL';
            } elseif (is_int($v) || is_float($v)) {
                $value = $v;
            } elseif (is_string($v)) {
                if (substr($v, 0, 1) == '@') {
                    $value = $v;
                } else {
                    $value = "'{$v}'";
                }
            }
            $fileds[] = "{$k} = {$value}";
        }

        return join(',', $fileds);
    }
    private function implode($array, $glue = ',')
    {
        $filedsSql = $valuesSql = '';
        $fileds = $values = [];
        foreach ($array as $k => $v) {
            $fileds[] = $k;
            if (is_null($v)) {
                $value = ' NULL';
            } elseif (is_int($v) || is_float($v)) {
                $value = $v;
            } elseif (is_string($v)) {
                if (substr($v, 0, 1) == '@') {
                    $value = $v;
                } else {
                    $value = "'{$v}'";
                }

            }
            $values[] = $value;
        }
        $filedsSql = join(', ', $fileds);
        $valuesSql = join(', ', $values);

        return "({$filedsSql}) VALUES ({$valuesSql})";
    }

    //静态化配置信息
    private function free($result)
    {
        // Clean up
        mssql_free_result($result);
    }

    /**
     * 中文编码转换
     * @param unknown $string
     * @return string
     */
    public function convertToUtf8($string){
        if( !empty($string) ){
            $fileType = mb_detect_encoding($string , array('UTF-8','GBK','LATIN1','BIG5')) ;
            if( $fileType != 'UTF-8'){
                $string = mb_convert_encoding($string ,'utf-8' , $fileType);
            }
        }
        return $string;
    }

    public function chineseToConvert($string)
    {
        if (extension_loaded('pdo_sqlsrv')) {
            return $string;
        } else {
            return mb_convert_encoding($string, 'GBK', 'UTF-8');
        }
    }
}