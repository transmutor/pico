<?php
/*
        explain $sql, $rs, $r, $flds
        function connect()
        function database($s)
        function close()
        function escape($s)
        function quote($s)
        function query($sql)
        function fetchrow($rs)
        function fetcharray($rs)
        function limit($start, $count)
        function error()
        function dateto($t)
        function datefrom($s)
        function build($a)
        function sql2array($sql)
        function sql2row($sql)
        function sql2row($sql)
        function last()
        function affected()
        function insert($a, $tablename, $flds)
        function update($a, $tablename, $flds, $where)
        function select($sql, $flds)
        function where($a, $flds) {
        function tablelist()
        function schema($tablename)
        function create($tablename, $a)
        function createfield($a)

        BIT - 1
        TINYINT - 8 (255)
        SMALLINT - 16 (56.5k)
        MEDIUMINT - 24 (16M)
        INT - 32 (4G)
        BIGINT - 64
        BITON: bitmask = bitmask | POW(2, n)
        BITOFF: bitmask = ~((~bitmask) | POW(2, n))

*/

class mysql {

        var $link = null;
        var $host = null;
        var $port = null;
        var $username = null;
        var $password = null;
        var $database = null;
        var $characterset = 'utf8';
        var $qlink = null;
        var $log = array(); // set to null to switch off
        var $stripslashes = 1; // switch this off if selecting binary data
        var $strict = 0; // die if a query fails

        function mysql($host = null, $username = null, $password = null, $database = null, $port = null) {
                $this->host = $host;
                $this->username = $username;
                $this->password = $password;
                $this->database = $database;
                $this->port = $port;
        }

        function connect() {
                if ($this->link) return ($this->link);
                if ($this->link = mysql_connect($this->host . (empty($this->port) ? '' : (':' . $this->port)), $this->username, $this->password, $new = 1)) {
                        if (!empty($this->database)) if (!$this->choose($this->database)) return (false);
                        $this->query('SET time_zone = "+0:00"');
                        if ($this->characterset) $this->query('SET character set ' . $this->characterset);
                } else {
                        if (isset($this->log)) $this->log[] = 'Could not connect to database server';
                        if ($this->strict) die(join("\n", $this->log));
                }
                return ($this->link);
        }

        function choose($s) {
                if (empty($this->link)) {
                        $this->database = $s;
                        if (!$this->connect()) {
                                return(false);
                        }
                } else {
                        if (!mysql_select_db($s, $this->link)) {
                                if ($this->strict) die(mysql_error($this->link));
                                if (isset($this->log)) $this->log[] = mysql_error($this->link);
                                return (false);
                        } else {
                                if (isset($this->log)) $this->log[] = 'Database changed to: ' . $s;
                        }
                        $this->database = $s;
                }
                return (true);
        }

        function close() {
                $x = mysql_close($this->link);
                if (!$x && $this->strict) die(mysql_error($this->link));
                $this->link = null;
                return($x);
        }

        function escape($s, $prefix = null, $suffix = null) {
                /* how are strings escaped in SQL */
                return ("'" . $prefix . mysql_escape_string($s) . $suffix . "'");
        }

        function quote($s) {
                /* how are database tables and fields quotes
                   take note of tablename.fieldname syntax
                   if $s is an array, quote each element and join with a comma
                */
                if (is_array($s)) {
                        foreach($s as $k => $v) $s[$k] = '`' . str_replace('.','`.`',$v) . '`';
                        return (join(', ', $s));
                } else {
                        return ('`' . str_replace('.','`.`',str_replace(',','`,`',$s)) . '`');
                }
        }

        function query($sql) {
                $this->qlink = null;
                if (!$this->link && !$this->connect()) return(false);
                if (is_array($sql)) $sql = $this->build($sql);
                if (isset($this->log)) $this->log[] = $sql;
                if (!$this->qlink = mysql_query($sql, $this->link)) {
                        if (isset($this->log)) $this->log[] = mysql_error($this->link);
                        if ($this->strict) die('mysql error: ' . (isset($this->log) ? join("\n", $this->log) : mysql_error($this->link)));
                }
                return ($this->qlink);
        }

