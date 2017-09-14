<?php

namespace NextEvent\PHPSDK\Model;
use DateTimeZone;

/**
 * Class DateTime
 *
 * Extend the default DateTime class with a flag indicating that it
 * only represent a date and the time should be ignored.
 *
 * @package NextEvent\PHPSDK\Model
 */
class DateTime extends \DateTime
{
  /**
   * @var bool internal flag if this DateTime represents only a date
   */
  protected $dateOnly = false;


  /**
   * DateTime constructor.
   *
   * In addition, check whether this time string is a date only and set a flag.
   * Only this pattern 'Y-m-d' is recognized as a date without time
   *
   * @see http://www.php.net/manual/en/class.datetime.php
   * @param string            $time
   * @param DateTimeZone|null $timezone
   */
  public function __construct($time = 'now', DateTimeZone $timezone = null)
  {
    preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $time, $matches);
    if (count($matches)) {
      $this->dateOnly = true;
    }
    parent::__construct($time, $timezone);
  }


  /**
   * Returns true if this DateTime model only represent a date.
   *
   * @return bool
   */
  public function isDateOnly()
  {
    return $this->dateOnly;
  }


  /**
   * Parse ISO8601 string to DateTime object and make sure that it is in
   * the same timezone as used by the server.
   *
   * @param string $string
   * @return DateTime|null
   */
  public static function fromJson($string)
  {
    try {
      $date = new DateTime($string);
      $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      return $date;
    } catch (\Exception $ex) {
      return null;
    }
  }

}
