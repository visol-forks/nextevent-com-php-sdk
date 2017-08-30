<?php

namespace NextEvent\PHPSDK\Exception;

/**
 * Thrown when tickets are not yet available for the given order
 *
 * @package NextEvent\PHPSDK\Exception
 */
class TicketNotFoundException extends \Exception
{
  protected $message = 'No Tickets found for order';
}
