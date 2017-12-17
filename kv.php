<?php

        /*
          public domain
          get, set or remove a value from a referenced array
        */
        function kv(&$var, $k, $v = null, $cmd = null) {
                if (!isset($k)) return(null); // must have a key;
                if (!is_array($var)) $var = array();
                if (isset($v)) {
                        if (isset($var[$k])) {
                                if ($cmd == 'delete') {
                                        $v = $var[$k];
                                        unset($var[$k]);
                                        return($v);
                                } else if ($cmd == 'append') {
                                        if (!is_array($var[$k])) $var[$k] = array($var[$k]);
                                        return($var[$k][] = $v);
                                } else if ($cmd == 'default') {
                                        return($var[$k]);
                                } else {
                                        return($var[$k] = $v); // updated value
                                }
                        } else {
                                if ($cmd == 'default') return($v);
                                if ($cmd == 'delete') return($v);
                                if ($cmd == 'append') $v = array($v);
                        }
                        return($var[$k] = $v); // added value
                } else {
                        if ($cmd == 'delete') {
                                unset($var[$k]);
                        } else {
                                return(isset($var[$k]) ? $var[$k] : null);
                        }
                }
        }

?>
