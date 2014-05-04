<?php
if(php_sapi_name() !== "cli") {
	exit();
}

$files = array('ks.txt', 'credits.txt', 'names.txt', 'first.txt');

$markov = array("   " => array());

function clean_str($str) {
	$str = preg_replace('~[^\p{L} \-]++~u', '', $str);
	$str = preg_replace('~[0-9]+~u', '', $str);
	$str_parts = explode(" ", $str);
	$str_parts = array_reduce($str_parts, function($carry, $s) {
		$s = trim(ucfirst(strtolower($s)));
		if(strlen($s) < 3) {
			return $carry;
		}
		$carry[] = $s;
		return $carry;
	}, array());
	$str = implode(" ", $str_parts);
	return trim($str);
}

foreach($files as $file) {
	$handle = fopen($file, "r");
	if($handle) {
		while (($line = fgets($handle)) !== false) {
			$str = clean_str($line);
			if(empty($str)) {
				continue;
			}
			$parts = preg_split('/(?<!^)(?!$)/u', $str);
			$parts[] = '$';
			$last_part = "   ";
			foreach($parts as $part) {
				if(!isset($markov[$last_part][$part])) {
					$markov[$last_part][$part] = 1;
				} else {
					$markov[$last_part][$part]++;
				}
				$last_part_chars = preg_split('/(?<!^)(?!$)/u', $last_part);
				$ultimate = array_pop($last_part_chars);
				$penultimate = array_pop($last_part_chars);
				$last_part = $penultimate.$ultimate.$part;
			}
		}
	}
	fclose($handle);
}

foreach($markov as $start => $next) {
	$total = 0;
	foreach($next as $key => $count) {
		$total += $count;
		$markov[$start][$key] = $total;
	}
	$markov[$start] = array_flip($markov[$start]);
//	krsort($markov[$start]);
//	$markov[$start]['total'] = $total;
} 

file_put_contents("markov.json", json_encode($markov, JSON_PRETTY_PRINT));
