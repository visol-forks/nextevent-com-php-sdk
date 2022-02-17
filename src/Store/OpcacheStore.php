<?php

namespace NextEvent\PHPSDK\Store;

/**
 * Implements StoreInterface and stores its data in a temp file.
 *
 * If __opcache__ is available, us this to cache temp file for performance
 * improvement.
 *
 * @package NextEvent\PHPSDK\Store
 */
class OpcacheStore extends MemoryStore
{
  /**
   * @var string
   */
  protected $fileName;


  /**
   * Construct OpcacheStore.
   * Save cache in tmp directory and use Opcache for performance improvement.
   * Optionally define a separate scope for the cache.
   *
   * @param string $scope whitespace and any special chars except _- are stripped
   */
  public function __construct($scope = '')
  {
    $this->fileName = sys_get_temp_dir() . '/nextevent_sdk_cache';
    if ($scope) {
      $this->fileName .= '_' . preg_replace('/[^A-Za-z0-9\-_]/', '', $scope);
    }
  }


  /**
   * Define value by key in store
   *
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   */
  public function set($key, $value, $ttl = null)
  {
    $this->readData();
    parent::set($key, $value, $ttl);
    $this->persistData();
  }


  /**
   * Retrieve value by key from store
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    $this->readData();
    return parent::get($key);
  }


  /**
   * Determine if the value corresponding to a provided key exists
   *
   * @param string $key
   * @return boolean
   */
  public function has($key)
  {
    $this->readData();
    return parent::has($key);
  }


  /**
   * Delete a value from the cache
   *
   * @param string $key
   */
  public function delete($key)
  {
    $this->readData();
    parent::delete($key);
    $this->persistData();
  }


  /**
   * Clear the entire cache
   *
   * @return void
   */
  public function clear()
  {
    parent::clear();

    if (file_exists($this->fileName)) {
      unlink($this->fileName);
    }
  }


  /**
   * Helper method to read the data store
   */
  protected function readData()
  {
    if (!isset($this->store)) {
      $this->store = file_exists($this->fileName) ? include($this->fileName) : [];
    }
  }


  /**
   * Helper method to persist in-memory data to a file
   */
  protected function persistData()
  {
    $content = '<?php return ' . var_export($this->store, true) . ';';
    file_put_contents($this->fileName, $content);

    if (function_exists('opcache_compile_file')) {
      @opcache_compile_file($this->fileName);
    }
  }
}
