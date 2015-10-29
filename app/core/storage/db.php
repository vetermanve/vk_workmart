<?php

lets_sure_loaded('core_storage_db');

lets_use('core_config');

/**
 * @param $dbPart
 *
 * @return mysqli|bool
 */
function _core_storage_db_get_connection ($dbPart) {
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
        trigger_error($dbPart.' db connection config not found');
        return false;
    }
    
    $partConfig = $config[$dbPart];
    
    $mysqli = mysqli_connect(
        $partConfig['host'], 
        $partConfig['user'], 
        $partConfig['pass'], 
        (isset($partConfig['db_name']) 
            ? $partConfig['db_name'] 
            : $dbPart 
        )
    );
    
    if (!$mysqli) {
        trigger_error('Cannot establish connection to db '.$dbPart.' with given config. Error: '.mysqli_connect_error().
           '; code: '.mysqli_connect_errno(), E_USER_ERROR);
        
        return false;
    }
    
    $connections[$dbPart] = $mysqli;
    
    return $connections[$dbPart];
}

function core_storage_db_get_row ($table, $cols, $where, $cond = []) {
    $connection = _core_storage_db_get_connection(_core_storage_get_db($table));
    $cols = (array)$cols;
    
    if (!$connection) {
        trigger_error('Lost connection from db', E_USER_WARNING);
        return [];    
    }
    
    $whereString =  $where ? _core_storage_db_build_where($connection, $where) : ''; 
    
    $queryString = 'SELECT '.implode(', ', $cols).' '.
        ' FROM '.$table.' '.
        ($whereString ? 'WHERE '. $whereString : '');
    
    if (isset($cond['ORDER BY'])) {
        $queryString .= 'ORDER BY '.$cond['ORDER BY'];
    }
    
    if (isset($cond['LIMIT'])) {
        $queryString .= 'LIMIT '.(int)$cond['LIMIT'];     
    }
    
    $res = mysqli_query($connection, $queryString);
    
    if ($res === false || $connection->error) {
        trigger_error($connection->error);
        return [];
    }
    
    return mysqli_fetch_assoc($res);   
}

function core_storage_db_get_row_one ($table, $cols, $where, $cond = []) {
    $result = core_storage_db_get_row($table, $cols, $where, [
        'LIMIT' => 1,
    ]);
    
    return $result ? $result[0] : $result;    
}

function core_storage_db_insert_row ($table, $bind, $ignore = false) {
    $connection = _core_storage_db_get_connection(_core_storage_get_db($table));
    
    if (!$connection) {
        trigger_error('Lost connection from db', E_USER_WARNING);
        return false;
    }
    
    list ($colsNames, $values) = _core_storage_db_prepare_insert_row($connection, $bind);
    
    $queryString = 'INSERT '.($ignore ? 'IGNORE' : '').' INTO '.$table.' ('.implode(', ', $colsNames).') '.
        ' VALUES ('.implode(',', $values).')';
    
    $res = mysqli_query($connection, $queryString);
    
    if ($res === false || $connection->error) {
        trigger_error($connection->error);
        return [];
    }
    
    return $res;
}

function _core_storage_db_prepare_insert_row($connection, $insertBind) {
    $values = $colsNames = [];
    
    foreach ($insertBind as $colName => $value) {
        $colsNames[] = $colName;
        
        if (is_null($value)) {
            $values[] = 'NULL';
        } else if (is_numeric($value)) {
            $values[] = $value;
        } else {
            $values[] = '"'.mysqli_real_escape_string($connection, $value).'"';
        }
    }
    
    return [$colsNames, $values];
} 

function _core_storage_db_build_where ($connection, $where) {
    if (!is_array($where[0])) {
        $where = [$where];
    }
    
    $whereString = '';
    foreach ($where as $whereParam) {
        list ($field, $operation, $value) = $whereParam;
        $field = mysqli_real_escape_string($connection, $field);
        
        if (is_array($value)) {
            foreach ($value as &$val) {
               $val = mysqli_real_escape_string($connection, $val);
            }
            
            $whereString.= '('.$field.' in ('.implode($value).')'.')';
        } else {
            $whereString .= ($whereString ? ' AND ' : '').'('.$field.' '.$operation.' '.mysqli_real_escape_string($connection, $value).')' ;       
        }
    }
    
    return trim($whereString);
}

function _core_storage_get_db($table) {
    static $cache;
    
    if (!$cache) {
        $tablesConfig = core_config_get('db_tables', []);
        
        foreach ($tablesConfig as $dbPart => $tables) {
            foreach ($tables as $table) {
                $cache[$table] = $dbPart;    
            }
        }
    }
    
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    
    if (isset($cache['*'])) {
        return $cache['*'];
    }
    
    trigger_error('Db partition fot table: "'.$table.'"'.' not found',  E_USER_WARNING);
}