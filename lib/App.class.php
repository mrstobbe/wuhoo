<?php

class App {
	static public $instance = null;

	protected $startTime = null;	
	public $appDir = null;
	public $isCLI = false;
	public $confMap = null;

	public $cacher = null;

	static public $mimeMap = [
		'html'=>'text/html; charset=UTF-8',
		'txt'=>'text/plain; charset=UTF-8',
		'xml'=>'application/xml; charset=UTF-8',
		'json'=>'application/json; charset=UTF-8'
	];


	protected function __construct($appDir) {
		if (self::$instance !== null)
			throw new Exception('class App is a singleton');

		$this->startTime = microtime(true);
		ob_start();

		$appDir = realpath($appDir);
		//#TODO: sanity-check appDir
		if ($appDir[strlen($appDir) - 1] !== '/')
			$appDir .= '/';
		$this->appDir = $appDir;
		self::$instance = $this;

		$this->isCLI = (php_sapi_name() === 'cli');
		//#NB: If `!isCLI` we assume that we're running as a web-app

		//#TODO: ensure errors are set to extremely strict (aka, E_NOTE is E_ERROR, etc)
		//#TODO: setup global exception/error handler and what-not

		$this->loadCore();
		$this->loadConfig();
		$this->loadDrivers();

		$this->cacher = CacheDriver::getDriver($this);
	} //__construct()

	static public function init($appDir = null) {
		if ($appDir === null)
			$appDir = dirname(__FILE__) . '/..';
		return new self($appDir);
	} //init()

	public function conf($key, $default = null) {
		$parts = explode('.', $key);
		$ref = &$this->confMap;
		for ($i = 0, $n = count($parts) - 1; $i !== $n; ++$i) {
			if ((!isset($ref[$parts[$i]])) || (!is_array($ref[$parts[$i]])))
				return $default;
			$ref = &$ref[$parts[$i]];
		}
		$skey = $parts[count($parts) - 1];
		return (isset($ref[$skey])) ? $ref[$skey] : $default;
	} //conf()

	//#TODO: In a more complex app, `run()` would be controllable via parameters
	public function run() {
		if ($this->isCLI) {
			//#TODO: enable a generic command-line interface
			throw new Exception('No command-line interface available');
		}
		if (!isset($_SERVER['REQUEST_URI']))
			throw new Exception('`REQUEST_URI` not set. Hmmmm... server config issues.');

		$resp = $this->dispatch($_SERVER['REQUEST_URI']);

		/*
		header('Content-Type: text/plain');
		print_r($resp);
		exit(0);
		*/

		$handler = $resp->handler;
		//#TODO: Support If-None-Matched, etc responding with 304

		if (($fmt = $resp->format) === null)
			$fmt = $handler::$defaultFormat;

		$viewID = null;
		if ($resp->respCode !== 200) {
			$viewID = 'error-' . $resp->respCode;
		} elseif ($resp->view !== null) {
			$viewID = $resp->view;
		} else {
			$viewID = $resp->action;
		}
		$content = $this->render($resp, $viewID, $fmt);
		if ($resp->respCode !== 200)
			header('HTTP/1.1 ' . $resp->respCode . ' ' . $resp->respError);
		//#TODO: Support things like dynamically gzipping
		//#TODO: Support client-side caching headers
		header('Content-Length: ' . strlen($content));
		header('Content-Type: ' . self::$mimeMap[$fmt]);
		header(sprintf('X-Wuhoo-Runtime: %.2fms', (microtime(true) - $this->startTime) * 1000));
		while (ob_get_level())
			ob_end_clean();
		echo $content;
		exit(0);
	} //run()

