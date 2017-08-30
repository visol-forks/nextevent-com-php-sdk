<?php

namespace NextEvent\PHPSDK\Exception;

/**
 * SDK Client ist not authenticated yet
 *
 * @package NextEvent\PHPSDK\Exception
 */
class NotAuthenticatedException extends \Exception
{
  protected $message = 'SDK Client is not authenticated';
}
