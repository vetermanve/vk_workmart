<?php

lets_sure_loaded('storage_db');

lets_use('core_config');

global $_storage_db_started_transactions;

$_storage_db_started_transactions = [];

/**
 * @param $dbPart
 *
 * @return mysqli|bool
 */
function _storage_db_get_connection($dbPart)
{
    static $config;
    static $connections;
    
    if (isset($connections[$dbPart])) {
        return $connections[$dbPart];
    }
    
    if (!isset($config)) {
        $config = core_config_get('db', []);
    }
    
    if (!isset($config[$dbPart])) {
        core_dump($config);
        trigger_error($dbPart . ' db connection config not found');
        
        return false;
    }
    
    $partConfig = $config[$dbPart];
    
    $mysqli = mysqli_connect($partConfig['host'], $partConfig['user'], $partConfig['pass'],
        (isset($partConfig['db_name']) ? $partConfig['db_name'] : $dbPart));
    
    if (!$mysqli) {
        trigger_error('Cannot establish connection to db ' . $dbPart . ' with given config. Error: ' . mysqli_connect_error() . '; code: ' . mysqli_connect_errno(),
            E_USER_ERROR);
        
        return false;
    }
    
    mysqli_set_charset($mysqli, "utf8");
    
    $connections[$dbPart] = $mysqli;
    
    return $connections[$dbPart];
}

/**
 * @param mysqli $connection
 * @param string $table
 *
 * @param string $query
 *
 * @return bool
 */
function _storage_db_check($connection, $table, $query = '')
{
    core_log($query, __FUNCTION__);
    if ($connection->error) {
        trigger_error($connection->error . ' in table: ' . $table.' on query: '.$query);
        
        return true;
    }
    
    return false;
}

function storage_db_get_rows($table, $cols, $where, $cond = [], $fetchAssocPrimaryKey = null)
{
    $part       = _storage_db_get_part($table);
    $connection = _storage_db_get_connection($part);
    
    $cols = (array)$cols;
    
    if (!$connection) {
        trigger_error('Lost connection from db', E_USER_WARNING);
        
        return [];
    }
    
    $whereString = $where ? _storage_db_build_where($connection, $where) : '';
    
    $queryString = 'SELECT ' . implode(', ',
            $cols) . ' FROM ' . $table . ' ' . ($whereString !== '' ? 'WHERE ' . $whereString : '');
    
    if (isset($cond['ORDER BY'])) {
        $queryString .= ' ORDER BY ' . $cond['ORDER BY'];
    }
    
    if (isset($cond['LIMIT'])) {
        $queryString .= ' LIMIT ' . (int)$cond['LIMIT'];
    }
    
    $result = mysqli_query($connection, $queryString);
    
    if (_storage_db_check($connection, $table, $queryString)) {
        return [];
    }
    
    $data = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        if ($fetchAssocPrimaryKey && isset($row[$fetchAssocPrimaryKey])) {
            $data[$row[$fetchAssocPrimaryKey]] = $row;    
        } else {
            $data[] = $row;
        }
    }
    
    return $data;
}

function storage_db_get_row($table, $cols, $where, $cond = [])
{
    $result = storage_db_get_rows($table, $cols, $where, [
        'LIMIT' => 1,
    ]);
    
    return $result ? $result[0] : $result;
}

function storage_db_get_value($table, $col, $where, $cond = [])
{
    $result = storage_db_get_rows($table, $col, $where, [
        'LIMIT' => 1,
    ]);
    
    return $result ? $result[0][$col] : $result;
}

function storage_db_get_last_error($table)
{
    $connection = _storage_db_get_connection(_storage_db_get_part($table));
    
    return $connection->error ? $connection->error . ' #' . $connection->errno : null;
}


