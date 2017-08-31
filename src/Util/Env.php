<?php

namespace NextEvent\PHPSDK\Util;

/**
 * Class Env
 *
 * Helper for handling changing variables in different environments
 *
 * Known environment variables:
 * - <code>string iam_service_url</code> the Url to the IAM Service
 * - <code>string payment_service_url</code> the Url to the Payment Service
 * - <code>string locale</code> language code, use only 2 letters like 'en'
 *
 * @package NextEvent\PHPSDK\Util
 */
class Env
{
  /**
   * Active environment, use always uppercase
   *
   * @var string
   */
  protected static $environment = 'PROD';
  /**
   * Container for storing environment variables
   *
   * @var array
   */
  protected static $variables = [
    'PROD' => [
      'iam_service_url' => 'https://iam.nextevent.com/',
      'payment_service_url' => 'https://payment.nextevent.com/'
    ],
    'TEST' => [
      'iam_service_url' => 'https://iam.test.nextevent.com/',
      'payment_service_url' => 'https://payment.test.nextevent.com/'
    ],
    'INT' => [
      'iam_service_url' => 'https://iam.int.nextevent.com/',
      'payment_service_url' => 'https://payment.int.nextevent.com/'
    ],
    'DEV' => []
  ];


  /**
   * Select the NextEvent environment to connect with
   *
   * @param string $environment either PROD, TEST, DEV
   */
  public static function setEnv($environment)
  {
    self::$environment = strtoupper($environment);
  }


  /**
   * Get currently selected environment
   *
   * @return string
   */
  public static function getEnv()
  {
    return self::$environment;
  }


  /**
   * Set environment variable by key
   *
   * @param string $key
   * @param mixed  $value
   * @param string $environment
   */
  public static function setVar($key, $value, $environment = '*')
  {
    $environment = isset($environment) ? $environment : self::getEnv();
    if ($environment === '*') {
      foreach (self::$variables as $env => $vars) {
        self::$variables[$env][$key] = $value;
      }
    } else {
      self::$variables[strtoupper($environment)][$key] = $value;
    }
  }


  /**
   * Get variable from environment
   *
   * @param string $key
   * @return mixed
   */
  public static function getVar($key)
  {
    if (isset(self::$variables[self::$environment][$key])) {
      return self::$variables[self::$environment][$key];
    } else {
      return null;
    }
  }

}
