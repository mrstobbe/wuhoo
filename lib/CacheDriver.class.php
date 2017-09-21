<?php

abstract class CacheDriver {

	static protected $driverMap = [ ];
	static protected $allByID = [ ];
	static protected $enabled = null;

	public $app = null;

	public function __construct(App $app) {
		$this->app = $app;
	} //__construct()

	public function garbageCollect() {
		//Do nothing unless the driver overrides
	}

	abstract public function exists($key);
	abstract public function get($key, $default = null);
	abstract public function set($key, $value, $ttl = null);
	abstract public function remove($key);

	static public function getDriver(App $app) {
		$handler = self::$enabled;
		return ($handler === null) ? new NullCache($app) : new $handler($app);
	} //getDriver()

	static public function loadAll(App $app, $path) {
		if ($path[strlen($path) - 1] !== '/')
			$path .= '/';
		$entries = scandir($path);
		foreach ($entries as $entry) {
			if ($entry[0] === '.')
				continue;
			$fp = realpath($path . $entry);
			if ((is_file($fp)) && (substr($fp, -10) === '.cache.php')) {
				if (!isset(self::$driverMap[$fp])) {
					require $fp;
					if (!isset(self::$driverMap[$fp]))
						throw new Exception('Cache driver misconfigured');
					$handler = self::$driverMap[$fp];
					//#TODO: sanity check that it inherits ServiceDriver
					if (isset(self::$allByID[$handler::$id]))
						throw new Exception('Cache driver id is not unique');
					self::$allByID[$handler::$id] = $handler;
					if (($handler::available($app)) && ((int)$app->conf($handler::$id . '.enabled', false))) {
						if (self::$enabled !== null)
							throw new Exception('Multiple cache drivers may not be enabled at the same time');
						self::$enabled = $handler;
					}
				}
			} //if(good file)
		}//foreach(files)
	} //loadAll()

	static public function register($classname, $filename) {
		self::$driverMap[$filename] = $classname;
	} //register()

	static public function available(App $app) {
		return true;
	} //available()
} //class CacheDriver
