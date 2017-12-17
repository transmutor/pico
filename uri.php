<?php


        /* parse a uri into components according to RFC 2396, substituting missing values from $base */
        function uri($s, $base = null) { // see appendix B, http://www.ietf.org/rfc/rfc2396.txt

        if (empty($s)) return(array());

                if (isset($base) && $base !== '') {
                        $b = uri($base);
                        if (empty($b['scheme'])) return(array()); // scheme must be present in base
                } else {
                        $b = array();
                }

                $a = array();

                if (preg_match('<^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?>', $s, $m)) { // using < and > expression delimiters

                        $samescheme = empty($b['scheme']) || empty($m[2]) || ($b['scheme'] == $m[2]);

                        //$a['m'] = $m;
                        $a['scheme'] = (isset($m[2]) && $m[2] !== '') ? $m[2] : null;
                        $a['authority'] = (isset($m[3]) && $m[3] !== '') ? $m[3] : null; // domain
                        $a['path'] = (isset($m[5]) && $m[5] !== '') ? $m[5] : null;
                        $a['query'] = (isset($m[7]) && $m[7] !== '') ? $m[7] : null;
                        $a['fragment'] = (isset($m[9]) && $m[9] !== '') ? $m[9] : null;

                        if (!empty($b['path'])) {
                                $c = explode('/', $b['path']);
                                array_pop($c);
                                $basedirectory = join('/', $c);
                        } else {
                                $basedirectory = null;
                        }

                        $a['scheme'] = (isset($a['scheme']) ? $a['scheme'] : (isset($b['scheme']) ? $b['scheme'] : null));
                        $a['authority'] = (isset($a['authority']) ? $a['authority'] : (isset($b['authority']) && $samescheme ? $b['authority'] : ''));

                        if (isset($a['path'])) {
                                if (isset($b['path']) && $samescheme && ($a['authority'] == $b['authority'])) $a['path'] = absolutepath($a['path'], $b['path']);
                        } else {
                                if (isset($b['path']) && $samescheme && $a['authority'] == $b['authority']) {
                                        if (empty($a['query'])) {
                                                $a['path'] = $b['path'];
                                        } else {
                                                $a['path'] = $basedirectory . '/';
                                        }
                                } else {
                                        $a['path'] = null;
                                }
                        }

                        $a['query'] = (isset($a['query']) ? $a['query'] : ((isset($b['path']) && $a['path'] == $b['path']) ? $b['query'] : null));
                        $a['fragment'] = (isset($a['fragment']) ? ($a['fragment']) : null);
                        $a['protocol'] = $a['scheme'];

                        if (isset($a['authority']) && substr($a['authority'], 0, 2) == '//') {
                                if (($i = strpos($a['authority'], '@'))) {
                                        $up = explode(':', substr($a['authority'], 2, $i - 2));
                                        $a['user'] = isset($up[0]) ? $up[0] : null;
                                        $a['password'] = isset($up[1]) ? $up[1] : null;
                                        $hostport = substr($a['authority'], $i + 1);
                                } else {
                                        $hostport = substr($a['authority'], 2);
                                }
                                if (($i = strpos($hostport, ':'))) {
                                        $a['host'] = substr($hostport, 0, $i);
                                        $a['port'] = substr($hostport, $i + 1);
                                } else {
                                        $a['host'] = $hostport;
                                        $a['port'] = null;
                                }
                        } else {
                                $a['host'] =  null;
                                $a['port'] =  null;
                                $a['user'] =  null;
                                $a['password'] =  null;
                        }

                        $a['full'] = (
                                $a['scheme']
                                . ':'
                                . (isset($a['authority']) ? $a['authority'] : '')
                                . $a['path']
                                . (isset($a['query']) ? ('?' . $a['query']) : '')
                                . (isset($a['fragment']) ? ('#' . $a['fragment']) : '')
                        );
                }
                return($a);
        }


        function absolutepath($path, $base = null) {

                $directory = (substr($path, -1) == '.');

                $path = explode('/', $path); //x($path);

                // alter $base so it does not begin with /, does not end in / and does not contain ../ or ./ (leading / will be added later).
                if (!empty($base)) {
                        $base = explode('/', $base);
                        if (empty($base[0])) {
                                foreach($base as $k => $v) if ($v == '.' || $v == '..') return(null);
                        } else {
                                return(null); // must start with a /
                        }
                }

                if (empty($path[0])) { //absolute
                        $new = array('');
                } else {
                        $new = (isset($base)) ? $base : array();
                        if (count($new) > 1 && $new[(count($new) - 1)] !== '') array_pop($new);
                }

                for ($i = 0; $i < count($path); $i++) {
                        if ($path[$i] == '..') {
                                if (count($new) > 0) {
                                        array_pop($new);
                                } else {
                                        return(null);
                                }
                        } else if ($path[$i] !== '.' && $path[$i] !== '') {
                                $new[] = $path[$i];
                        }
                }

                if ($path[(count($path) - 1)] === '') $new[] = '';

                return(join('/', $new) . ($directory ? '/' : ''));
        }

?>
