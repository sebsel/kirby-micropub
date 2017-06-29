<?php

namespace IndieWeb;

use Obj, Url, Header, Response, Remote;

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
   * @return bool true on success or exits with a HTTP 403 header
   */
  public static function requireMe($requiredMe = null) {

    $token = IndieAuth::getToken();

    if(property_exists($token, 'me')
      and url::host($token->me) == url::host($requiredMe))
      return true;

    header::status(403);
    echo new Response('User has no access', 'html');
    exit();
  }

  /**
   * Gets the Access Token by querying the Token Endpoint with the Authentication Bearer
   *
   * @param str $requiredScope The scope that is required in order to pass
   * @return bool true on success or exits with a HTTP 401 header
   */
  public static function requireScope($requiredScope) {

    $token = IndieAuth::getToken();

    if(property_exists($token, 'scope') && in_array($requiredScope, explode(' ', $token->scope))) return true;

    header::status(401);
    echo new Response('The token provided does not have the necessary scope', 'html');
    exit();
  }

  /**
   * Gets the Access Token by querying the Token Endpoint with the Authentication Bearer
   *
   * @param str $bearer The Authentication Bearer to
   * @param str $requiredScope The scope that is required in order to pass
   * @return object the Token object or exits with a HTTP 400 header
   */
  public static function getToken($bearer = null) {

    if(isset(IndieAuth::$token)) return IndieAuth::$token;

    if(!isset($bearer)) $bearer = IndieAuth::getBearer();

    // Get Token from token endpoint
    $response = remote::get(IndieAuth::TOKEN_ENDPOINT, [
     'headers' => ['Authorization: Bearer '.$bearer]
    ]);
    parse_str($response->content, $token);

    if(isset($token['error']) and isset($token['error_description'])) {
      header::status(400);
      echo new Response('Error: ' . $token['error_description'], 'html');
      exit();
    }

    if(isset($token)) return IndieAuth::$token = new Token($token);

    header::status(400);
    echo new Response('No token found', 'html');
    exit();
  }

  /**
   * Gets the Authentication Bearer from either the HTTP-header or the POST-body
   *
   * @return str The Authentication Bearer or exits with a HTTP 401 header
   */
  public static function getBearer() {

    // Get 'Authorization: Bearer xxx' from the header or 'access_token=xxx' from the Form-Encoded POST-body
    if(array_key_exists('HTTP_AUTHORIZATION', $_SERVER)
      and preg_match('/Bearer (.+)/', $_SERVER['HTTP_AUTHORIZATION'], $match)) {
      return $match[1];
    } elseif(isset($_POST['access_token'])) {
      return $_POST['access_token'];
    }

    header::status(401);
    echo new Response('An access token is required. Send an HTTP Authorization header such as \'Authorization: Bearer xxx\' or include a POST-body parameter such as \'access_token=xxx\'', 'html');
    exit();
  }
}