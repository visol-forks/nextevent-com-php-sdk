<?php

namespace NextEvent\PHPSDK\Util\Log;

use NextEvent\PHPSDK\Exception\InvalidArgumentException;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Class Logger
 *
 * This is only the Wrapper for a PSR Logger and not the actual Logger.
 * Implements some convenience functions for better logging.
 *
 * @package NextEvent\PHPSDK\Util
 */
class Logger extends AbstractLogger
{
  /**
   * @var null|LoggerInterface
   */
  protected $logger = null;
  /**
   * Default properties for the logging context
   *
   * @var array
   */
  protected $defaultContext = array();


  /**
   * Make sure the logger is properly wrapped and can be used with defaultContext.
   * If no logger given, use logger mock.
   *
   * @param LoggerInterface $logger
   * @param array           $defaultContext
   * @return Logger
   * @throws InvalidArgumentException
   */
  public static function wrapLogger($logger = null, array $defaultContext = array())
  {
    if ($logger instanceof Logger) {
      $logger->setDefaultContext(array_merge($logger->defaultContext, $defaultContext));
      return $logger;
    } else if ($logger) {
      if (!($logger instanceof LoggerInterface)) {
        throw new InvalidArgumentException('The logger object must implement Psr\Log\LoggerInterface');
      }

      $wrapped = new Logger($logger);
      $wrapped->setDefaultContext($defaultContext);
      return $wrapped;
    } else {
      return new Logger();
    }
  }


  /**
   * Logger constructor. Wrap given Logger with defaultContext functionality.
   * If no logger defined, provide mock replacement with void functions.
   *
   * @param LoggerInterface $logger
   */
  public function __construct($logger = null)
  {
    $this->logger = $logger;
  }


  /**
   * Define which values should be used as default for the context
   *
   * @param array $defaultContext
   * @return $this
   */
  public function setDefaultContext(array $defaultContext = array())
  {
    $this->defaultContext = $defaultContext;
    return $this;
  }


  /**
   * Logs with the given level
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   *
   * @return void
   */
  public function log($level, $message, array $context = array())
  {
    if ($this->logger) {
      $this->logger->log(
        $level,
        $message,
        array_merge($this->defaultContext, $context)
      );
    }
  }


  /**
   * Getter for the wrapped Logger instance
   *
   * @return LoggerInterface|null
   */
  public function getLogger()
  {
    return $this->logger;
  }
}
