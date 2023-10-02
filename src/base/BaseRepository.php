<?php

namespace app\base;

use PDO;
use app\database\DatabaseConnection;

// Using PHP Data Objects Extension


abstract class BaseRepository
{
  protected static $instance;
  // Holds instance of PDO connection
  protected $pdo;
  // Database table
  protected $tableName = '';

  protected function __construct()
  {
    $this->pdo = DatabaseConnection::getInstance()->getPDO();
  }

  public static function getInstance()
  {
    if (!isset(self::$instance)) {
      self::$instance = new static();
    }
    return self::$instance;
  }

  public function getPDO()
  {
    return $this->pdo;
  }

  public function getAll()
  {
    $sql = "SELECT * FROM $this->tableName";
    return $this->pdo->query($sql);
  }

  // Where (key: column name, value(0: value, 1: data type, 2: type of comparison either LIKE or =)) 

  public function countRow($where = [])
  {
    $sql = "SELECT COUNT(*) FROM $this->tableName";

    if (count($where) > 0) {
      // WHERE Query Building
      $sql .= " WHERE ";
      // Append Conditions
      $sql .= implode(" AND ", array_map(function ($key, $value) {
        if ($value[2] == 'LIKE') {
          return "$key LIKE :$key";
        }

        return "$key = :$key";
      }, array_keys($where), array_values($where)));
    }
    // Hydrating statement, for sanitizing
    $stmt = $this->pdo->prepare($sql);
    // Bind values
    foreach ($where as $key => $value) {
      // Binds parameter with appropriate data type 
      if ($value[2] == 'LIKE') {
        $stmt->bindValue(":$key", "%$value[0]%", $value[1]);
      } else {
        $stmt->bindValue(":$key", $value[0], $value[1]);
      }
    }

    $stmt->execute();
    return $stmt->fetchColumn();
  }

  public function findAll(
    $where = [],
    $order = null,
    $pageNo = null,
    $pageSize = null,
    $isDesc = false,
  ) {
    $sql = "SELECT * FROM $this->tableName";

    // Mapping where
    if (count($where) > 0) {
      $sql .= " WHERE ";
      $sql .= implode(" AND ", array_map(function ($key, $value) {
        if ($value[2] == 'LIKE') {
          return "$key LIKE :$key";
        }

        return "$key = :$key";
      }, array_keys($where), array_values($where)));
    }

    if ($order) {
      $sql .= " ORDER BY $order";
    }

    if ($isDesc) {
      $sql .= " DESC";
    } else {
      $sql .= " ASC";
    }

    if ($pageSize && $pageNo) {
      $sql .= " LIMIT :pageSize";
      $sql .= " OFFSET :pageNo";
    }

    // Hydrating statement, for sanitizing
    $stmt = $this->pdo->prepare($sql);

    foreach ($where as $key => $value) {
      if ($value[2] == 'LIKE') {
        $stmt->bindValue(":$key", "%$value[0]%", $value[1]);
      } else {
        $stmt->bindValue(":$key", $value[0], $value[1]);
      }
    }

    if ($pageSize && $pageNo) {
      $offset = $pageSize * ($pageNo - 1);

      $stmt->bindValue(":pageSize", $pageSize, PDO::PARAM_INT);
      $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    }

    $stmt->execute();

    return $stmt->fetchAll();
  }

  public function findOne($where)
  {
    $sql = "SELECT * FROM $this->tableName";

    if (count($where) > 0) {
      $sql .= " WHERE";
      $sql .= implode(" AND ", array_map(function ($key, $value) {
        if ($value[2] == 'LIKE') {
          return "$key LIKE :$key";
        }

        return "$key = :$key";
      }, array_keys($where), array_values($where)));
    }

    // Hydrating statement, for sanitizing
    $stmt = $this->pdo->prepare($sql);

    foreach ($where as $key => $value) {
      if ($value[2] == 'LIKE') {
        $stmt->bindValue(":$key", "%$value[0]%", $value[1]);
      } else {
        $stmt->bindValue(":$key", $value[0], $value[1]);
      }
    }

    $stmt->execute();

    return $stmt->fetch();
  }

  public function insert($model, $arrParams)
  {
    $sql = "INSERT INTO $this->tableName (";
    $sql .= implode(", ", array_keys($arrParams));
    $sql .= ") VALUES (";
    $sql .= implode(", ", array_map(function ($key, $value) {
      return ":$key";
    }, array_keys($arrParams), array_values($arrParams)));
    $sql .= ")";

    $stmt = $this->pdo->prepare($sql);
    // Hydrating and sanitizing
    foreach ($arrParams as $key => $value) {
      $stmt->bindValue(":$key", $model->get($key), $value);
    }

    $stmt->execute();
    return $this->pdo->lastInsertId();
  }

  public function update($model, $arrParams)
  {
    $sql = "UPDATE $this->tableName SET ";
    $sql .= implode(", ", array_map(function ($key, $value) {
      return "$key = :$key";
    }, array_keys($arrParams), array_values($arrParams)));
    $sql .= ")";
    $primaryKey = $model->get('_primary_key');
    $sql .= " WHERE $primaryKey = :primaryKey";

    $stmt = $this->pdo->prepare($sql);
    // Hydrating and sanitizing
    foreach ($arrParams as $key => $value) {
      $stmt->bindValue(":$key", $model->get($key), $value);
    }

    $stmt->bindValue(":primaryKey", $model->get($primaryKey), PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->rowCount();
  }

  public function delete($model)
  {
    $sql = "DELETE FROM $this->tableName WHERE ";
    $primaryKey = $model->get('_primary_key');
    $sql .= "$primaryKey = :primaryKey";

    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(":primaryKey", $model->get('$primaryKey'), PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->rowCount();
  }

  public function getNLastRow($N)
  {
    $sql = "SELECT COUNT(*) FROM $this->tableName";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count < $N) {
      $N = $count;
    }

    $offset = $count - $N;
    $sql = "SELECT * FROM $this->tableName LIMIT :limit OFFSET :offset";
    $stmt = $this->pdo->prepare($sql);
    $stmt->bindValue(":limit", $N, PDO::PARAM_INT);
    $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }
}