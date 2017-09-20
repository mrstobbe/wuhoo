<?php

abstract class Action {
	static protected $actionMap = [ ];

	public $app = null;

	public $handler = null;

	public $uri = null;
	public $path = null;
	public $query = null;
	public $format = null;
	public $action = null;
	public $params = [ ];

	public $respCode = null;
	public $respError = null;
	public $respResult = null;
	public $view = null;


	public function __construct(App $app, array $reqInfo) {
		$this->app = $app;

		$this->handler = $reqInfo['handler'];

		$this->uri = $reqInfo['uri'];
		$this->path = $reqInfo['path'];
		$this->query = $reqInfo['query'];
		$this->format = $reqInfo['fmt'];
		$this->action = $reqInfo['action'];
		$this->params = $reqInfo['params'];
	} //__construct()

	abstract public function execute();


	protected function respSuccess(array $data = null) {
		$this->respCode = 200;
		$this->respResult = $data;
		return $this;
	} //respSuccess()

	protected function respError($code, $msg) {
		$this->respCode = $code;
		$this->respError = $msg;
		return $this;
	} //respError()

	protected function respNotFound() {
		return $this->respError(404, 'Unknown request');
	} //respNotFound()

	protected function respBadParam($msg) {
		return $this->respError(418, $msg);
	} //respBadParam()

	public function param($key, $default = null) {
		return (isset($this->params[$key])) ? $this->params[$key] : $default;
	} //param()

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
			if ($sub === '')
				break;
			if (is_file($actDir . $sub . '.act.php')) {
				$action = $sub;
				$actFile = $actDir . $sub . '.act.php';
			} elseif (is_file($actDir . $sub . '/default.act.php')) {
				$action = $sub . '/default';
				$actFile = $actDir . $sub . '/default.act.php';
			}
			if ($actFile !== null)
				$paramsIdx = $n - $i;
		} //for($parts)

		if ($actFile === null) {
			$action = 'default';
			$paramsIdx = 0;
			$actFile = $actDir . 'default.act.php';
		}
		$actFile = realpath($actFile);
		if (!isset(self::$actionMap[$actFile])) {
			require $actFile;
			if (!isset(self::$actionMap[$actFile]))
				throw new Exception("Action '" . $action . "' misconfigured");
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
		if (($req['query'] !== null) && ($req['query'] !== '?')) {
			$parts = explode('&', substr($req['query'], 1));
			foreach ($parts as $part) {
				$key = null;
				$val = null;
				if (($p = strpos($part, '=')) === false) {
					$key = trim(rawurldecode($part));
					$val = true;
				} else {
					$key = trim(rawurldecode(substr($part, 0, $p)));
					$val = rawurldecode(substr($part, $p + 1));
				}
				if ($key !== '')
					$params[$key] = $val;
			}
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
