<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Location model
 *
 * Represents a geographic place, like the location of an event.
 *
 * @see http://schema.org/Place
 * @package NextEvent\PHPSDK\Model
 */
class Location extends Model
{
  /**
   * Mapping of JSON data keys to match the expected source structure
   *
   * @var array
   */
  protected $mapJson = [
    'description' => 'name',
    'geocode'     => 'geo',
    'addr1'       => 'street',
  ];

  /**
   * @inheritdoc
   */
  public function isValid()
  {
    // some fields may be empty
    return isset($this->source['name']);
  }


  /**
   * Get the title/name of the location
   *
   * @return string
   */
  public function getTitle()
  {
    return $this->source['name'];
  }


  /**
   * Get the postal address component
   *
   * @return PostalAddress|null
   */
  public function getAddress()
  {
    return isset($this->source['address']) ? new PostalAddress($this->source['address']) :
      (isset($this->source['street']) ? new PostalAddress($this->source) : null);
  }


  /**
   * Get the geographic coordinates component
   *
   * @return GeoCoordinates|null
   */
  public function getGeoLocation()
  {
    return isset($this->source['geo']) ? new GeoCoordinates($this->source['geo']) : null;
  }
}
