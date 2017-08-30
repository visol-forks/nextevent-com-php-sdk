<?php

namespace NextEvent\PHPSDK\Model;

/**
 * Token model
 *
 * Parse Token issued by the IAMClient and provide some helper functions
 *
 * @internal
 * @package NextEvent\PHPSDK\Service
 */
class Token
{
  /**
   * @var string
   */
  protected $accessToken;
  /**
   * @var int
   */
  protected $expiresAt;
  /**
   * @var string
   */
  protected $scope;
  /**
   * @var string
   */
  protected $tokenType;


  /**
   * Token constructor
   *
   * @param string $accessToken
   * @param int    $expiresIn
   * @param string $scope
   * @param string $tokenType
   */
  public function __construct($accessToken, $expiresIn, $scope, $tokenType)
  {
    $this->accessToken = $accessToken;
    $this->expiresAt = time() + $expiresIn;
    $this->scope = $scope;
    $this->tokenType = $tokenType;
  }


  /**
   * Parse string in json format an construct token
   *
   * @param string $str
   * @return Token|null
   */
  public static function fromString($str)
  {
    $data = json_decode($str, true);
    if ($data && isset($data['accessToken'])) {
      $token = new Token(
        $data['accessToken'], 0, $data['scope'], $data['tokenType']
      );
      $token->expiresAt = $data['expiresAt'];
      return $token;
    }
    return null;
  }


  /**
   * Convert token to string in json format
   *
   * @return string
   */
  public function toString()
  {
    return json_encode(get_object_vars($this));
  }


  /**
   * Get the access token part
   *
   * @return string
   */
  public function getAccessToken()
  {
    return $this->accessToken;
  }


  /**
   * Check whether the token has expired
   *
   * @return bool True if expired and no longer valid
   */
  public function isExpired()
  {
    return $this->expiresAt < time();
  }


  /**
   * Get the timestamp when the token expires
   *
   * @return int Unix timestamp
   */
  public function getExpiresAt()
  {
    return $this->expiresAt;
  }


  /**
   * Get the scope(s) this token authorizes
   *
   * @return string
   */
  public function getScope()
  {
    return $this->scope;
  }


  /**
   * Get the type part
   *
   * @return string
   */
  public function getTokenType()
  {
    return $this->tokenType;
  }


  /**
   * Compose a string to be used as Authorizaton: header in HTTP requests
   *
   * @return string
   */
  public function getAuthorizationHeader()
  {
    return $this->tokenType . ' ' . $this->accessToken;
  }
}
