<?php

/**
 * @file
 * Contains Drupal\restful_token_auth\Plugin\resource\AccessToken__1_0.
 */

namespace Drupal\restful_token_auth\Plugin\resource;

use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderEntityInterface;

/**
 * Class AccessToken__1_0
 * @package Drupal\restful_token_auth\Plugin\resource
 *
 * @Resource(
 *   name = "access_token:1.0",
 *   resource = "access_token",
 *   label = "Access token authentication",
 *   description = "Export the access token authentication resource.",
 *   authenticationTypes = {
 *     "cookie",
 *     "basic_auth"
 *   },
 *   authenticationOptional = FALSE,
 *   dataProvider = {
 *     "entityType": "restful_token_auth",
 *     "bundles": {
 *       "access_token"
 *     },
 *   },
 *   formatter = "single_json",
 *   menuItem = "login-token",
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class AccessToken__1_0 extends TokenAuthenticationBase implements ResourceInterface {

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    return array(
      '' => array(
        // Get or create a new token.
        RequestInterface::METHOD_GET => 'getOrCreateToken',
        RequestInterface::METHOD_OPTIONS => 'discover',
      ),
    );
  }

  /**
   * Create a token for a user, and return its value.
   */
  public function getOrCreateToken() {
    $entity_type = $this->getEntityType();
    $account = $this->getAccount();
    // Check if there is a token that did not expire yet.
    /* @var DataProviderEntityInterface $data_provider */
    $data_provider = $this->getDataProvider();
    $query = $data_provider->EFQObject();
    $result = $query
      ->entityCondition('entity_type', $entity_type)
      ->entityCondition('bundle', 'access_token')
      ->propertyCondition('uid', $account->uid)
      ->range(0, 1)
      ->execute();

    $token_exists = FALSE;

    if (!empty($result[$entity_type])) {
      $id = key($result[$entity_type]);
      $access_token = entity_load_single($entity_type, $id);

      $token_exists = TRUE;
      if (!empty($access_token->expire) && $access_token->expire < REQUEST_TIME) {
        if (variable_get('restful_token_auth_delete_expired_tokens', TRUE)) {
          // Token has expired, so we can delete this token.
          $access_token->delete();
        }

        $token_exists = FALSE;
      }
    }

    if (!$token_exists) {
      /* @var \Drupal\restful_token_auth\Entity\RestfulTokenAuthController $controller */
      $controller = entity_get_controller($this->getEntityType());
      $access_token = $controller->generateAccessToken($account->uid);
      $id = $access_token->id;
    }
    $output = $this->view($id);

    return $output;
  }

}
