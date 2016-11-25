<?php

namespace IndieWeb;

use Obj;
use Error;

class Token extends Obj { }

class IndieAuth {

  const ERROR_FORBIDDEN          = 0;
  const ERROR_INSUFFICIENT_SCOPE = 1;
  const ERROR_INVALID_REQUEST    = 2;

  const TOKEN_ENDPOINT = 'https://tokens.indieauth.com/token';

  public static $token;


  /**
   * Checks the token for a me-value and compares it to the required me-value
   *
   * @param str $requiredMe Url of allowed person, defaults to this site's url
   * @return bool True on success or throws an Forbidden error
   */
  public function requireMe($requiredMe = null) {

    $token = IndieAuth::getToken();

    if (url::host($token->me) != url::host($requiredMe))
      throw new Error('You don\'t belong here', IndieAuth::ERROR_FORBIDDEN);

    return true;
  }

  /**
   * Gets the Access Token by querying the Token Endpoint with the Authentication Bearer
   *
   * @param str $requiredScope The scope that is required in order to pass
   * @return bool True on success or throws an Insufficient Scope error
   */
  public function requireScope($requiredScope) {

    $token = IndieAuth::getToken();

    if(property_exists($token, 'scope') && in_array($requiredScope, explode(' ', $token->scope))) {
      return true;
    } else {
      throw new Error('The token provided does not have the necessary scope', IndieAuth::ERROR_INSUFFICIENT_SCOPE);
    }
  }

  /**
   * Gets the Access Token by querying the Token Endpoint with the Authentication Bearer
   *
   * @param str $bearer The Authentication Bearer to
   * @param str $requiredScope The scope that is required in order to pass
   * @return object the Token object
   */
  public function getToken($bearer = null) {

    if (isset(IndieAuth::$token)) return IndieAuth::$token;

    if (!isset($bearer)) $bearer = IndieAuth::getBearer();

    // Get Token from token endpoint
    $response = remote::get(IndieAuth::TOKEN_ENDPOINT, [
     'headers' => ['Authorization: Bearer '.$bearer]
    ]);
    parse_str($response->content, $token);

    return IndieAuth::$token = new Token($token);
  }

  /**
   * Gets the Authentication Bearer from either the HTTP-header or the POST-body
   *
   * @return str The Authentication Bearer
   */
  public function getBearer() {

    // Get 'Authorization: Bearer xxx' from the header or 'access_token=xxx' from the Form-Encoded POST-body
    if(array_key_exists('HTTP_AUTHORIZATION', $_SERVER)
      and preg_match('/Bearer (.+)/', $_SERVER['HTTP_AUTHORIZATION'], $match)) {
      $bearer = $match[1];
    } elseif (isset($_POST['access_token'])) {
      $bearer = get('access_token');
    } else {
      throw new Error('An access token is required. Send an HTTP Authorization header such as \'Authorization: Bearer xxx\' or include a POST-body parameter such as \'access_token=xxx\'', IndieAuth::ERROR_FORBIDDEN);
    }

    return $bearer;
  }
}