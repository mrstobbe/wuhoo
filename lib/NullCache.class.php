<?php

class NullCache extends CacheDriver {

	public function exists($key) {
		return false;
	} //exists()

	public function get($key, $default = null) {
		return $default;
	} //get()

	public function set($key, $value, $ttl = null) {
		return;
	}//set()

	public function remove($key) {
		return false;
	}//remove()
} //class NullCache

return;
?>