function storage_db_insert_row($table, $bind, $ignore = false)
{
    $connection = _storage_db_get_connection(_storage_db_get_part($table));
    
    if (!$connection) {
        trigger_error('Lost connection from db', E_USER_WARNING);
        
        return false;
    }
    
    list ($colsNames, $values) = _storage_db_prepare_insert_row($connection, $bind);
    
    $queryString = 'INSERT ' . ($ignore ? 'IGNORE' : '') . ' INTO ' . $table . ' (' . implode(', ',
            $colsNames) . ') ' . ' VALUES (' . implode(',', $values) . ')';
    
    $res = mysqli_query($connection, $queryString);
    
    if (_storage_db_check($connection, $table, $queryString)) {
        return false;
    }
    
    $lastInsertId = mysqli_insert_id($connection);
    
    return $lastInsertId !== 0 ? $lastInsertId : $res->num_rows;
}

function storage_db_set($table, $bind)
{
    $connection = _storage_db_get_connection(_storage_db_get_part($table));
    
    if (!$connection) {
        trigger_error('Lost connection from db', E_USER_WARNING);
        
        return false;
    }
    
    list ($colsNames, $values) = _storage_db_prepare_insert_row($connection, $bind);
    
    $duplicateString = [];
    
    foreach ($colsNames as $colName) {
        $duplicateString[] = $colName . '=VALUES(' . $colName . ')';
    }
    
    $queryString = 'INSERT INTO ' . $table . ' (' . implode(', ', $colsNames) . ') ' . 'VALUES (' . implode(',',
            $values) . ') ' . 'ON DUPLICATE KEY UPDATE ' . implode(', ', $duplicateString);
    
    $res = mysqli_query($connection, $queryString);
    
    if (_storage_db_check($connection, $table, $queryString)) {
        return false;
    }
    
    $lastInsertId = mysqli_insert_id($connection);
    
    return $lastInsertId !== 0 ? $lastInsertId : $res;
}

function storage_db_transaction_begin($table)
{
    global $_storage_db_started_transactions;
    
    $part = _storage_db_get_part($table);
    
    // transaction already started
    if (isset($_storage_db_started_transactions[$part])) {
        $_storage_db_started_transactions[$part][$table] = $table; 
        return true;
    }
    
    $part       = _storage_db_get_part($table);
    $connection = _storage_db_get_connection($part);
    
    $res = mysqli_begin_transaction($connection);
    
    if (_storage_db_check($connection, $table, 'TRANSACTION BEGIN:'.$part)) {
        return false;
    }
    
    if (!$res) {
        trigger_error('cant start transaction on part: ' . $part . ' for table ' . $table);
        return false;
    }
    
    $_storage_db_started_transactions[$part][$table] = $table;
    
    lets_use('core_shutdown');
    core_shutdown_add_check('db_transactions_end_check', 'storage_db_transactions_end_check', false);
    
    return $res;
}

function storage_db_transaction_commit($tables)
{
    $tables = (array)$tables;
    
    global $_storage_db_started_transactions;
    $tablesByParts = [];
    
    foreach ($tables as $table) {
        $tablesByParts[_storage_db_get_part($table)][$table] = $table;
    }
    
    foreach ($tablesByParts as $part => $tables) {
        if (array_diff_key($_storage_db_started_transactions[$part], $tables)) {
            core_error('tring to commit transaction with incorrect tables passed'. json_encode([$part, $tables, $_storage_db_started_transactions[$part]]));
            return false;
        }
        
        $connection = _storage_db_get_connection($part);
    
        $commitRes = mysqli_commit($connection);
        
        _storage_db_check($connection, json_encode(array_values($tables)), 'TRANSACTION COMMIT:'.$part);
        
        if (!$commitRes) {
            mysqli_rollback($connection);
            
            _storage_db_check($connection, json_encode(array_values($tables)), 'TRANSACTION ROLLBACK:'.$part);
            
            core_error('fail commit transaction on part: ' . $part);
            return false;
        }
    }
}

