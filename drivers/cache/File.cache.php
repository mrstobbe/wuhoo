<?php

//#TODO: Enable system-wide read/write locking
class FileCache extends CacheDriver {
	static public $id = 'filecache';
	static public $name = 'Filesystem cache';

	protected $dir = null;

	public function __construct(App $app) {
		parent::__construct($app);

		//#TODO: support windows paths
		if (($this->dir = (string)$this->app->conf('filecache.path')) === '')
			throw new Exception('File cache path not configured');
		if ($this->dir[0] !== '/')
			throw new Exception('Refusing to use non-fully qualified path for cache storage');
		if ($this->dir[strlen($this->dir) - 1] !== '/')
			$this->dir .= '/';
		if ((!file_exists($this->dir)) && (!mkdir($this->dir, 0770, true)))
			throw new Exception('Could not ensure the cache directory exists');
		if (file_put_contents($this->dir . '.test-write', 'test') === false)
			throw new Exception('Failed to write to specified cache file storage path');
		@unlink($this->dir . '.test-write');
	}

	public function keyToPath($key) {
		return $this->dir . md5($key) . '.cache';
	}

	protected function fetchEntry($key) {
		$fp = $this->keyToPath($key);
		if (!is_file($fp))
			return null;
		$data = unserialize(file_get_contents($fp));
		if (!isset($data['key']))
			return null;
		return (($data['key'] === $key) && ($data['expires'] === null) || (time() < $data['expires'])) ? $data : null;
	} //fetchEntry()

	public function exists($key) {
		return ($this->fetchEntry($key) !== null);
	} //exists()

	public function get($key, $default = null) {
		if (($data = $this->fetchEntry($key)) === null)
			return $default;
		return $data['value'];
	} //get()

	public function set($key, $value, $ttl = null) {
		$data = [
			'key'=>$key,
			'expires'=>($ttl === null) ? null : time() + $ttl,
			'value'=>$value
		];
		file_put_contents($this->keyToPath($key), serialize($data));
	} //set()

	public function remove($key) {
		$fp = $this->keyToPath($key);
		if (is_file($fp))
			return unlink($fp);
		return false;
	} //remove()
} //class FileCache

CacheDriver::register('FileCache', __FILE__);

return;
?>
