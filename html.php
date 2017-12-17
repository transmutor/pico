<?php

/*
        public domain
        HTML utility functions
*/

function htmlescape($s) {
        return(htmlspecialchars($s, ENT_QUOTES, 'UTF-8'));
}

/* generate HTML attribute string (with 1 space prepended) from a PHP array (string key/values only), escaping attribute keys and values for HTML consumption */
function attribute($a = null, $only = array()) {
        $b = array();
        if (isset($a) && is_array($a))
                foreach($a as $k => $v)
                        if (is_string($k) && (is_string($v) || is_numeric($v)))
                                $b[] = htmlescape($k) . '="' . htmlescape($v) . '"';
        return(empty($b) ? '' : (' ' . join(' ', $b)));
}

/* generate HTML element string - does not escape element content */
function element($e1, $e2 = null, $s = null, $a = null) {
        if (is_array($s) || is_object($s)) $s = null;
        if (empty($e2)) return('<' . $e1 . attribute($a) . ' />');
        return('<' . $e1 . attribute($a) . '>' . $s . '</' . $e1 . '>');
}

/* basic set: no dl() or link() ! */
function html($s, $a = null) { return(element('html', 'html', $s, $a)); }
function head($s, $a = null) { return(element('head', 'head', $s, $a)); }
function body($s, $a = null) { return(element('body', 'body', $s, $a)); }
function title($s, $a = null) { return(element('title', 'title', $s, $a)); }
function meta($a = null) { return(element('meta', null, null, $a)); }
function h1($s, $a = null) { return(element('h1', 'h1', $s, $a)); }
function h2($s, $a = null) { return(element('h2', 'h2', $s, $a)); }
function h3($s, $a = null) { return(element('h3', 'h3', $s, $a)); }
function h4($s, $a = null) { return(element('h4', 'h4', $s, $a)); }
function h5($s, $a = null) { return(element('h5', 'h5', $s, $a)); }
function p($s, $a = null) { return(element('p', 'p', $s, $a)); }
function b($s, $a = null) { return(element('b', 'b', $s, null)); }
function i($s, $a = null) { return(element('i', 'i', $s, $a)); }
function em($s) { return(element('em', 'em', $s, null)); }
function pre($s, $a = null) { return(element('pre', 'pre', $s, $a)); }
function br($a = null) { return(element('br', null, null, $a)); }
function hr($a = null) { return(element('hr', null, null, $a)); }
function iframe($a = null) { return(element('iframe', 'iframe', null, $a)); }
function div($s, $a = null) { return(element('div', 'div', $s, $a)); }
function span($s, $a = null) { return(element('span', 'span', $s, $a)); }
function img($a = null) { return(element('img', null, null, $a)); }
function a($s, $a = null) { return(element('a', 'a', $s, $a)); }
function ol($s, $a = null) { return(element('ol', 'ol', $s, $a)); }
function ul($s, $a = null) { return(element('ul', 'ul', $s, $a)); }
function li($s, $a = null) { return(element('li', 'li', $s, $a)); }
function table($s, $a = null) { return(element('table', 'table', $s, $a)); }
function thead($s, $a = null) { return(element('thead', 'thead', $s, $a)); }
function tbody($s, $a = null) { return(element('tbody', 'tbody', $s, $a)); }
function tr($s, $a = null) { return(element('tr', 'tr', $s, $a)); }
function td($s, $a = null) { return(element('td', 'td', $s, $a)); }
function th($s, $a = null) { return(element('th', 'th', $s, $a)); }
function form($s, $a = null) { return(element('form', 'form', $s, $a)); }
function legend($s, $a = null) { return(element('legend', 'legend', $s, $a)); }
function fieldset($s, $a = null) { return(element('fieldset', 'fieldset', $s, $a)); }
function label($s, $a = null) { return(element('label', 'label', $s, $a)); }
function input($a = null) { return(element('input', null, null, $a)); }
function option($s, $a = null) { return(element('option', 'option', $s, $a)); }
function button($s, $a = null) { return(element('button', 'button', $s, $a)); }
function textarea($s, $a = null) { return(element('textarea', 'textarea', $s, $a)); }
function select($s, $a = null) { return(element('select', 'td', $s, $a)); }
function dt($s, $a = null) { return(element('dt', 'dt', $s, $a)); }
function dd($s, $a = null) { return(element('dd', 'dd', $s, $a)); }
function script($s, $a = null) { return(element('script', 'script', $s, $a)); }
function style($s, $a = null) { return(element('style', 'style', $s, $a)); }

/* utility */

function comment($s) { return('<!-- ' . $s . ' -->'); }
function hn($n, $s, $a = null) { return(element(('h' . $n), ('h' . $n), $s, $a)); }

function id() {
        return('i' . uniqid());
}

function x($a = null, $exit = 1) {
        global $argv;
        echo (isset($argv) ? (print_r($a, 1) . "\n") : pre(htmlescape(print_r($a, 1))));
        if ($exit) exit();
}

?>
