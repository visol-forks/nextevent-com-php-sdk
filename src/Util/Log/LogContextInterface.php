<?php

namespace NextEvent\PHPSDK\Util\Log;

/**
 * Interface LogContextInterface
 *
 * General Interface used to provide function to convert implementor class
 * to an array, which can be used for the log context.
 *
 * @package NextEvent\PHPSDK\Util\Log
 */
interface LogContextInterface
{
  /**
   * Return self representation for log context
   *
   * @return array
   */
  public function toLogContext();
}
