<?php

namespace IFW\Cache;

use IFW;
use IFW\Cache\CacheInterface;


/**
 * Cache implementation that uses serialized objects in files on disk.
 * The cache is persistent accross requests.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Apcu implements CacheInterface {



	private $cache;


	

	/**
	 * Store any value in the cache
	 * 
	 * @param string $key
	 * @param mixed $value Will be serialized
	 * @param boolean $persist Cache must be available in next requests. Use false of it's just for this script run.
	 */
	public function set($key, $value, $persist = true) {

		//don't set false values because unserialize returns false on failure.
		if ($key === false)
			return true;

		
		if($persist) {
			apcu_store($key, $value);
		}
		
		$this->cache[$key] = $value;
	}

	/**
	 * Get a value from the cache
	 * 
	 * @param string $key 
	 * @return mixed null if it doesn't exist
	 */
	public function get($key) {		
		if(isset($this->cache[$key])) {
			return $this->cache[$key];
		}
		$success = false;
		
		$value = apcu_fetch($key, $success);
		
		if(!$success) {
			return null;
		}
		
		$this->cache[$key] = $value;
		return $this->cache[$key];		
	}

	/**
	 * Delete a value from the cache
	 * 
	 * @param string $key 
	 */
	public function delete($key) {		
		unset($this->cache[$key]);
		apcu_delete($key);
	}

	private $flushOnDestruct = false;

	/**
	 * Flush all values 
	 * 
	 * @param bool $onDestruct Delay flush until current script run ends by 
	 * default so cached values can still be used. For example cached record 
	 * relations will function until the script ends.
	 * 
	 * @return bool
	 */
	public function flush($onDestruct = true) {
		
//		throw new \Exception("Flush?");
		if ($onDestruct) {
			$this->flushOnDestruct = true;
			return true;
		}
		
		IFW::app()->debug("Flush cache");
		$this->cache = [];
//	var_dump(apcu_cache_info());
		apcu_clear_cache();
		
		
	}

	public function __destruct() {
		if ($this->flushOnDestruct) {
			$this->flush(false);
		}
	}

	public function isSupported() {
		return extension_loaded('apcu');
	}

}