        function fetchrow() {
                if (!$this->qlink) return(false);
                if ($r = mysql_fetch_row($this->qlink)) {
                        if ($this->stripslashes) foreach ($r as $k => $v) if (isset($v)) $r[$k] = stripslashes($v);
                }
                return ($r);
        }

        function fetcharray() {
                if (!$this->qlink) return(false);
                if ($r = mysql_fetch_assoc($this->qlink))
                        if ($this->stripslashes) foreach ($r as $k => $v) if (isset($v)) $r[$k] = stripslashes($v);
                return ($r);
        }

        function last() {
                return(mysql_insert_id($this->link));
        }

        function affected() {
                return(mysql_affected_rows($this->link));
        }


        function limit($start, $count) {
                return($start . ', ' . $count);
        }

        function error() {
                return (mysql_error($this->link));
        }

        function dateto($t) { /* Translate a script date (timestamp) to SQL string representation (UTC stored in DB) */
                return(gmdate('YmdHis', $t));
        }

        function datefrom($s) { /* Translate a database datetime field to script date (timestamp) (UTC stored in DB) */
                if ($s == '' || $s == '0000-00-00 00:00:00') return(null);

                if (($t = @gmmktime(substr($s, 11, 2), substr($s, 14, 2), substr($s, 17, 2), substr($s, 5, 2), substr($s, 8, 2), substr($s, 0, 4))) !== false) return($t);
                return(null);
        }

        function build($a) {
                if (is_array($a)) {
                        $s = '';
                        foreach(array('select', 'from', 'where', 'group by', 'having', 'order by', 'limit') as $v) if (!empty($a[$v]) && !is_array($a[$v])) $a[$v] = array($a[$v]);
                        $s .= (!empty($a['select'])) ? ('SELECT ' . join(', ', $a['select'])) : '';
                        $s .= (!empty($a['from'])) ? (' FROM ' . join(', ', $a['from'])) : '';
                        $s .= (!empty($a['where'])) ? (' WHERE (' . join(') AND (', $a['where']) . ')') : '';
                        $s .= (!empty($a['group by'])) ? (' GROUP BY ' . join(', ', $a['group by'])) : '';
                        $s .= (!empty($a['having'])) ? (' HAVING ' . join(', ', $a['having'])) : '';
                        $s .= (!empty($a['order by'])) ? (' ORDER BY ' . join(', ', $a['order by'])) : '';
                        $s .= (!empty($a['limit'])) ? (' LIMIT ' . join(', ', $a['limit'])) : '';
                        return ($s);
                }
                return ($a);
        }

        function sql2array($sql) {
                $rs = array();
                if ($this->query($sql)) while ($r = $this->fetcharray()) $rs[] = $r;
                return($rs);
        }

        function sql2row($sql) {
                $rs = array();
                if ($this->query($sql)) while ($r = $this->fetchrow()) $rs[] = $r;
                return($rs);
        }

        function sql2one($sql) {
                if ($this->query($sql)) if ($r = $this->fetchrow()) return($r[0]);
                return(null);
        }

        function sql2index($sql) {
                $rs = array();
                if ($this->query($sql)) while ($r = $this->fetchrow()) $rs[($r[0])] = $r[1];
                return($rs);
        }

        function select($sql, $flds = null) {
                $rs = $this->sql2array($sql);
                foreach (array_keys($rs) as $k) {
                        $r =& $rs[$k];
                        if (isset($flds)) foreach ($flds as $f) {
                                if (isset($f['name']) && isset($f['type']) && (!isset($f['mode']) || $f['mode'] & 1)) {
                                        $n =& $f['name'];
                                        $t =& $f['type'];
                                        if (isset($r[$n])) {
                                                switch ($t) {
                                                        case 'date':
                                                                $r[$n] = $this->datefrom($r[$n]);
                                                                break;
                                                        case 'number':
                                                                $r[$n] = floatval($r[$n]);
                                                                break;
                                                }
                                        }
                                }
                        }
                }
                return($rs);
        }

