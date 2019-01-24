<?php

namespace NextEvent\PHPSDK\Model;

use ReflectionClass;
use NextEvent\PHPSDK\Exception\WebhookMessageException;

/**
 * A webhook message comes from the platform the app runs on and contains data, which can be used for various processes.
 * The instance of this class should be used, to verify content of the incoming message to make your platform more
 * secure. In order to verify the incoming message, you have to know the secret, you configured on the webhook in the
 * NextEvent App.
 *
 * Example:
 * ```
 * try {
 *   $gate = WebhookMessage::current()->verify()->getModel(Gate::class);
 *   // Do something...
 * }
 * catch (WebhookMessageException $exception) {
 *    // Handle this
 * }
 * ```
 */
class WebhookMessage extends Model
{
  protected $verified;

  /**
   * Current cached webhook message.
   *
   * @var WebhookMessage
   */
  protected static $_currentMessage;

  /** @inheritDoc */
  public function __construct($source)
  {
    parent::__construct($source);
    $this->verified = false;
  }


  /** @inheritDoc */
  public function isValid()
  {
    return isset($this->source['headers']) && isset($this->source['payload']) &&
            $this->getId() !== null && $this->getEvent() !== null;
  }


  /**
   * Get the id of this webhook message.
   *
   * @return string
   */
  public function getId()
  {
    $headers = $this->getHeaders();
    return isset($headers['X-NE-Delivery']) ? $headers['X-NE-Delivery'] : null;
  }


  /**
   * Get the event of this webhook message.
   *
   * @return string
   */
  public function getEvent()
  {
    $headers = $this->getHeaders();
    return isset($headers['X-NE-Event']) ? $headers['X-NE-Event'] : null;
  }


  /**
   * Get the headers of this webhook message.
   *
   * @return array
   */
  public function getHeaders()
  {
    return $this->source['headers'];
  }


  /**
   * Get the raw payload of this webhook message.
   *
   * @return string
   */
  public function getPayload()
  {
    return $this->source['payload'];
  }


  /**
   * Get the signature for this webhook message.
   *
   * @return string
   */
  public function getSignature()
  {
    $headers = $this->getHeaders();
    return isset($headers['X-Hub-Signature']) ? $headers['X-Hub-Signature'] : null;
  }


  /**
   * Verifies the signature of this message with the given secret.
   *
   * @param string $secret
   * @throws NextEvent\PHPSDK\Exception\WebhookMessageException If no signature is present or the signature is invalid.
   * @return NextEvent\PHPSDK\Model\WebhookMessage
   */
  public function verify($secret)
  {
    if ($this->verified) {
      return $this;
    }
    $signature = $this->getSignature();
    if (!isset($signature)) {
      throw new WebhookMessageException('No X-Hub-Signature in the headers');
    }
    $parts = explode('=', $signature);
    $algo = $parts[0];
    $receivedHash = $parts[1];
    if (hash_hmac($algo, $this->getPayload(), $secret) !== $receivedHash) {
      throw new WebhookMessageException('Invalid X-Hub-Signature in the headers');
    }
    $this->verified = true;
    return $this;
  }


  /**
   * Creates a model from the given class name and returns it.
   *
   * @param string $className The full class name of the model to generate.
   * @param array $instanceArgs Optional additional arguments, which the model needs.
   * @throws NextEvent\PHPSDK\Exception\WebhookMessageException If not verified yet or
   *                                                            if the event type does not fit the provided model class.
   * @return NextEvent\PHPSDK\Model\Model
   */
  public function getModel($className, $args = array())
  {
    if (!$this->verified) {
      throw new WebhookMessageException('First verify the response before accessing the model');
    }
    $json = $this->getJSON();
    $operation = $json['operation'];
    $r = new ReflectionClass($className);
    $instanceArgs = array($json[$this->getEvent()]);
    foreach ($args as $arg) {
      $instanceArgs[] = $arg;
    }
    return $r->newInstanceArgs($instanceArgs);
  }


  /**
   * Decodes the json payload and returns it as an associative array.
   *
   * @throws NextEvent\PHPSDK\Exception\WebhookMessageException If not verified yet.
   * @return array
   */
  public function getJSON()
  {
    if (!$this->verified) {
      throw new WebhookMessageException('First verify the response before accessing json payload');
    }
    return json_decode($this->getPayload(), true);
  }


  /**
   * Creates a webhook message from the current request.
   *
   * @return WebhookMessage The current webhook message.
   */
  public static function current()
  {
    if (!self::$_currentMessage) {
      self::$_currentMessage = new WebhookMessage(array(
        'headers' => apache_request_headers(),
        'payload' => file_get_contents('php://input'),
      ));
    }
    return self::$_currentMessage;
  }

}