function storage_db_transaction_rollback($tables)
{
    $tables = (array)$tables;
    
    global $_storage_db_started_transactions;
    $tablesByParts = [];
    
    foreach ($tables as $table) {
        $tablesByParts[_storage_db_get_part($table)][$table] = $table;
    }
    
    foreach ($tablesByParts as $part => $tables) {
        if (array_diff_key($_storage_db_started_transactions[$part], $tables)) {
            core_error('tring to rollback transaction with incorrect tables passed'. json_encode([$part, $tables, $_storage_db_started_transactions[$part]]));
            return false;
        }
        
        $connection = _storage_db_get_connection($part);
    
        mysqli_rollback($connection);
        
        _storage_db_check($connection, json_encode(array_values($tables)), 'TRANSACTION ROLLBACK:'.$part);
    }
}

function storage_db_transactions_commit_all()
{
    global $_storage_db_started_transactions;
    
    if (!$_storage_db_started_transactions) {
        core_log(__FUNCTION__ . ' called but no started transactions');
        
        return true;
    }
    
    foreach ($_storage_db_started_transactions as $part => $connection) {
        if (!mysqli_commit($connection)) {
            core_error('fail commit transaction on part: ' . $part);
            
            return false;
        }
    }
    
    return true;
}

function storage_db_transactions_rollback_all()
{
    global $_storage_db_started_transactions;
    
    if (!$_storage_db_started_transactions) {
        core_log(__FUNCTION__ . ' called but no started transactions');
        
        return true;
    }
    
    $allResult = true;
    
    foreach ($_storage_db_started_transactions as $part => $connection) {
        $res = mysqli_rollback($connection);
        if (!$res) {
            core_error('fail rollback transaction on part: ' . $part);
        }
        $allResult = $allResult && $res;
    }
    
    return $allResult;
}

function storage_db_transactions_end_check()
{
    global $_storage_db_started_transactions;
    if (!empty($_storage_db_started_transactions)) {
        core_error('not ended transactions found on shutdown');
    }
}

function _storage_db_prepare_insert_row($connection, $insertBind)
{
    $values = $colsNames = [];
    
    foreach ($insertBind as $colName => $value) {
        $colsNames[] = $colName;
        
        if (is_null($value)) {
            $values[] = 'NULL';
        }
        else {
            if (is_numeric($value)) {
                $values[] = $value;
            }
            else {
                $values[] = '"' . mysqli_real_escape_string($connection, $value) . '"';
            }
        }
    }
    
    return [$colsNames, $values];
}

function _storage_db_build_where($connection, $where)
{
    if (!isset($where[0])) {
        core_error('incorrect where bind: ' . json_encode($where), __FUNCTION__);
        
        return '0';
    }
    
    if (!is_array($where[0])) {
        $where = [$where];
    }
    
    $whereString = '';
    foreach ($where as $whereParam) {
        if (count($whereParam) == 2) {
            list ($field, $value) = $whereParam;
            $operation = '=';
        }
        else {
            list ($field, $operation, $value) = $whereParam;
        }
        
        $field = mysqli_real_escape_string($connection, $field);
        
        $glue = ($whereString ? ' AND ' : '');
        
        if (is_array($value)) {
            foreach ($value as &$val) {
                $val = mysqli_real_escape_string($connection, $val);
                if (!is_numeric($val)) {
                    $val = '"' . $val . '"';
                }
            }
            
            $whereString .= $glue . '(' . $field . ' in (' . implode(',', $value) . ')' . ')';
        }
        else {
            $val = mysqli_real_escape_string($connection, $value);
            
            if (!is_numeric($val)) {
                $val = '"' . $val . '"';
            }
            
            $whereString .= $glue . '(' . $field . ' ' . $operation . ' ' . $val . ')';
        }
    }
    
    return trim($whereString);
}

function _storage_db_get_part($table)
{
    static $cache;
    
    if (!$cache) {
        $tablesConfig = core_config_get('db_tables', []);
        
        foreach ($tablesConfig as $dbPart => $partTables) {
            foreach ($partTables as $partTable) {
                $cache[$partTable] = $dbPart;
            }
        }
    }
    
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    
    if (isset($cache['*'])) {
        return $cache['*'];
    }
    
    trigger_error('Db partition fot table: "' . $table . '"' . ' not found', E_USER_WARNING);
}