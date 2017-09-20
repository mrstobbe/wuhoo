<?php

class App {
	static public $instance = null;

	protected $startTime = null;	
	public $appDir = null;
	public $isCLI = false;
	public $confMap = null;


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
		//$this->loadDrivers();
	} //__construct()

	static public function init($appDir = null) {
		if ($appDir === null)
			$appDir = dirname(__FILE__) . '/..';
		return new self($appDir);
	} //init()

	//#TODO: In a more complex app, `run()` would be controllable via parameters
	public function run() {
		if ($this->isCLI) {
			//#TODO: enable a generic command-line interface
			throw new Exception('No command-line interface available');
		}
		if (!isset($_SERVER['REQUEST_URI']))
			throw new Exception('`REQUEST_URI` not set. Hmmmm... server config issues.');


		//$resp = $this->dispatch($_SERVER["REQUEST_URI"]);
		$req = Action::resolve($this, $_SERVER['REQUEST_URI']);
		header("Content-Type: text/plain");
		var_dump($req);
		while (ob_get_level())
			ob_end_flush();
	} //run()

	public function conf($key, $default = null) {
		$parts = split('.', $key);
		$ref = &$this->confMap;
		for ($i = 0, $n = count($parts) - 1; $i !== $n; ++$i) {
			if ((!isset($ref[$parts[$i]])) || (!is_array($ref[$parts[$i]])))
				return $default;
			$ref = &$ref[$parts[$i]];
		}
		$skey = $parts[count($parts) - 1];
		return (isset($ref[$skey])) ? $ref[$skey] : $default;
	} //conf()

	protected function loadCore() {
		require $this->appDir . 'lib/Util.class.php';
		require $this->appDir . 'lib/Action.class.php';
		/*
		require $this->appDir . 'lib/CacheDriver.class.php';
		require $this->appDir . 'lib/ServiceDriver.class.php';
		*/
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
} //class App

return;
?>