	public function render(Action $resp, $view, $format) {
		//#TODO: More resolution options such as format fallbacks and localization
		$viewFile = $this->appDir . 'views/' . $view . '.' . $format . '.view.php';
		if (!is_file($viewFile)) {
			if ($format === 'json') {
				$res = [
					'statusCode'=>$resp->respCode,
					'status'=>($resp->respCode === 200) ? 'okay' : 'error',
					'cached'=>$resp->fromCache
				];
				if ($resp->respCode !== 200)
					$res['error'] = $resp->respError;
				if ($resp->respResult !== null)
					$res['result'] = $resp->respResult;
				return json_encode($res);
			} else {
				//#TODO: Fallback on rendering 404 (or similar), preferably in the requested format
				throw new Exception("View '" . $view . "' (" . $format . ") not found");
			}
		}
		return self::privateInclude($viewFile, [
			'app'=>$this,
			'resp'=>$resp,
			'view'=>$view,
			'format'=>$format
		]);
	} //render()

	public function dispatch($reqURI) {
		$reqInfo = Action::resolve($this, $reqURI);
		$handler = $reqInfo['handler'];
		$req = new $handler($this, $reqInfo);
		$cacheKey = $req->cacheKey();
		$canCache = (($handler::$caching['server'] !== false) && ($cacheKey !== null));
		if (($canCache) && (($resp = $this->cacheGet($cacheKey)) !== null)) {
			$req->respSuccess($resp, true);
			return $req;
		}
		$req->execute();
		if (($canCache) && ($req->respCode === 200))
			$this->cacheSet($cacheKey, $req->respResult, ($handler::$caching['server'] === true) ? null : $handler::$caching['server']);
		return $req;
	} //reqURI

	public function cacheExists($key) {
		return $this->cacher->exists($key);
	} //cacheExists()

	public function cacheGet($key, $default = null) {
		return $this->cacher->get($key, $default);
	} //cacheGet()

	public function cacheSet($key, $value, $ttl = null) {
		return $this->cacher->set($key, $value, $ttl);
	} //cacheSet()

	public function cacheRemove($key) {
		return $this->cacher->remove($key);
	} //cacheRemove($key)

	static protected function privateInclude($__filename, array $__locals = [ ]) {
		foreach ($__locals as $__k => $__v)
			$$__k = $__v;
		ob_start();
		require $__filename;
		$res = ob_get_contents();
		ob_end_clean();
		return $res;
	} //privateInclude()

	protected function loadCore() {
		require $this->appDir . 'lib/Util.class.php';
		require $this->appDir . 'lib/Action.class.php';
		require $this->appDir . 'lib/CacheDriver.class.php';
		require $this->appDir . 'lib/NullCache.class.php';
		require $this->appDir . 'lib/ServiceDriver.class.php';
	} //loadCore()

	protected function loadConfig() {
		//#TODO: Enable pulling the final config from a cache
		$conf = [ ];
		$confDir = $this->appDir . 'etc/';
		//#TODO: Enable cascading environment configs (such as "dev", "staging", etc)
		$this->loadConfigFile($confDir . 'wuhoo.conf', $conf);
		$subConfDir = $confDir . 'wuhoo.d/';
		if (is_dir($subConfDir))
			$this->recurseLoadConfig($subConfDir, $conf);
		$this->confMap = $conf;
	} //loadConfig()

	protected function loadConfigFile($filename, array &$res) {
		if (($entries = parse_ini_file($filename, true)) === false)
			throw new Exception("Error parsing configuration file '" . $filename . "'");
		Util::merge($res, $entries);
	} //loadConfigFile()

	protected function recurseLoadConfig($dir, array &$res) {
		$entries = scandir($dir);
		foreach ($entries as $entry) {
			if (substr($entry, 0, 1) === '.')
				continue;
			$fp = $dir . $entry;
			if ((is_file($fp)) && (substr($entry, -5) === '.conf')) {
				$this->loadConfigFile($fp, $res);
			} elseif (is_dir($fp)) {
				$this->recurseLoadConfig($fp . '/', $res);
			}
		}
	}

	protected function loadDrivers() {
		ServiceDriver::loadAll($this, $this->appDir . 'drivers/service/');
		CacheDriver::loadAll($this, $this->appDir . 'drivers/cache/');
	} //loadDrivers()
} //class App

return;
?>
