<?php
/*
        public domain
*/

function displayselect($c) {
	if (isset($c['options']) && is_array($c['options'])) {
	
		$c['value'] = isset($c['value']) ? $c['value'] : null;
		foreach($c['options'] as $k => $v) {
			if (is_array($v)) { // user supplied option attributes
				if (isset($v['value']) && strlen($c['value']) && $c['value'] == $v['value']) return(isset($v['label']) ? htmlescape($v['label']) : null);
			} else {
				if (strlen($c['value']) && $c['value'] == $k) return(htmlescape($v));
			}
		}
	}
}

function controlselect($a) {
	$b = array();
	$selected = isset($a['selected']) ? $a['selected'] : null; unset($a['selected']);
	$selected = isset($selected) ? $selected : (isset($a['value']) ? $a['value'] : null); unset($a['value']);
	$empty = isset($a['empty']) ? $a['empty'] : null; unset($a['empty']);

	unset($a['type']);

	$a['class'] = addon((isset($a['class']) ? $a['class'] : null), 'form-control');

	if (isset($a['options']) && is_array($a['options'])) {
		if (isset($empty)) $b[] = controloption(array('value' => '', 'label' => $empty, 'selected' => (strlen($selected) ? null : 'selected')));
		foreach($a['options'] as $k => $v) {
			if (is_array($v)) { // user supplied option attributes
				$b[] = controloption($v);
			} else {
				$b[] = controloption(array(
					'value' => $k,
					'label' => $v,
					'selected' => (strlen($selected) && $selected == $k ? 'selected' : null)
				));

			}
		}
	}
	return(select(join('', $b), $a));
}

function convertfromselect($c, $r, $frm) {
	return(isset($c['name']) && isset($r[$c['name']]) ? $r[$c['name']] : null);
}

function converttoselect($r, $n) {
	return($r[$n]);
}


function controloption($a) {
	$value = isset($a['value']) ? $a['value'] : '';
	$html = isset($a['label']) ? htmlentities($a['label']) : htmlentities($value);
	$a['label'] = null;
	return(option($html, $a));
}

?>