        function insert($r, $tablename, $flds) {
                $b = $c = array();
                if (empty($flds)) return(array());
                foreach( $flds as $fld ) {
                        if (isset($fld['name']) && isset($fld['type'])) {
                                $name = $fld['name'];
                                if (empty($fld['auto']) && (!isset($fld['mode']) || $fld['mode'] & 2)) {
                                        if (!isset($r[$name]) && isset($fld['default'])) $r[$name] = $fld['default']; // set default value
                                        if (!isset($r[$name]) || (empty($r[$name]) && $r[$name] != 0)) { // matches 0 if ==
                                                $r[$name] = 'NULL';
                                        } else if ($fld['type'] == 'text' || $fld['type'] == 'binary') {
                                                $r[$name] = $this->escape($r[$name]);
                                        } else if ($fld['type'] == 'date') {
                                                $r[$name] = $this->dateto($r[$name]);
                                        } else if ($fld['type'] == 'number') {
                                                $r[$name] = is_numeric($r[$name]) ? $r[$name] : 'NULL';
                                        }
                                        $b[] = $this->quote($name);
                                        $c[] = $r[$name];
                                }
                        }
                }
                $sql = 'INSERT INTO ' . $this->quote($tablename) . ' (' . join(',', $b) . ') VALUES (' . join(',', $c) . ');';
                return($this->query($sql));
        }

        function update($r, $tablename, $flds, $where) {
                // $where is array of sql clauses
                $b = array();
                if (empty($flds)) return(array());
                if (!is_array($where)) $where = array($where);
                foreach ($flds as $fld) {
                        if (isset($fld['name']) && isset($fld['type'])) {
                                $name = $fld['name'];
                                if (empty($fld['auto']) && (!isset($fld['mode']) || $fld['mode'] & 4)) {
                                        if (!isset($r[$name]) || (empty($r[$name]) && $r[$name] != 0)) { // matches 0 if ==
                                                $r[$name] = 'NULL';
                                        } else if ($fld['type'] == 'text' || $fld['type'] == 'binary') {
                                                $r[$name] = $this->escape($r[$name]);
                                        } else if ($fld['type'] == 'date') {
                                                $r[$name] = $this->dateto($r[$name]);
                                        } else if ($fld['type'] == 'number') {
                                                $r[$name] = is_numeric($r[$name]) ? $r[$name] : 'NULL';
                                        }
                                        $b[] = $this->quote($name) . ' = ' . $r[$name];
                                }
                        }
                }
                $sql = 'UPDATE ' . $this->quote($tablename) . ' SET ' . join(',', $b) . ' WHERE ' . '(' . join(') AND (', $where) . ')' . ';';
                return($this->query($sql));
        }

        function where($a, $flds) {
                $b = array();
                if (!is_array($a)) die('bad criteria array');
                $keys = array(); foreach($flds as $k => $v) if (isset($v['name'])) $keys[($v['name'])] = $k;
                foreach ($a as $k => $v) {
                        $n = $keys[$k];
                        if (isset($flds[$n])) {
                                $fld =& $flds[$n];
                                if ($v == '') {
                                        $v = 'NULL';
                                } else if ($fld['type'] == 'text') {
                                        $v = $this->escape($v);
                                } else if ($fld['type'] == 'date') {
                                        $v = $this->dateto($v);
                                } else if ($fld['type'] == 'number') {
                                        $v = is_numeric($v) ? $v : 'NULL';
                                }
                                $b[] = $this->quote($fld['name']) . ' = ' . $v;
                        } else {
                                die('unknown criteria parameter: ' . $n);
                        }
                }
                return('(' . join(') AND (', $b) . ')');
        }

