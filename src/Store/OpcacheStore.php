<?php

namespace NextEvent\PHPSDK\Store;

/**
 * Impelents StoreInterface and stores its data in a temp file.
 *
 * If __opcache__ is available, us this to chache temp file for performance
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
   * Default constrctor
   */
  public function __construct()
  {
    $this->fileName = sys_get_temp_dir() . '/nextevent_sdk_cache_o8a76bfa0a87';
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
   * Determine the if value corresponding to a provided key exist
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
      $this->store = @include($this->fileName) ?: [];
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
