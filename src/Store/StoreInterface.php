<?php

namespace NextEvent\PHPSDK\Store;

/**
 * StoreInterface for implementing a key-value store
 *
 * Provide own Cache for performance improvements.
 * For example saving Session between multiple calls
 *
 * ```php
 * // cache adapter
 * <?php
 * use NextEvent\PHPSDK\Store\StoreInterface;
 *
 * class MyCache implements StoreInterface
 * {
 *
 *  public function set($key, $value)
 *  {
 *    // your implementation
 *  }
 *
 *  public function get($key)
 *  {
 *    // your implementation
 *  }
 *
 *  public function clear()
 *  {
 *    // your implementation
 *  }
 * }
 * ?>
 *
 * // initialize
 * <?php
 * $my_cache = new MyCache();
 *
 * // on initialize
 * $ne_client = new \NextEvent\PHPSDK\Client($appUrl, $credentials, $widgetHash,
 * $my_cache);
 * // or
 * $ne_client->setCache($my_cache);
 * ?>
 * ```
 *
 * @package NextEvent\PHPSDK\Store
 */
interface StoreInterface
{
  /**
   * Add a value to the store under a unique key
   *
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   * @return void
   */
  public function set($key, $value, $ttl = null);


  /**
   * Retrieve value by key from store
   *
   * @param string $key
   * @return mixed
   */
  public function get($key);


  /**
   * Determine the if value corresponding to a provided key exist
   *
   * @param string $key
   * @return boolean
   */
  public function has($key);


  /**
   * Delete a value from the cache
   *
   * @param string $key
   */
  public function delete($key);


  /**
   * Clear all expired records from store
   *
   * @return void
   */
  public function expunge();


  /**
   * Delete all expired records from store
   *
   * @return void
   */
  public function clear();
}
