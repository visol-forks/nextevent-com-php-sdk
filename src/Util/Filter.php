<?php

namespace NextEvent\PHPSDK\Util;

/**
 * The filter class is a helper for converting filters, represented as an array, into a string for API requests.
 *
 * @package NextEvent\PHPSDK\Util
 */
class Filter
{
  /**
   * Converts the given filter array to a string, which can be used in a
   * GET request.
   * For example
   * ```array('myId' => array(1,2), 'myHash' => 'abc')```
   * becomes
   * ```myId=1,2&myHash=abc```.
   *
   * @param array $filter A list of filters. If the key points to an array, the values in it get appended with ','.
   * @return string An url encoded string.
   */
  public static function toString(array $filter)
  {
    $parts = [];

    foreach ($filter as $key => $val) {
      if (!isset($val)) {
        continue;
      }
      if (is_array($val)) {
        $encoded = array_map(function($val) {
          return urlencode($val);
        }, $val) ;
        $parts[] = $key . '=' . implode(',', $encoded);
      } else {
        $v = urlencode($val);
        $parts[] = "$key=$v";
      }
    }

    return implode('&', $parts);
  }

}
