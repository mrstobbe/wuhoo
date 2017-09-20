<?php

abstract class ServiceDriver {
	static protected $driverMap = [ ];
	static protected $allByID = [ ];
	static protected $availByID = [ ];

	public $app = null;

	public function __construct(App $app) {
		$this->app = $app;
	} //__construct()

	static public function getService(App $app, $id) {
		if (!isset(self::$availByID[$id]))
			return null;
		$handler = self::$availByID[$id];
		return new $handler($app);
	} //getService()

	static public function loadAll(App $app, $path) {
		if ($path[strlen($path) - 1] !== '/')
			$path .= '/';
		$entries = scandir($path);
		foreach ($entries as $entry) {
			if ($entry[0] === '.')
				continue;
			$fp = realpath($path . $entry);
			if ((is_file($fp)) && (substr($fp, -12) === '.service.php')) {
				if (!isset(self::$driverMap[$fp])) {
					require $fp;
					if (!isset(self::$driverMap[$fp]))
						throw new Exception('Service driver misconfigured');
					$handler = self::$driverMap[$fp];
					//#TODO: sanity check that it inherits ServiceDriver
					if (isset(self::$allByID[$handler::$id]))
						throw new Exception('Service driver id is not unique');
					self::$allByID[$handler::$id] = $handler;
					if (($handler::available($app)) && ((int)$app->conf($handler::$id . '.enabled', true)))
						self::$availByID[$handler::$id] = $handler;
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

	static public function getList() {
		$res = [ ];
		foreach (self::$availByID as $id=>$handler)
			$res[$id] = $handler::$name;
		return $res;
	} //getList()
} //class ServiceDriver

return;
?>
