<?php
class DB {

  /////////////////////////////////////////////////////////////////////////////////////////////////


  // DB connection property - The best way is to take it from Config File
  private $dbHost = 'localhost', //Your HOST
          $dbName = 'testdb', //Your Db Name
          $dbUser = 'root', // Your Db User
          $dbPassword = ''; // Your Db Password

  // This function will be execute where there is a problem with the sql that send to the DataBase
  private function errorMode($ErrorException){
    if ($debug = true)
      echo $ErrorException->getMessage() . '<br>';
    else
      die();
  }

  // This function will be execute where the data base connection failed
  private function dbConntionFailed(){
    die('DB Connection Failed');
  }



  /////////////////////////////////////////////////////////////////////////////////////////////////////


  private static $_instance = null;
  private $_pdo,
          $_query,
          $_results,
          $_count = 0,
          $_error = false;



  private function __construct(){
    try {

      $dsn = 'mysql:host='. $this->dbHost .';dbname='. $this->dbName .';charset=utf8';
      $this->_pdo = new PDO($dsn, $this->dbUser, $this->dbPassword);

      $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $this->_pdo->exec("SET NAMES 'utf8'");
      $this->_pdo->exec("SET sql_mode = ''");

    } catch (PDOException $e){

      $this->dbConntionFailed();

    }
  }

  public static function getInstance(){
    if(!isset(self::$_instance))
      self::$_instance = new DB();
    return self::$_instance;
  }











  // ------- Querying Methods! ---------------------------------------- //
  // ------------------------------------------------------------------ //


  public function query( $sql, $params = [], $toFetch = true ){
    $this->_error = false;

    try {

      $this->_query = $this->_pdo->prepare($sql);

      if(count($params)){
        $x = 1;
        foreach($params as $param){
          $this->_query->bindValue($x, $param);
          $x++;
        }
      }

      $this->_query->execute();

      if( $toFetch ){
        $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
        $this->_count = $this->_query->rowCount();
      }

    } catch (PDOException $e) {

      $this->_error = true;
      $this->errorMode($e);

    }

    return $this;

  }




  public function preQuery($action, $from, $where = null, $orderBy = null, $toFetch = true){
    $params = [];
    $sql = $action .' FROM `'. $from .'`';

    // Where
    if(isset($where)){

      $allowedOperators = ['=', '>=', '<=', '>', '<'];
      if(!is_array($where[0])){
        $field = $where[0];
        $operator = $where[1];
        $value = $where[2];

        if(in_array($operator, $allowedOperators)){
          $sql .= ' WHERE `'. $field .'` '. $operator .' ? ';
          $params[] = $value;
        }

      } else {
        $sql .= ' WHERE ';
        $x = 1;
        foreach($where as $oneWhere){
          $field = $oneWhere[0];
          $operator = $oneWhere[1];
          $value = $oneWhere[2];

          if(in_array($operator, $allowedOperators)){
            if($x > 1){
              $sql .= 'AND ';
            }
            $sql .= '`'. $field .'` '. $operator .' ? ';
            $params[] = $value;

          }

          $x++;

        }
      }
    }

    //Order By
    if(isset($orderBy)){
      $sql .= ' ORDER BY `'. $orderBy .'`';
    }

    //Send To The Query Method
    if(!$this->query($sql, $params, $toFetch)->error())
      return $this;

    // If something go wrong return false
    return false;

  }










  // ------- CURD! ---------------------------------------------------- //
  // ------------------------------------------------------------------ //




  // ------- Select ------- //
  public function select($select, $from, $where = null, $orderBy = null){
    $selectSql = 'SELECT ';
    if(is_array($select)){
      $x = 1;
      foreach($select as $field){

        $selectSql .= '`'. $field .'`';
        if($x < count($select))
          $selectSql .= ', ';

        $x++;

      }
    } else {
      if( $select = '*' ){
        $selectSql .= $select .' ';
      } else {
        $selectSql .= '`'. $select .'` ';
      }
    }

    return $this->preQuery($selectSql, $from, $where, $orderBy);
  }





  // ------- Delete ------- //
  public function delete($from, $where){
    if($this->preQuery('DELETE ', $from, $where, null, false))
        return true;

    return false;
  }





  // ------- Insert ------- //
  public function insert($into, $fields){
    $keys = array_keys($fields);
    $values = '';

    $x = 1;
    foreach($fields as $field){
      $values .= '?';
      if($x < count($fields)) $values .= ', ';
      $x++;
    }

    $sql = 'INSERT into `'. $into .'` (`'. implode('`, `', $keys) .'`) VALUES('. $values .')';
    if(!$this->query($sql, $fields)->error())
      return true;

    return false;

  }





  // ------- Update ------- //
  public function update($table, $fields, $where){
    $set = '';
    $params = $fields;

    // Set
    $x = 1;
    foreach($fields as $field => $value){
      $set .= $field .' = ?';
      if($x < count($fields))
        $set .= ', ';
      $x++;
    }

    // Where
    if(count($where) === 3){
      $allowedOperators = ['=', '>=', '<=', '>', '<'];

      $field = $where[0];
      $operator = $where[1];
      $value = $where[2];

      if(in_array($operator, $allowedOperators)){
        $whereSql = $field .' '. $operator .' ?';
        $params[] = $value;
      }

    }

    $sql = 'UPDATE '. $table .' SET '. $set .' WHERE '. $whereSql;
    if(!$this->query($sql, $params)->error())
      return true;

    return false;
  }












  // ------- Helpers! ------------------------------------------------- //
  // ------------------------------------------------------------------ //

  public function error(){
    return $this->_error;
  }

  public function results(){
    return $this->_results;
  }

  public function firstResult(){
    return $this->results()[0];
  }

}
