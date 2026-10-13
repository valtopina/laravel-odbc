<?php
/**
 * Created by PhpStorm.
 * Date: 23/02/2018
 * Time: 17:51
 */

namespace Abram\Odbc;

use PDOStatement;

class ODBCPdoStatement extends PDOStatement
{
    protected $query;
    protected $params = [];
    protected $statement;

    public function __construct($conn, $query)
    {
        $this->query = preg_replace('/(?<=\s|^):[^\s:]++/um', '?', $query);

        $this->params = $this->getParamsFromQuery($query);

        $this->statement = odbc_prepare($conn, $this->query);
    }

    protected function getParamsFromQuery($qry)
    {
        $params = [];
        $qryArray = explode(" ", $qry);
        $i = 0;

        while (isset($qryArray[$i])) {
            if (preg_match("/^:/", $qryArray[$i]))
                $params[$qryArray[$i]] = null;
            $i++;
        }

        return $params;
    }

    public function rowCount()
    {
        return odbc_num_rows($this->statement);
    }

    public function bindValue($param, $val, $ignore = null)
    {
        $this->params[$param] = $val;
    }

    public function execute($ignore = null)
    {
        odbc_execute($this->statement, $this->params);
        $this->params = [];
    }

//    public function fetchAll($how = NULL, $class_name = NULL, $ctor_args = NULL)
//    {
//        $records = [];
//        while ($record = $this->fetch()) {
//            $records[] = $record;
//        }
//        return $records;
//    }

    public function fetchAll(int $mode = \PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        $records = [];

        //Ignoro $mode e $args, perche' odbc_fetch_array() restituisce sempre array associativi...
        while ($record = $this->fetch()) {
            $records[] = $record;
        }

        return $records;
    }

    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        //Evito di chiamare il metodo nativo di PDOStatement che esplode perche' il PDO interno non e' inizializzato.
        //Essendo che si utilizzano array associativi tramite odbc_fetch_array(), possiamo ignorare il "mode".

        return true;
    }

//    public function fetch($option = null, $ignore = null, $ignore2 = null)
//    {
//        return odbc_fetch_array($this->statement);
//    }

    public function fetch($option = null, $ignore = null, $ignore2 = null)
    {
        $row = odbc_fetch_array($this->statement);

        if ($row === false) {
            return false; //zero risultati
        }

        //Cast ad oggetto cosi' da mantenere la struttura in stile PDO
        return (object) $row;
    }
}
