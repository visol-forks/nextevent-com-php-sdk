<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Organization model
 *
 * Represents an organization entity
 *
 * @see http://schema.org/Organization
 * @package NextEvent\PHPSDK\Model
 */
class Organization extends Model
{
  /**
   * @inheritdoc
   */
  public function isValid()
  {
    // require at least a name
    return isset($this->source['name']) || isset($this->source['company']);
  }


  /**
   * Get the title/name of the organization
   *
   * @return string
   */
  public function getName()
  {
    return isset($this->source['name']) ? $this->source['name'] : isset($this->source['company']) ? $this->source['company'] : '';
  }


  /**
   * Get the postal address component
   *
   * @return PostalAddress|null
   */
  public function getAddress()
  {
    return isset($this->source['address']) ? new PostalAddress($this->source['address']) : null;
  }


  /**
   * Get the email address component
   *
   * @return string|null
   */
  public function getEmail()
  {
    return isset($this->source['email']) ? $this->source['email'] : null;
  }

  /**
   * Get the telephone component
   *
   * @return string|null
   */
  public function getPhone()
  {
    return isset($this->source['telephone']) ? $this->source['telephone'] : null;
  }


  /**
   * Get the url (website) component
   *
   * @return string|null
   */
  public function getUrl()
  {
    return isset($this->source['url']) ? $this->source['url'] : null;
  }
}
