<?php

namespace NextEvent\PHPSDK\Store;

/**
 * Stores data in memory as long as process is active
 *
 * @package NextEvent\PHPSDK\Store
 */
class MemoryStore implements StoreInterface
{
  /**
   * @var array
   */
  protected $store;


  /**
   * Construct an empty MemoryStore,
   * which stores the data in memory during the request
   */
  public function __construct()
  {
    $this->store = array();
  }


  /**
   * Add a value by key to store
   *
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   */
  public function set($key, $value, $ttl = null)
  {
    $this->store[$key] = $value;
  }


  /**
   * Determine the if value corresponding to a provided key exist
   *
   * @param string $key
   * @return boolean
   */
  public function has($key)
  {
    return isset($this->store[$key]);
  }


  /**
   * Retrieve value by key from store
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    return isset($this->store[$key]) ? $this->store[$key] : null;
  }


  /**
   * Delete a value from the cache
   *
   * @param string $key
   */
  public function delete($key)
  {
    unset($this->store[$key]);
  }


  /**
   * Clear full cache
   *
   * @return void
   */
  public function clear()
  {
    $this->store = array();
  }


  /**
   * Clear all expired records from store
   *
   * @return void
   */
  public function expunge()
  {
    // not implemented
  }
}
