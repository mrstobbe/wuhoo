<?php

//#NB: This should be a "namespace", but I hate PHP namespaces

class Util {
	static function htmlEnc($s) {
		return htmlentities($s, ENT_COMPAT | ENT_HTML5, 'UTF-8');
	} //htmlEnc()

	static function merge(array &$dest, $src) {
		if ((!is_array($src)) && (!is_object($src)))
			throw new Exception("Cannot merge something that is not a container of key-value pairs");
		foreach ($src as $k=>$v) {
			if ((is_array($v)) || (is_object($v))) {
				if ((!isset($dest[$k])) || (!is_array($dest[$k])))
					$dest[$k] = [ ];
				self::merge($dest[$k], $v);
			} else {
				$dest[$k] = $v;
			}
		} //foreach($src)
	} //merge()
}; //class Util

return;
?>
