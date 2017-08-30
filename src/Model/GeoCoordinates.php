<?php

namespace NextEvent\PHPSDK\Model;

/**
 * GeoCoordinates model
 *
 * Struct for a pair of geographic coordinates as defined
 * in the  World Geodetic System (WGS 84).
 *
 * @see http://schema.org/GeoCoordinates
 * @package NextEvent\PHPSDK\Model
 */
class GeoCoordinates extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['latitude']) && isset($this->source['longitude']);
  }


  /**
   * Get the latitude value of this coordinate
   *
   * @return float|string
   */
  public function getLatitude()
  {
    return $this->source['latitude'];
  }


  /**
   * Get the longitude value of this coordinate
   *
   * @return float|string
   */
  public function getLongitude()
  {
    return $this->source['longitude'];
  }
}
