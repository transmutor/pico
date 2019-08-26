<?php
/*
        public domain
*/

	date_default_timezone_set(mem('timezone', 'UTC', 'default'));

	function tick() {
		$a = explode(' ', microtime());
		return(round(((float)$a[0] + (float)$a[1]) * 1000000));
	}

	function slug($s) {
		/* lowercase and remove unwanted characters from string */
		return(empty($s) ? null : strtolower(preg_replace('/\s+/', '-', preg_replace('/[^\w \.\-]/', '', trim($s)))));
	}

	function truncate($s, $n, $htmlescape = 1) {
		return((isset($n) && $n > 0 && strlen($s) > $n) ? ($htmlescape ? (htmlescape(substr($s, 0, $n)) . '&hellip;') : (substr($s, 0, $n) . '�')) : ($htmlescape ? htmlescape($s) : $s));
	}
	function is_sql($x) {
		return (isset($x) && (is_numeric($x) || (is_string($x) && strlen(trim($x)) > 0)));
	}

	function csvescape($s) {
		return('"' . str_replace('"', '""', $s) . '"');
	}

	/* add a string to the end of a string or array and return string */
	function addon($v, $s, $d = ' ') {
		if (!isset($s)) return($v);
		if (!isset($v)) return($s);
		if (is_array($v)) {
			array_push($v, $s);
			return(join($d, $v));
		} else if (is_string($v)) {
			$v = explode($d, $v);
			array_push($v, $s);
			return(join($d, $v));
		} else {
			return($s);
		}
	}



	if (!function_exists('session')) {
		function session($k, $v = null, $cmd = null) {
		  return(kv($_SESSION, $k, $v, $cmd));
		}
	}


	/* remember a user message to display on next page load */
	function flash($s = null, $key = 'flash') {
		if (isset($s)) {
			if (!is_array($s)) $s = array($s);
			foreach ($s as $v) session($key, $v, 'append');
		} else {
			$a = session($key, array(), 'default');
			session($key, null, 'delete');
			return($a);
		}
	}



	/* generate HTML to display a form */
	function dataform($frm, $r = null) {

		$frm['method'] = empty($frm['method']) ? 'post' : $frm['method'];

		$a = array();
		foreach ($frm as $k => $v) if (is_string($k) && (is_string($v) || is_numeric($v)) && (strpos($k, 'data-') === 0 || in_array($k, array('id', 'class', 'action', 'method', 'enctype')))) $a[$k] = $v;
		return(form(
			(isset($frm['legend']) ? legend(htmlescape($frm['legend'])) : null)
			. controls($frm, $r)
			, $a
		));
	}

	function controls($frm, $r = null) {
		$b = array();
		if (isset($frm['controls']) && is_array($frm['controls'])) {

			foreach($frm['controls'] as $k => $c) {
				// show both insert and update controls if form mode not specified, bitmask: 1 - select, 2 - insert, 4 - update
				$show = isset($frm['mode']) ? (isset($c['mode']) ? (($frm['mode'] & $c['mode']) == $frm['mode']) : 1) : (isset($c['mode']) ? ($c['mode'] & 2 || $c['mode'] & 4) : 1);
				if ($show && !empty($c['control']) && (!isset($c['visible']) || $c['visible'] != 0)) {

					if ($c['control'] == 'fieldset') {
						unset($c['control']);
						$legend = isset($c['label']) ? $c['label'] : null; unset($c['label']);
						$legend = isset($c['legend']) ? $c['legend'] : $legend; unset($c['legend']);
						$controls = isset($c['controls']) ? $c['controls'] : null; unset($c['controls']);

						$b[] = fieldset(
							(isset($legend) ? legend($legend) : null)
							. (isset($controls) ? controls(array('controls'=>$controls)) : null)
							, $c
						);

					} else {
						$frm['row'] =& $r; //  pass the whole row to control as well (via the $frm parameter)
						if (isset($c['name'])) {
							if (isset($r[($c['name'])])) {
								$c['value'] = $r[($c['name'])]; // pre-load an edit form with values from $r
							} else {
								if (isset($frm['mode']) && $frm['mode'] == 2 && !isset($c['value']) && isset($c['default'])) $c['value'] = $c['default'];
								// use default value if it is an insert form
							}
						}
						$b[] = control($c, $frm);
					}
				}
			}
		}

		return(join('', $b));
	}

	function control($c, $frm = null) {
		/*
			wrap in div and label if label attribute set
			auto generate missing attributes: id, name and type
		*/
		if (isset($c) && is_array($c) && !empty($c['control'])) {
			$prefix = isset($c['prefix']) ? $c['prefix'] : null; unset($c['prefix']);
			$reverse = isset($c['reverse']) ? $c['reverse'] : null; unset($c['reverse']);
			if (isset($c['name']) && $c['name'] !== '') $c['name'] = $prefix . $c['name'];
			if (empty($c['name']) && empty($c['id'])) $c['id'] = 'id' . uniqid();
			if (empty($c['id'])) $c['id'] = id();
			if (!empty($c['width'])) $c['size'] = $c['width'];
			if (isset($c['raw'])) $raw = $c['raw'];

			$control = $c['control']; unset($c['control']);
			if ($control == 'submit') {
			} else {
				if (isset($c['label'])) $label = $c['label']; unset($c['label']);
			}

			unset($c['mode']);
			unset($c['source']);
			unset($c['raw']);


			$f = 'control' . $control;
			if (!function_exists($f)) include_once($f . '.php');
			$s = function_exists($f) ? $f($c, $frm) : 'bad control type: ' . $control;
			if ($control == 'hidden' || $control == 'submit' || !empty($raw)) return($s);

			/*
				mem('application.control.div.class', 'control');
				mem('application.label.div.class', 'label');
				mem('application.label.class', null);
				mem('application.input.div.class', 'input');
			*/

			$s = div($s, array('class'=>mem('application.input.div.class')));
			$label =  (isset($label)) ? div(label($label, array('for'=>$c['id'], 'class'=>mem('application.label.class'))), array('class'=>addon(null, mem('application.label.div.class')))) : '';
			$class = mem('application.control.div.class');
			return(div(
				($reverse ? ($s . $label) : ($label . $s))
				. (empty($c['error']) ? '' : label(htmlescape($c['error']), array('class'=>'error', 'for'=>$c['id'])))
				, array('class'=>addon($class, $control))
			));


		}
	}

	function convertfrom($r, $frm) { // convert from user/http entry
		$rn = array();

		$frm['mode'] = isset($frm['mode']) ? $frm['mode'] : null;
		foreach ($frm['controls'] as $k => $c) {
			// mode bitmask: 1 - select, 2 - insert, 4 - update, 8 - delete
			$show = (isset($frm['mode']) && isset($c['mode']) && ($c['mode'] & $frm['mode']) != $frm['mode']) ? 0 : 1;

			if ($show && isset($c['name'])) {
				$n = $c['name'];
				if (isset($c['control'])) { // && empty($c['auto'])) {
					$t = $c['control'];
					switch ($t) {
						case 'fieldset':
							if (isset($c['controls'])) $rn = array_merge($rn, convertfrom($r, array('controls'=>$c['controls'], 'mode'=>$frm['mode'])));
							break;
						case 'date':
							if (isset($r[$n])) $rn[$n] = ($r[$n] == '') ? null : strtotime($r[$n]);
							break;
						case 'boolean':
							if (isset($r[$n]) && $r[$n] !== '') {
								$rn[$n] = $r[$n];
							} else {
								if (isset($r[($n . '_')])) $rn[$n] = 0;
							}
							break;
						case 'number': case 'text': case 'binary': case 'textarea': case 'boolean': case 'hidden':
							if (isset($r[$n])) $rn[$n] = ($r[$n] == '') ? null : $r[$n];
							break;
						default:
							$fn = 'convertfrom' . $t;
							if (!function_exists($fn)) include_once('control' . $t . '.php');
							if (function_exists($fn)) {
								$rn[$n] = $fn($c, $r, $n);
							} else {
								die('cannot convert from unknown control ' . $t);
							}
							break;
					}
				} else {
					if (array_key_exists($n, $r)) $rn[$n] = $r[$n]; // no control, copy over
				}
			} // no name
		} // foreach control
		return($rn);
	}

	function convertto($r, $frm) { // convert from user/http entry

		foreach ($frm['controls'] as $k => $c) {
			if (isset($c['name']) && isset($c['control'])) {
				$n = $c['name'];
				$t = $c['control'];
				if (isset($r[$n]) && $r[$n] !== '') {
					switch ($t) {
						case 'fieldset':
							if (isset($c['controls'])) $r = array_merge($r, convertto($r, array('controls'=>$c['controls'])));
							break;
						case 'date':
							$r[$n] = date(mem('dateformat', 'd M Y H:i:s', 'default'), intval($r[$n]));
							break;
						case 'number': case 'text': case 'binary': case 'textarea': case 'boolean': case 'password': case 'select':
							break;
						default:
							$fn = 'convertto' . $t;
							if (!function_exists($fn)) include_once('control' . $t . '.php');
							if (function_exists($fn)) {
								$r[$n] = $fn($r[$n]);
							} else {
								//die('cannot convert to unknown control ' . $t);
							}
							break;
					}
				}
			}
		}
		return($r);
	}

	function display($r, $frm) {
		/* convert values in recordset $rs from script to human/html output */
		$flds =& $frm['controls'];
		$rn = array();
		foreach ($flds as $k => $c) {
			if (isset($c['name']) && isset($c['control']) && (!isset($c['mode']) || ($c['mode'] & 1))) {
				$n =& $c['name'];
				$t =& $c['control'];
				$fn = 'display' . $t;
				if (!function_exists($fn)) include_once('control' . $t . '.php');
				if (function_exists($fn)) {
					$c['value'] = (isset($r[$n]) ? $r[$n] : null);
					$rn[$n] = $fn($c, $r, $frm);
				} else {
					die('cannot display unknown control ' . $t);
				}
			}
		}
		return($rn);
	}

	function displaynumber($c) {
		return(htmlescape(isset($c['precision']) ? number_format($c['value'], intval($c['precision'])) : $c['value']));
	}

	function displayboolean($c) {
		return(empty($c['value']) ? 'No' : 'Yes');
	}

	function displaydate($c) {
		$format = empty($c['format']) ? (empty($c['dateonly']) ? (mem('dateformat', 'd M Y', 'default') . ' ' . mem('timeformat', 'H:i:s', 'default')) : mem('dateformat', 'd M Y', 'default')) : $c['format'];
		return(is_numeric($c['value']) ? date($format, intval($c['value'])) : null);
	}

	function displaytext($c) {
		return(htmlescape((isset($c['truncate']) && $c['truncate'] > 0 && strlen($c['value']) > $c['truncate']) ? (substr($c['value'], 0, $c['truncate']) . ' ...') : $c['value']));
	}

	function displaybinary($c) {
		return('[binary (' . strlen($c['value']) . ')]');
	}

	function controltext($c) {
		$c['type'] = 'text';
		$c['class'] = addon((isset($c['class']) ? $c['class'] : null), 'form-control');
		return(input($c));
	}

	function controlbinary($a) {
		return(span('[binary (' . (isset($a['value']) ? strlen($a['value']) : '0') . ' bytes)]'));
	}

	function controlnumber($a) {
		return(input(array_merge($a, array('type'=>'text', 'class'=>'form-control'))));
	}

	function controldate($a) {
		return(input(array_merge($a, array('type'=>'text', 'value'=>(isset($a['value']) ? displaydate($a) : null), 'class'=>'form-control'))));
	}

	function controlcheckbox($a) {
		return(input(array_merge($a, array('type'=>'checkbox'))));
	}

	function controlboolean($a) {
		return(
			input(array_merge($a, array('type'=>'checkbox', 'value'=>1, 'checked'=>(empty($a['value']) ? null : 'checked'))))
			. input(array('type'=>'hidden', 'name'=>($a['name'] . '_'), 'value'=>1))
		);
	}

	function validateboolean($r, $n) {
		return(true);
	}

	function controlsubmit($a) {
		if (isset($a['value'])) {
			return(input(array_merge($a, array('type'=>'submit'))));
		} else {
			$label = isset($a['label']) ? $a['label'] : 'Submit'; unset($a['label']);
			$a['type'] = 'submit';
			$a['class'] = addon((isset($a['class']) ? $a['class'] : null), 'btn btn-default');
			return(button($label, $a));
		}
	}

	function controlhidden($a) {
		return(input(array_merge($a, array('type'=>'hidden'))));
	}

	function validate($r, $frm, $o = null) {
		/* validate a row $r against the fields in form $frm, returning an altered form with added error elements and value elements (from original unconverted values in $o) */
		$errors = 0;

		foreach ($frm['controls'] as $k => $c) {

			$control = isset($c['control']) ? $c['control'] : null;

			if ($control == 'fieldset' && isset($c['controls'])) {

				$tmp = validate($r, array('controls'=>$c['controls']), $o);
				$frm['controls'][$k]['controls'] = $tmp['controls'];
				$errors += $tmp['errors'];

			} else {

				if (isset($c['name'])) {
					$e = null;
					$n = $c['name'];
					$t = isset($c['type']) ? $c['type'] : null;

					if (!empty($c['locked'])) unset($r[$n]); // whether set or not

					if (isset($r[$n]) && $r[$n] !== '') {
						$v = $r[$n];

						$frm['controls'][$k]['value'] = isset($o[$n]) ? $o[$n] : ''; // use original unconverted value

						if ($t == 'number') {
							if (!is_numeric($v)) {
								$e = 'Please enter a number';
							} else {
								if (isset($c['max']) && $v > $c['max']) $e = 'Number exceeds the maximum allowed';
								if (!$e && isset($c['min']) && $v < $c['min']) $e = 'Number exceeds the minimum allowed';
							}
						} else if ($t == 'text') {
							if (isset($c['max']) && strlen($v) > $c['max']) $e = 'Too many letters';
							if (!$e && isset($c['min']) && strlen($v) < $c['min']) $e = 'Too few letters';
						} else if ($t == 'date') {
							/* assume min/max dates and date value is in script format (timestamp) */
							if (!is_numeric($v)) $e = 'Invalid date';
							if (!$e && isset($c['max']) && $v > $c['max']) $e = 'Date is too late';
							if (!$e && isset($c['min']) && $v < $c['min']) $e = 'Date is too early';
						}

						if (!empty($c['control']) && !in_array($c['control'], array('number','text','date','hidden'))) {
							$fn = 'validate' . $c['control'];
							if (!function_exists($fn)) include_once('control' . $c['control'] . '.php');
							if (function_exists($fn) && !$fn($r, $n)) $e = 'The value is invalid';
						}

					} else {
						if (!empty($c['required'])) $e = 'Missing required value';
					}

					if (!empty($e)) {
						$frm['controls'][$k]['error'] = isset($c['validationtext']) ? $c['validationtext'] : $e;
						$errors++;
						$e = null;
					}

				} // has a name
			} // not a fieldset
		}
		$frm['errors'] = $errors;
		return($frm);
	}

	function datasheet($frm, $where = null) {
		global $db;

		$t = tick();
		
		$database = isset($frm['db']) ? $frm['db'] : $db; 
		
		if (empty($frm['controls'])) $frm['controls'] = array();
		$flds =& $frm['controls'];
		$frm['page'] = empty($frm['page']) ? ((!empty($frm['nopager']) || empty($_GET['page'])) ? 1 : intval($_GET['page'])) : $frm['page'];
		$frm['pagesize'] = empty($frm['pagesize']) ? null : $frm['pagesize'];
		$frm['pagelinkcount'] = empty($frm['pagelinkcount']) ? null : $frm['pagelinkcount'];
		$tpl = empty($frm['tpl']) ? mem('tpl', '{first} {previous} Page {pagelist} of {pagecount} Page(s) (Showing {pagerows} of {max} Record(s) in {seconds}s) {next} {last}', 'default') : $frm['tpl'];

		if (empty($frm['source']) && empty($frm['from'])) die('missing form source');

		if (isset($frm['select'])) {
			$select =& $frm['select'];
		} else {
			$select = array(); foreach($flds as $f) if ( (!isset($f['mode']) || ($f['mode'] & 1)) && isset($f['name']) && isset($f['type']) && (!isset($f['truncate']) || !empty($f['truncate']))) {
				$select[] = !is_sql($f['source']) ? $database->quote($f['name']) : (((!empty($f['type']) && $f['type'] == 'expr') ? $f['source'] : $database->quote($f['source'])) . ' AS ' . $database->quote($f['name']));
			}
		}

		$order = array();
		if (!empty($frm['sortlinks'])) {
			$keys = array_keys($flds);
			foreach (getsort($flds, 'sort') as $c => $v) {
				$f = $flds[$keys[($c - 1)]];
				$name = !is_sql($f['source']) ? $database->quote($f['name']) : ($f['type'] == 'expr' ? $f['source'] : $database->quote($f['source']));
				$order[] = $name . ' ' . ($v > 0 ? 'ASC' : 'DESC');
			}
		}

		if (empty($order) && isset($frm['order by'])) {
			$order = $frm['order by'];
		}

		$sql = array(
			'select' => $select,
			'from' => empty($frm['from']) ? $frm['source'] : $frm['from'],
			'where' => empty($frm['where']) ? '' : $frm['where'],
			'group by' => empty($frm['group by']) ? '' : $frm['group by'],
			'having' => empty($frm['having']) ? '' : $frm['having'],
			'order by' => $order,
			'limit' => (empty($frm['limit']) ? (empty($frm['pagesize']) ? null : $database->limit($frm['pagesize'] * ($frm['page'] - 1), $frm['pagesize'])) : $frm['limit']),
		);

		$frm['rsdata'] = $database->select($sql, $flds);

		if (!empty($frm['sortlinks'])) {
			$flds = sortlinks($flds, 'sort', (!isset($frm['sortlinksmulticolumn']) || !empty($frm['sortlinksmulticolumn']))); // multicolumn on by default
			foreach($flds as $k => $f) {
				if (!empty($f['label']) && empty($f['nosortlinks']) && (!isset($f['mode']) || ($f['mode'] & 1))) {
					if (isset($f['linksort0'])) $flds[$k]['label'] .= '&nbsp;' . a(mem('linksortascimg', '&#x21E7;', 'default'), array('href'=>$f['linksort0'], 'class'=>'s0 sort-asc', 'title'=>say('Sort Ascending')));
					if (isset($f['linksort0off'])) $flds[$k]['label'] .= '&nbsp;' . a(mem('linksortoffimg', 'X', 'default'), array('href'=>$f['linksort0off'], 'class'=>'s0 sort-asc-off', 'title'=>say('Remove Sort Ascending')));
					if (isset($f['linksort1'])) $flds[$k]['label'] .= '&nbsp;' . a(mem('linksortdescimg', '&#x21E9;', 'default'), array('href'=>$f['linksort1'], 'class'=>'s0 sort-desc', 'title'=>say('Sort Descending')));
					if (isset($f['linksort1off'])) $flds[$k]['label'] .= '&nbsp;' . a(mem('linksortoffimg', 'X', 'default'), array('href'=>$f['linksort1off'], 'class'=>'s0 sort-desc-off', 'title'=>say('Remove Sort Descending')));
				}
			}
		}

		$frm['rs'] = array();
		for ($k = 0; $k < count($frm['rsdata']); $k++) $frm['rs'][$k] = display($frm['rsdata'][$k], $frm);

		/* PAGER */

		if (!empty($frm['pager']) && !empty($frm['pagesize']) && ($frm['page'] > 1 || count($frm['rs']) == $frm['pagesize'])) {
			$countexpr = empty($frm['count']) ? 'COUNT(*)' : $frm['count'];
			$sqlcount = array_merge($sql, array('select'=>$countexpr, 'limit'=>null, 'order by'=>null, 'group by'=>null));
			if (!empty($frm['countfrom'])) $sqlcount['from'] = $frm['countfrom']; // removing joined tables can speed count query
			$frm['max'] = $database->sql2one($sqlcount);

			if ($frm['max'] > $frm['pagesize']) {
				$info = pager($_GET, 'page', $frm['pagesize'], $frm['max'], $frm['pagelinkcount']);
				$info['pagerows'] = min($frm['pagesize'], count($frm['rs']));
				$info['seconds'] = round((tick() - $t) / 1000000, 2);
				$pager = populate(mem('pager', $tpl, 'default'), $info);
			} else {
				$pager = '';
			}
		} else {
			$frm['max'] = count($frm['rs']);
			$pager = '';
		}

		$frm['pager'] = $pager;


		return($frm);
	}

	function datatable($frm, $a = null) {

		if (!isset($frm['rs'])) $frm = datasheet($frm);
		if (!isset($frm['id'])) $frm['id'] = id();
		if (!isset($a['id'])) $a['id'] = $frm['id'];

		$a['class'] = addon(isset($a['class']) ? $a['class'] : null, 'table');
		$line = array();
		if (empty($frm['rs'])) $line[] = span((empty($frm['empty']) ? 'There were no records found that matched your criteria' : $frm['empty']), array('class'=>'notfound'));
		if (!empty($frm['pager'])) $line[] = $frm['pager'];
		if (!empty($frm['addlink'])) $line[] = span($frm['addlink'], array('class'=>'addlink'));

		$b = array();
		if (!empty($frm['controls'])) foreach($frm['controls'] as $f) if (isset($f['control']) && isset($f['label']) && (!isset($f['mode']) || ($f['mode'] & 1))) $b[] = th($f['label']);

		$obj = empty($frm['obj']) ? 'item' : $frm['obj'];
		$c = array();
		for ($cursor = 0; $cursor < count($frm['rs']); $cursor++) {
			$class = $obj . (empty($frm['pk']['name']) ? null : (' i' . $frm['rsdata'][$cursor][($frm['pk']['name'])]));
			$c[] = tr(datarow(array_merge($frm, compact('cursor'))), array('class'=>$class));
		}
		return(
			div(table(thead(tr(join('', $b))) . tbody(join('', $c)), $a), array('class'=>'table-responsive'))
			. (empty($line) ? null : p(join(' ', $line), array('class'=>'pager')))
		);
	}

	function datarow($frm) {
		$d = array();
		$cursor = empty($frm['cursor']) ? 0 : $frm['cursor'];
		foreach($frm['controls'] as $f) if (isset($f['control']) && isset($f['name']) && isset($f['label']) && (!isset($f['mode']) || ($f['mode'] & 1))) $d[] = td($frm['rs'][$cursor][($f['name'])]);
		return(join('', $d));
	}

	function datalist($frm, $a = null) {
		if (empty($frm['obj'])) return; // need obj for teaser call
		//if (empty($frm['teaser'])) return; // need teaser for layout
		if (!isset($frm['rs'])) $frm = datasheet($frm);
		$line = array();
		if (empty($frm['rs'])) $line[] = span((empty($frm['empty']) ? 'There were no records found that matched your criteria' : $frm['empty']), array('class'=>'notfound'));
		if (!empty($frm['pager'])) $line[] = $frm['pager'];
		if (!empty($frm['addlink'])) $line[] = span($frm['addlink'], array('class'=>'addlink'));

		$list = array();
		for ($frm['cursor'] = 0; $frm['cursor'] < count($frm['rs']); $frm['cursor']++) {
			$class = isset($frm['rsdata'][($frm['cursor'])]['id']) ? ($frm['obj'] . ' i' . $frm['rsdata'][($frm['cursor'])]['id']) : null;
			$id = isset($frm['rsdata'][($frm['cursor'])]['id']) ? ('item_' . $frm['rsdata'][($frm['cursor'])]['id']) : null;
			$list[] = li(
				(empty($frm['teaser']) ? join(' | ', $frm['rs'][($frm['cursor'])]) : run($frm['obj'], $frm['teaser'], $frm))
				, array('class'=>$class, 'id'=>$id)
			);
		}
		return(
			ol(join('', $list), $a)
			. (empty($line) ? null : p(join(' ', $line), array('class'=>'pager')))
		);
	}

	function getsort($flds, $pname) {
		$sorts = array(); // sorts[control] = +/-order
		$ns = 1; // number of sorts
		$max = count($flds);
		for ($i = 0; $i < $max; $i++) {
			if (isset($_GET[($pname . $ns)])) {
				if (($c = abs($_GET[($pname . $ns)])) > 0) {  // ignore sorts being switched off (i.e. == 0)
					$sorts[$c] = ($_GET[($pname . $ns)] > 0) ? $ns : ($ns * -1);
				}
			} else {
				break; // must be sequential
			}
			unset($_GET[($pname . $ns)]);
			$ns++;
		}
		if (!empty($sorts)) { // re-build QS
			$s = 1; foreach ($sorts as $c => $ns) $_GET[('sort' . $s++)] = ($ns > 0 ? ($c) : -($c));
		}

		return($sorts);
	}

	function sortlinks($flds, $pname = 'sort', $multicolumn = 1) {
		$sorts = getsort($flds, $pname);
		$nextsort = empty($multicolumn) ? 1 : (count($sorts) + 1); // allow sorting by more than 1 column
		if (is_array($flds)) {
			foreach (array_keys($flds) as $k => $v) {
				$c =& $flds[$v];
				$k++; // cannot have +/- 0
				if (isset($c['label'])) {
					if (empty($c['truncate']) || $c['truncate'] != '0') {
						if (isset($sorts[$k])) {
							if ($sorts[$k] > 0) {
								$c['linksort0off'] = url(array_merge($_GET, array($pname . $sorts[$k] => 0)));
								$c['linksort1'] = url(array_merge($_GET, array($pname . $sorts[$k] => (($k) * -1))));
							} else {
								$c['linksort0'] = url(array_merge($_GET, array($pname . abs($sorts[$k]) => ($k))));
								$c['linksort1off'] = url(array_merge($_GET, array($pname . abs($sorts[$k]) => 0)));
							}
						} else {
							$c['linksort0'] = url(array_merge($_GET, array($pname . $nextsort => ($k))));
							$c['linksort1'] = url(array_merge($_GET, array($pname . $nextsort => (($k) * -1))));
						}
					}
				}
			}
			return($flds);
		} else {
			return(array());
		}
	}

	function sortorder($flds, $pname = 'sort') {
		$uorders = array();
		$keys = array_keys($flds);
		foreach (getsort($flds, $pname) as $c => $v) {
			$uorders[] = array($flds[$keys[($c - 1)]]['name'], ($v > 0 ? 'ASC' : 'DESC'));
		}
		return($uorders);
	}

	function populate($s, $a) {
		/* populate {key} tags in $s with values from array $a */
		if (preg_match_all('/{(\w+)}/', $s, $b)) {
			foreach ($b[1] as $k => $v) {
				$t = isset($a[$v]) ? $a[$v] : '';
				$s = str_replace($b[0][$k], $t, $s);
			}
		}
		return($s);
	}

	function load($obj, $cmd) {
		/* load PHP file, run info function and return array() */
		$obj = slug($obj);
		$cmd = slug($cmd);
		if (strlen($obj) && strlen($cmd)) {
			$f = str_replace(array('-', '.'), '_', $obj) . '_' . str_replace(array('-', '.'), '_', $cmd);
			if (!function_exists($f)) {
				ob_start();
				include_once($obj . DIRECTORY_SEPARATOR . $cmd . '.php');
				ob_end_clean();
			}
			if (function_exists($f)) {
				$fi = $f . '_info';
				$info = (function_exists($fi) ? $fi() : array());
				$info['function'] = $f;
				return($info);
			}
		}
		return(array());
	}

	function run($obj, $cmd, $a = null, $entrypoint = 0, $api = 0) {

		global $argc;

		if ($info = load($obj, $cmd)) {
			if (!empty($info['auth']) && ($auth = mem('user.auth', 0, 'default')) < $info['auth']) {
				if ($auth) {
					$error = 'Sorry, you do not have the required level of authorisation.';
					$redirect = url(array());
				} else {
					session('redirect', url($_GET), 'append');
					$error = 'Sorry, you are not authorised. Please log in first.';
					$redirect = url(array('obj'=>'user', 'cmd'=>'login'));
				}
			}
			if (isset($info['title'])) mem('title', $info['title']);
			if (isset($info['param']) && is_array($info['param'])) {
				foreach($info['param'] as $pi) { // $pi = array('name', 'type', 'default', 'allowed')
					if (isset($a[$pi[0]])) {
						if ($pi[1] == 'number' && !is_numeric($a[$pi[0]])) die('expected numeric parameter: ' . $pi[0]);
					} else {
						if (isset($pi[2])) { // default
							$a[$pi[0]] = $pi[2];
						} else {
							die('missing required parameter: ' . $pi[0]);
						}
					}
				}
			}
		} else {
			$notfound = 1;
		}

		if (empty($error) && empty($notfound) && empty($redirect)) {
			if (empty($argc)) {
				ob_start();
				$html = $info['function']($a);
				if (strlen($x = ob_get_contents())) $html = $x;
				ob_end_clean();
				if ((!isset($info['comment']) || $info['comment']) && is_string($html) && mem('comments')) $html = comment('start: ' . $obj . '/' . $cmd . '.php') . (!empty($info['div']) || !isset($info['div']) ? div($html, array('class'=>($obj . ' ' . $cmd))) : $html) . comment('end: ' . $obj . '/' . $cmd . '.php');
			} else {
				// no buffering if run from command line
				$html = $info['function']($a);
			}
		}


		if (empty($info['api']) || is_string($html)) {

			if ($entrypoint) {

				if (!empty($error)) flash($error);
				if (!empty($redirect)) redirect($redirect);
				if (!empty($notfound)) return(notfound($a));

				return($html);
			} else {

				if (!empty($error)) flash($error);
				if (!empty($redirect)) redirect($redirect);
				if (!empty($notfound)) return(mem('comments') ? comment('missing: ' . $obj . '/' . $cmd . '.php') : null);
				return($html);
			}
		} else {

			if ($entrypoint) {
				if (empty($api)) {
					if (!empty($error)) flash($error);
					if (!empty($redirect)) redirect($redirect);
					if (!empty($notfound)) return(notfound($a));

					if (!empty($html['info'])) flash($html['info']);
					if (!empty($html['error'])) flash($html['error']);
					if (!empty($html['redirect'])) redirect($html['redirect']);
					if (!empty($html['notfound'])) return(notfound($a));
					if (!empty($html['html'])) return($html['html']);
				} else {
					mem('raw', 1);
					if (empty($error) && empty($notfound) && empty($redirect)) {
						$html['title'] = mem('title');
						return(json_encode($html));
					} else {
						return(json_encode(compact('error', 'notfound', 'redirect')));
					}
				}
			} else {
				if (!empty($error)) return($error);
				if (!empty($notfound)) return('notfound');
				if (!empty($html)) return($html);
				return;
			}
		}
	}


	if (!function_exists('notfound')) {
		function notfound($a = null) {
			header("HTTP/1.1 404 Not Found");
			ob_get_contents() && ob_end_clean();
			echo 'Not Found';
			exit;
		}
	}

	function pager($a, $p = 'page', $pagesize = 0, $max = 0, $numlinks = null) {

		if (!is_array($a)) $a = array();

		if ($pagesize < 1) $pagesize = (($max > 0) ? $max : 1);  /* interval my be zero for no page limit */

		$page = empty($a[$p]) ? 1 : max(1, intval($a[$p]));

		$b = pagerinfo($pagesize, $max, $page, $numlinks);

		$b['first'] = $b['showfirst'] ? a('First', array('class'=>'p0', 'href'=>url(array_merge($a, array($p=>$b['pagefirst']))))) : 'First';
		$b['previous'] = $b['showprevious'] ? a('Previous', array('class'=>'p0', 'href'=>url(array_merge($a, array($p=>$b['pageprevious']))))) : 'Previous';
		$b['prev'] = $b['previous'];
		$b['next'] = $b['shownext'] ? a('Next', array('class'=>'p0', 'href'=>url(array_merge($a, array($p=>$b['pagenext']))))) : 'Next';
		$b['last'] = $b['showlast'] ? a('Last', array('class'=>'p0', 'href'=>url(array_merge($a, array($p=>$b['pagelast']))))) : 'Last';
		$c = array();
		foreach ($b['pages'] as $info) {
			$c[] = empty($info['iscurrent']) ? a($info['label'], array('class'=>'p0', 'href'=>url(array_merge($a, array($p=>$info['pagenumber']))))) : $info['label'];
		}
		$b['pagelist'] = join(' ', $c);
		return($b);
	}

	function pagerinfo($pagesize = 10, $max = 0, $page = 1, $numlinks = null) {
		$a = array();
		$pages = array();
		$pagesize = $pagesize < 1 ? 1 : $pagesize;
		$pagecount = $pagesize > 0 ? (ceil($max/$pagesize) > 0 ? ceil($max/$pagesize) : 1) : 0;
		$startpage = ($page > floor($numlinks / 2) && $numlinks >= 0) ? ($page - floor($numlinks / 2)) : 1;

		for ($i = $startpage; $i <= $pagecount && (!isset($numlinks) || $i < ($startpage + abs($numlinks))); $i++) {
			$pagenumber = ($i == 1) ? null : $i;
			$label = $i;
			$iscurrent = $page == $i ? 1 : 0;
			$pages[] = compact('pagenumber', 'iscurrent', 'label');
		}


		$a = compact('pagesize', 'max', 'page', 'numlinks', 'pagecount');
		$a['pagefirst'] = null; // found but not used in url
		$a['showfirst'] = ($page <= 1) ? 0 : 1;
		$a['pageprevious'] = ($page <= 2) ? null : ($page - 1);
		$a['showprevious'] = ($page <= 1) ? 0 : 1;
		$a['pagenext'] = (($page) >= $pagecount) ? null : ($page + 1);
		$a['shownext'] = (($page) >= $pagecount) ? 0 : 1;
		$a['pagelast'] = ($page >= $pagecount) ? null : $pagecount;
		$a['showlast'] = ($page >= $pagecount) ? 0 : 1;
		$a['pages'] = count($pages) < 1 ? array() : $pages;

		return($a);
	}

	function say($m, $any = null, $one = null, $none = null) {
		if (isset($any) && is_array($m)) $m = populate($any, $m);
		return($m);
	}


	function file_append_contents($p, $d, $m = 'a') {
		if ($h = fopen($p, $m)) {
			fwrite($h, $d);
			return(fclose($h));
		}
	}

	function qs($a) {
		if (is_array($a)) $a['obj'] = $a['cmd'] = null;
		$b = array();
		if (count($a) > 0) {
			ksort($a);
			foreach($a as $k=>$v) {
				if (is_array($v)) {
					foreach($v as $k1=>$v1) if ($v1 !== null) $b[] = $k . '[' . urlencode($k1) . ']=' . urlencode($v1);
				} else {
					if ($v !== null) $b[] = urlencode($k) . '=' . urlencode($v);
				}
			}
		}
		return(empty($b) ? null : ('?' . join('&', $b)));
	}

	function url($a = array(), $secure = null, $site = null) {
		global $db, $request;
		static $cache = array();

		if (!is_array($a)) $a = array();

		$path = array();
		if (isset($a['obj'])) $path[] = $a['obj'];
		if (isset($a['cmd'])) $path[] = $a['cmd'];

		if (isset($a['obj']) && isset($a['cmd'])) {
			$info = load($a['obj'], $a['cmd']);
			if (isset($info['param'])) {
				foreach($info['param'] as $v) {
					if (!empty($a[($v[0])])) {
						$path[] = $a[($v[0])];
						unset($a[($v[0])]);
					} else {
						break;
					}
				}
			}
		}
		unset($a['obj']);
		unset($a['cmd']);

		if (isset($request['protocol']) && $request['protocol'] == 'https' && !isset($secure)) $secure = 1;

		if (!empty($site) && $site != mem('site.id')) {
			if (!isset($cache[$site])) {
				if ($rs = $db->sql2array('SELECT id, host, fqdn, ssl FROM site WHERE id = ' . $site)) {
					$cache[$site] = $rs[0];
				} else {
					$cache[$site] = 0;
				}
			}
			$siteinfo = empty($cache[$site]) ? mem('site') : $cache[$site];
		} else {
			$siteinfo = mem('site', array('fqdn'=>(empty($request['host']) ? 'localhost' : $request['host'])), 'default');
		}

		$host = ($secure && !empty($siteinfo['ssl']) ? $siteinfo['ssl'] : (empty($siteinfo['fqdn']) ? ($siteinfo['host'] . '.' . $siteinfo['parenthost']) : $siteinfo['fqdn'])) . mem('site.suffix');
    $host .= (empty($request['port']) ? null : (':' . $request['port']));
		return ('http' . ($secure ? 's' : '') . '://' . $host . mem('urlprefix', DIRECTORY_SEPARATOR, 'default') . join(DIRECTORY_SEPARATOR, $path) . (empty($a) ? null : qs($a)));
	}

	function redirect($s = null) {
		global $argv;
		if (!isset($s)) $s = $_GET;
		if (is_array($s)) $s = url($s);
		if (!mem('redirect.off')) {
  		if (isset($argv)) {
  			echo ('Location: ' . $s);
  		} else {
  			header('Location: ' . $s);
  			ob_end_clean();
  		}
  		exit;
  	}
		
	}

	function dispatch($path) {

		// Set/validate global $_GET array with values from path based on schema

		$bits = array();
		if (isset($path) && $path !== '/') {
			$bits = explode(DIRECTORY_SEPARATOR, $path);
			array_shift($bits); // path begins with DIRECTORY_SEPARATOR
			if (isset($bits[0])) $_GET['obj'] = array_shift($bits);
			if (isset($bits[0])) $_GET['cmd'] = array_shift($bits);
		}

		if (!isset($_GET['obj']) && isset($_POST['obj'])) $_GET['obj'] = $_POST['obj'];
		if (!isset($_GET['cmd']) && isset($_POST['cmd'])) $_GET['cmd'] = $_POST['cmd'];

		if (isset($_GET['obj']) && !isset($_GET['cmd'])) {
			$_GET['path'] = $_GET['obj'];
			$_GET['obj'] = 'article';
			$_GET['cmd'] = 'display';
		}

		$_GET['obj'] = isset($_GET['obj']) ? $_GET['obj'] : mem('obj');
		$_GET['cmd'] = isset($_GET['cmd']) ? (empty($_GET['cmd']) ? 'index' : $_GET['cmd']) : mem('cmd');

		$fi = $_GET['obj'] . '_' . $_GET['cmd'] . '_info';
		if (!function_exists($fi)) load($_GET['obj'], $_GET['cmd']);
		if (function_exists($fi)) {
			$info = $fi();
			if (isset($info['param']) && is_array($info['param'])) {
				foreach($info['param'] as $k => $pi) { // $pi = array('name', 'type', 'default', 'allowed')
					if (count($bits) > 0) {
						if ($pi[1] == 'number' && !is_numeric($bits[0])) {
							// use default
							break;// what about the default value in this case?
						} else if (isset($_GET[$pi[0]])) {
							// let QS value override path value if it is valid, otherwise use path value
							break;
						} else {
							$_GET[$pi[0]] = array_shift($bits);
						}
					} else {
						if (isset($pi[2])) { // default value
							if (isset($_GET[$pi[0]])) {
								if ($pi[1] == 'number' && !is_numeric($_GET[$pi[0]])) $_GET[$pi[0]] = $pi[2]; // set to default if invalid
							} else {
								$_GET[$pi[0]] = $pi[2]; // set to default if missing in $_GET
							}
						} else {
							if ((isset($_GET[$pi[0]]) && $pi[1] == 'number' && !is_numeric($_GET[$pi[0]])) || (!isset($_GET[$pi[0]]))) die('missing or invalid parameter: ' . $pi[0]);
						}
					}
				}
			}
		}
	}

?>
