<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Class PostalAddress
 *
 * Represents an postal address data structure
 *
 * @see http://schema.org/PostalAddress
 * @package NextEvent\PHPSDK\Model
 */
class PostalAddress extends Model
{
  /**
   * Mapping of JSON data keys to match the expected source structure
   *
   * @var array
   */
  protected $mapJson = [
    'street'      => 'streetAddress',
    'zip'         => 'postalCode',
    'city'        => 'addressLocality',
    'country'     => 'addressCountry',
    'countryName' => 'addressCountry',
  ];

  /**
   * @inheritdoc
   */
  public function isValid()
  {
    // some fields may be empty
    return true;
  }


  /**
   * Get the country part
   *
   * @return string
   */
  public function getCountry()
  {
    return isset($this->source['addressCountry']) ? $this->source['addressCountry'] : '';
  }


  /**
   * Get the locality part
   *
   * @return string
   */
  public function getLocality()
  {
    return isset($this->source['addressLocality']) ? $this->source['addressLocality'] : '';
  }


  /**
   * Get the postasl code part
   *
   * @return string
   */
  public function getPostalCode()
  {
    return isset($this->source['postalCode']) ? $this->source['postalCode'] : '';
  }


  /**
   * Get the street/address part
   *
   * @return string
   */
  public function getStreetAddress()
  {
    return isset($this->source['streetAddress']) ? $this->source['streetAddress'] : '';
  }

}
