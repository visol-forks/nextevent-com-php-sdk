<?php

namespace NextEvent\PHPSDK\Exception;

/**
 * Thrown when a basket could not be found or is expired
 *
 * @package NextEvent\PHPSDK\Exception
 */
class BasketEmptyException extends APIResponseException
{
  protected $message = 'Basket does not exist or is expired';
}
