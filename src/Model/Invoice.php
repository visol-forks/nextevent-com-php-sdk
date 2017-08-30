<?php

namespace NextEvent\PHPSDK\Model;

class Invoice extends Model
{
  /**
   * Check model if its state is valid
   *
   * @return bool
   */
  public function isValid()
  {
    return isset($this->source['id']) &&
      isset($this->source['uuid']) &&
      isset($this->source['reference']) &&
      isset($this->source['authorization']);
  }


  /**
   * @return int
   */
  public function getId()
  {
    return $this->source['id'];
  }


  /**
   * @return string
   */
  public function getUUID()
  {
    return $this->source['uuid'];
  }


  /**
   * @return string
   */
  public function getReference()
  {
    return $this->source['reference'];
  }


  /**
   * @return string
   */
  public function getAuthorization()
  {
    return $this->source['authorization'];
  }
}
