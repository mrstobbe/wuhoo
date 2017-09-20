<?php

abstract class Action {
	static protected $actionMap = [ ];

	public $req = null;
	public $resp = null;
	public $cache = null;



	abstract public function execute();

	static public function decodeReqURI($reqURI) {
		$fmt = null;
		$path = null;
		$query = null;
		if (($p = strpos($reqURI, '?')) !== false) {
			$path = substr($reqURI, 0, $p);
			$query = substr($reqURI, $p);
		} else {
			$path = $reqURI;
		}
		if (preg_match('`\.([a-z0-9]+)$`', $path, $m)) {
			$fmt = $m[1];
			$path = substr($path, 0, strlen($path) - (strlen($fmt) + 1));
		}
		return [
			'uri'=>$reqURI,
			'path'=>$path,
			'query'=>$query,
			'fmt'=>$fmt
		];
	} //decodeReqURI()

	static public function resolve(App $app, $reqURI) {
		$req = self::decodeReqURI($reqURI);
		$actDir = $app->appDir . 'actions/';

		//#TODO: cannonize path... for the sake of moving forward, I'm just going to ignore anything starting with a dot and other weirdness (see `makeSubPath()`)
		$action = null;
		$actFile = null;
		$paramsIdx = null;
		$parts = explode('/', $req['path']);
		for ($i = 0, $n = count($parts); ($actFile === null) && ($i !== $n); ++$i) {
			$sub = self::makeSubPath($parts, $n - $i);
			if (is_file($actDir . $sub . '.act.php')) {
				$actFile = $actDir . $sub . '.act.php';
			} else if (is_file($actDir . $sub . '/default.act.php')) {
				$actFile = $actDir . $sub . '/default.act.php';
			}
			if ($actFile !== null) {
				$action = $sub;
				$paramsIdx = $n - $i;
			}
		} //for($parts)

		if ($actFile === null)
			throw new Exception('No default action handler');
		$actFile = realpath($actFile);
		if (!isset(self::$actionMap[$actFile])) {
			require $actFile;
			if (!isset(self::$actionMap[$actFile]))
				throw new Exception("Action misconfigured");
		}
		$params = [ ];
		for ($i = $paramsIdx, $n = count($parts); $i !== $n; ++$i) {
			$key = null;
			$val = null;
			if (($p = strpos($parts[$i], '=')) === false) {
				$key = trim(rawurldecode($parts[$i]));
				$val = true;
			} else {
				$key = trim(rawurldecode(substr($parts[$i], 0, $p)));
				$val = rawurldecode(substr($parts[$i], $p + 1));
			}
			if ($key !== '')
				$params[$key] = $val;
		}
		$req['action'] = $action;
		$req['params'] = $params;
		$req['handler'] = self::$actionMap[$actFile];
		return $req;
	} //resolve()

	static public function register($className, $filename) {
		self::$actionMap[$filename] = $className;
	} //register()

	static protected function makeSubPath(array $parts, $n) {
		$res = [ ];
		for ($i = 0; $i !== $n; ++$i) {
			$s = trim(rawurldecode($parts[$i]));
			if (($s === '') || ($s[$i] === '.') || (strpos($s, '/') !== false))
				continue;
			$res[] = $s;
		}
		return join('/', $res);
	} //makeSubPath()
} //class Action

return;
?>