        function tablelist() {
                $b = array();
                foreach($this->sql2row('SHOW TABLES') as $k => $r) $b[] = $r[0];
                return($b);
        }

        function schema($tablename, $only = array()) {
                $rs = array();

                foreach($this->sql2row('SHOW FIELDS FROM ' . $this->quote($tablename)) as $k => $r) {
                        if (!empty($only) && !in_array($r[0], $only)) continue; // skip if not in list (and list specified)
                        $f = array();  // name, type, size, precision, auto
                        $f['table'] = $tablename;
                        $f['name'] = $r[0];
                        $s = null;
                        $t = $r[1];
                        $p = 0;
                        if (($i = strpos($t, '(')) > 0) {
                                $s = substr($t, $i+1, (strpos($t, ')') - $i - 1));
                                if ($j = strpos($s, ',') !== false) {
                                        $p = substr($s, $j + 1);
                                        $s = substr($s, 0, strpos($s, ','));
                                }
                                $t = substr($t, 0, $i);

                        }
                        if (strpos($t, ' ') !== false) $t = substr($t, 0, strpos($t, ' '));

                        switch ($t) {
                                case 'int': case 'bigint': case 'tinyint': case 'smallint': case 'mediumint': case 'decimal': case 'float': case 'double':
                                        $f['type'] = 'number'; break;
                                case 'varchar': case 'char': case 'text': case 'longtext': case 'mediumtext': case 'tinytext':
                                        $f['type'] = 'text'; break;
                                case 'date':
                                        $f['type'] = 'date'; $s = 8; break;
                                case 'datetime': case 'timestamp':
                                        $f['type'] = 'date'; $s = 14; break;
                                case 'time':
                                        $f['type'] = 'date'; $s = 6; break;
                                case 'blob': case 'tinyblob': case 'mediumblob': case 'longblob': case 'binary':
                                        $f['type'] = 'binary'; break;
                                default:
                                        die('datatype mapping not found for mysql type: ' . $t); break;
                        }

                        $f['size'] = intval($s);
                        $f['precision'] = isset($p) ? intval($p) : null;
                        $f['auto'] = $r[5] === 'auto_increment' ? 1 : null;
                        $f['primary'] = $r[3] === 'PRI' ? 1 : null;
                        $rs[($tablename . '.' . $r[0])] = $f;
                }
                return($rs);
        }

        function create($tablename, $flds) {
                $b = array();
                foreach ($flds as $f) $b[] = $this->createfield($f);
                $s = 'CREATE TABLE ' . $this->quote($tablename) . ' (' . join(',', $b) . ');';
                return($s);
        }

        function createfield($f) {
                $s = $this->quote($f['name']) . ' ';
                switch ($f['type']) {
                        case 'number':
                                $s .= isset($f['precision']) ? (isset($f['size']) ? 'FLOAT(' . $f['size'] . ',' . $f['precision'] . ')' : 'FLOAT') : (isset($f['size']) ? 'INT(' . $f['size'] . ')' : 'INT');
                                $s .= !empty($f['auto']) ? ' AUTO_INCREMENT' : '';
                                break;
                        case 'text':
                                $s .= empty($f['size']) || $f['size'] > 254 ? 'TEXT' : 'VARCHAR(' . $f['size'] . ')';
                                break;
                        case 'date':
                                $s .= empty($f['size']) || $f['size'] >= 14 ? 'DATETIME' : ($f['size'] > 8 ? 'DATE' : 'TIME');
                                break;
                        case 'binary':
                                $s .= 'LONGBLOB';
                                break;

                }
                $s .= empty($f['primary']) ? '' : ' PRIMARY KEY';
                $s .= empty($f['null']) ? ' NOT NULL' : ' NULL DEFAULT NULL';
                $s .= empty($f['index']) ? '' : ', INDEX(' . $this->quote($f['name']) . ')';
                return($s);
        }

}

?>
