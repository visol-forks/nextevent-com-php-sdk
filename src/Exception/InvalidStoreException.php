<?php

namespace NextEvent\PHPSDK\Exception;

/**
 * Thrown when a given object doesn't implement the NextEvent\PHPSDK\Store\StoreInterface
 *
 * @package NextEvent\PHPSDK\Exception
 */
class InvalidStoreException extends \Exception
{
  protected $message = 'An instance of StoreInterface is required';
}
