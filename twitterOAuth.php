<?php
require_once('OAuth.php');

/*
 * Abraham Williams (abraham@abrah.am) http://abrah.am
 *
 * Basic lib to work with Twitter's OAuth beta. This is untested and should not
 * be used in production code. Twitter's beta could change at anytime.
 *
 * Code based on:
 * Fire Eagle code - http://github.com/myelin/fireeagle-php-lib
 * twitterlibphp - http://github.com/poseurtech/twitterlibphp
 */

session_start();
$consumer_key = 'vItOrIwZLWUFT4AT2bA2aA';
$consumer_secret = 't78HamYdjAdYmwh73oJ5qmWyz19Q7sPTgld6IpCQ4Qo';
$content = NULL;
$state = $_SESSION['oauth_state'];
$session_token = $_SESSION['oauth_request_token'];
$oauth_token = $_REQUEST['oauth_token'];
$section = $_REQUEST['section'];

if ($_REQUEST['test'] === 'clear') { session_destroy(); session_start(); }
if ($_REQUEST['oauth_token'] != NULL && $_SESSION['oauth_state'] === 'start') {
  $_SESSION['oauth_state'] = $state = 'returned';
}

switch ($state) {
  default:
    $to = new TwitterOAuth($consumer_key, $consumer_secret);
    $tok = $to->getRequestToken();
    $_SESSION['oauth_request_token'] = $token = $tok['oauth_token'];
    $_SESSION['oauth_request_token_secret'] = $tok['oauth_token_secret'];
    $_SESSION['oauth_state'] = "start";
    $request_link = $to->getAuthorizeURL($token);
    header("Location: $request_link");
    break;
  case 'returned':
    if ($_SESSION['oauth_access_token'] === NULL && $_SESSION['oauth_access_token_secret'] === NULL) {
      $to = new TwitterOAuth($consumer_key, $consumer_secret, $_SESSION['oauth_request_token'], $_SESSION['oauth_request_token_secret']);
      $tok = $to->getAccessToken();
      $_SESSION['oauth_access_token'] = $tok['oauth_token'];
      $_SESSION['oauth_access_token_secret'] = $tok['oauth_token_secret'];
    }
    $to = new TwitterOAuth($consumer_key, $consumer_secret, $_SESSION['oauth_access_token'], $_SESSION['oauth_access_token_secret']);

    if(isset($_GET['textarea'])) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://search.twitter.com/search.json?q=%22".urlencode($_GET['textarea'])."%22");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $search_results = json_decode(curl_exec($ch))->results;
        curl_close($ch);

        if(count($search_results) > 1) {
            $content .= "<br/>Sorry, this tweet has been used over " . count($search_results) . " times";
            $content .= print_r(($search_results),true);
        } else {
            $content .= "Successful update<br/>";
            $content .= $to->OAuthRequest('https://twitter.com/statuses/update.xml', array('status' => $_GET['textarea']), 'POST');
        }
    }
    break;
    if($content) {
        print $content;
    }
}

class TwitterOAuth {
  /* Contains the last HTTP status code returned */
  private $http_status;

  /* Contains the last API call */
  private $last_api_call;

  /* Set up the API root URL */
  public static $TO_API_ROOT = "https://twitter.com";

  /**
   * Set API URLS
   */
  function requestTokenURL() { return self::$TO_API_ROOT.'/oauth/request_token'; }
  function authorizeURL() { return self::$TO_API_ROOT.'/oauth/authorize'; }
  function accessTokenURL() { return self::$TO_API_ROOT.'/oauth/access_token'; }

  /**
   * Debug helpers
   */
  function lastStatusCode() { return $this->http_status; }
  function lastAPICall() { return $this->last_api_call; }

  /**
   * construct TwitterOAuth object
   */
  function __construct($consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL) {
    $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
    $this->consumer = new OAuthConsumer($consumer_key, $consumer_secret);
    if (!empty($oauth_token) && !empty($oauth_token_secret)) {
      $this->token = new OAuthConsumer($oauth_token, $oauth_token_secret);
    } else {
      $this->token = NULL;
    }
  }


  /**
   * Get a request_token from Twitter
   *
   * @returns a key/value array containing oauth_token and oauth_token_secret
   */
  function getRequestToken() {
    $r = $this->oAuthRequest($this->requestTokenURL());
    $token = $this->oAuthParseResponse($r);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Parse a URL-encoded OAuth response
   *
   * @return a key/value array
   */
  function oAuthParseResponse($responseString) {
    $r = array();
    foreach (explode('&', $responseString) as $param) {
      $pair = explode('=', $param, 2);
      if (count($pair) != 2) continue;
      $r[urldecode($pair[0])] = urldecode($pair[1]);
    }
    return $r;
  }

  /**
   * Get the authorize URL
   *
   * @returns a string
   */
  function getAuthorizeURL($token) {
    if (is_array($token)) $token = $token['oauth_token'];
    return $this->authorizeURL() . '?oauth_token=' . $token;
  }

  /**
   * Exchange the request token and secret for an access token and
   * secret, to sign API calls.
   *
   * @returns array("oauth_token" => the access token,
   *                "oauth_token_secret" => the access secret)
   */
  function getAccessToken($token = NULL) {
    $r = $this->oAuthRequest($this->accessTokenURL());
    $token = $this->oAuthParseResponse($r);
    $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
    return $token;
  }

  /**
   * Format and sign an OAuth / API request
   */
  function oAuthRequest($url, $args = array(), $method = NULL) {
    if (empty($method)) $method = empty($args) ? "GET" : "POST";
    $req = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $args);
    $req->sign_request($this->sha1_method, $this->consumer, $this->token);
    switch ($method) {
    case 'GET': return $this->http($req->to_url());
    case 'POST': return $this->http($req->get_normalized_http_url(), $req->to_postdata());
    }
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $post_data = null) {
    $ch = curl_init();
    if (defined("CURL_CA_BUNDLE_PATH")) curl_setopt($ch, CURLOPT_CAINFO, CURL_CA_BUNDLE_PATH);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //////////////////////////////////////////////////
    ///// Set to 1 to verify Twitter's SSL Cert //////
    //////////////////////////////////////////////////
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    if (isset($post_data)) {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }
    $response = curl_exec($ch);
    $this->http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->last_api_call = $url;
    curl_close ($ch);
    return $response;
  }
}
