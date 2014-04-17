<?php

/**
 * @file
 * Contains RestfulHookMenuestCase
 */

class RestfulHookMenuestCase extends DrupalWebTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Menu API',
      'description' => 'Test the hook_menu() implementation.',
      'group' => 'Restful',
    );
  }

  function setUp() {
    parent::setUp('restful_example');
  }

  /**
   * Test viewing an entity (GET method).
   */
  function testViewEntity() {
    $user1 = $this->drupalCreateUser();

    $title = $this->randomName();
    $settings = array(
      'type' => 'article',
      'title' => $title,
      'uid' => $user1->uid,
    );
    $node1 = $this->drupalCreateNode($settings);

    // Test version 1.0
    $result = $this->httpRequest('api/1/articles/' . $node1->nid, 'GET');
    $expected_result = array(
      'id' => $node1->nid,
      'label' => $node1->title,
      'self' => url('node/' . $node1->nid, array('absolute' => TRUE)),
    );

    $this->assertEqual($result, json_encode($expected_result));

    // Test version 1.1
    $headers = array('restful_minor_version' => 1);
    $result = $this->httpRequest('api/1/articles/' . $node1->nid, 'GET', NULL, $headers);
    unset($expected_result['self']);
    $this->assertEqual($result, json_encode($expected_result));
  }

  /**
   * Helper function to issue a HTTP request with simpletest's cURL.
   *
   * Copied and slightly adjusted from the RestWS module.
   *
   * @param array $body
   *   Either the body for POST and PUT or additional URL parameters for GET.
   */
  protected function httpRequest($url, $method, $account = NULL, $body = NULL, $headers = array()) {
    $format = 'json';

    if (isset($account)) {
      unset($this->curlHandle);
      $this->drupalLogin($account);
    }
    if (in_array($method, array('POST', 'PUT', 'DELETE'))) {
      // GET the CSRF token first for writing requests.
      $token = $this->drupalGet('restws/session/token');
    }
    switch ($method) {
      case 'GET':
        // Set query if there are addition GET parameters.
        $options = isset($body) ? array('absolute' => TRUE, 'query' => $body) : array('absolute' => TRUE);
        $curl_options = array(
          CURLOPT_HTTPGET => TRUE,
          CURLOPT_URL => url($url, $options),
          CURLOPT_NOBODY => FALSE,
        );
        break;

      case 'POST':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_POST => TRUE,
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/' . $format,
            'X-CSRF-Token: ' . $token,
          ),
        );
        break;

      case 'PUT':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'PUT',
          CURLOPT_POSTFIELDS => $body,
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/' . $format,
            'X-CSRF-Token: ' . $token,
          ),
        );
        break;

      case 'DELETE':
        $curl_options = array(
          CURLOPT_HTTPGET => FALSE,
          CURLOPT_CUSTOMREQUEST => 'DELETE',
          CURLOPT_URL => url($url, array('absolute' => TRUE)),
          CURLOPT_NOBODY => FALSE,
          CURLOPT_HTTPHEADER => array('X-CSRF-Token: ' . $token),
        );
        break;
    }

    if ($headers) {
      $curl_options[CURLOPT_HTTPHEADER] = $headers;
    }

    $response = $this->curlExec($curl_options);
    $headers = $this->drupalGetHeaders();
    $headers = implode("\n", $headers);

    $this->verbose($method . ' request to: ' . $url .
      '<hr />Code: ' . curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE) .
      '<hr />Response headers: ' . $headers .
      '<hr />Response body: ' . $response);

    return $response;
  }

}
