<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Gate model
 *
 * A gate represents an entity which is responsible for invalidating access codes.
 * It can only invalidate those access codes whose category id matches one of the gate's category ids.
 *
 * @package NextEvent\PHPSDK\Model
 */
class Gate extends Model
{
  const MODE_IN = 1;
  const MODE_OUT = 2;
  const MODE_BOTH = 3;

  /**
   * A reference to the rest client.
   *
   * @var NextEvent\PHPSDK\Rest\Client
   */
  protected $restClient;

  /**
   * Cached replaced gate, if any.
   *
   * @var NextEvent\PHPSDK\Model\Gate
   */
  protected $replacedGate;

  /**
   * Cached gate which replaces this, if any.
   *
   * @var NextEvent\PHPSDK\Model\Gate
   */
  protected $replacedBy;

  /**
   * @inheritdoc
   * @param \NextEvent\PHPSDK\Rest\Client $restClient
   */
  public function __construct($source, $restClient)
  {
    parent::__construct($source);
    $this->restClient = $restClient;
  }


  /**
   * @inheritdoc
   */
  public function isValid()
  {
    return isset($this->source['gate_id']) &&
            isset($this->source['hash']) &&
            isset($this->source['mode']) &&
            isset($this->source['name']) &&
            isset($this->source['categories']);
  }


  /**
   * Get the unique identifier for this gate
   *
   * @return int
   */
  public function getId()
  {
    return $this->source['gate_id'];
  }


  /**
   * Get the creation data of this gate
   *
   * @return DateTime|null
   */
  public function getCreatedDate()
  {
    return isset($this->source['created']) ? DateTime::fromJson($this->source['created']) : null;
  }


  /**
   * Get the changed date of this gate
   *
   * @return DateTime|null
   */
  public function getChangedDate()
  {
    return isset($this->source['changed']) ? DateTime::fromJson($this->source['changed']) : null;
  }


  /**
   * Get the unique hash for this gate
   *
   * @return string
   */
  public function getHash()
  {
    return $this->source['hash'];
  }


  /**
   * Get a list of all categories which can be invalidated on this gate
   *
   * @return array
   */
  public function getCategories()
  {
    return $this->source['categories'];
  }


  /**
   * Get the mode for this gate
   *
   * @see Gate::MODE_IN For the in mode
   * @see Gate::MODE_OUT For the out mode
   * @see Gate::MODE_BOTH For both modes
   * @return int
   */
  public function getMode()
  {
    switch ($this->source['mode']) {
      case 'in': return self::MODE_IN;
      case 'out': return self::MODE_OUT;
      case 'both': return self::MODE_BOTH;
    }
  }


  /**
   * Get the access from date for this gate
   *
   * @return DateTime|null
   */
  public function getAccessFrom()
  {
    return isset($this->source['access_from']) ? DateTime::fromJson($this->source['access_from']) : null;
  }


  /**
   * Get the access to date for this gate
   *
   * @return DateTime|null
   */
  public function getAccessTo()
  {
    return isset($this->source['access_to']) ? DateTime::fromJson($this->source['access_to']) : null;
  }


  /**
   * Get the name for this gate
   *
   * @return string
   */
  public function getName()
  {
    return $this->source['name'];
  }


  /**
   * Get the deactivated date for this gate
   *
   * @return DateTime|null
   */
  public function getDeactivated()
  {
    return isset($this->source['deactivated']) ? DateTime::fromJson($this->source['deactivated']) : null;
  }


  /**
   * Whether transfering the qr code for this gate is allowed or not
   *
   * @return boolean
   */
  public function isTransferAllowed()
  {
    return $this->source['allow_transfer'];
  }


  /**
   * Gate the gate which has been replaced by this gate
   *
   * @return Gate
   */
  public function getReplacedGate()
  {
    if (!isset($this->source['replaced_gate_id'])) {
      return null;
    }

    if (!isset($this->source['replaced_gate']) && $this->restClient) {
      $response = $this->restClient->get('/gate/' . intval($this->source['replaced_gate_id']) );
      $this->source['replaced_gate'] = $response->getEmbedded();
    }

    if (!isset($this->source['replaced_gate'])) {
      return null;
    }

    if (empty($this->replacedGate)) {
      $this->replacedGate = new Gate($this->source['replaced_gate'], $this->restClient);
    }

    return $this->replacedGate;
  }


  /**
   * Get the gate which replaces this gate.
   *
   * @return Gate
   */
  public function getReplacedBy()
  {
    if (!isset($this->source['replaced_by']) || empty($this->source['replaced_by'])) {
      return null;
    }

    if (!isset($this->source['replaced_by_gate']) && $this->restClient) {
      $response = $this->restClient->get('/gate/' . intval($this->source['replaced_by']['gate_id']) );
      $this->source['replaced_by_gate'] = $response->getEmbedded();
    }

    if (!isset($this->source['replaced_by_gate'])) {
      return null;
    }

    if (empty($this->replacedBy)) {
      $this->replacedBy = new Gate($this->source['replaced_by_gate'], $this->restClient);
    }

    return $this->replacedBy;
  }
}
