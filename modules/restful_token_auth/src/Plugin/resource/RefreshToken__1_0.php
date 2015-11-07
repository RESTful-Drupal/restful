<?php

/**
 * @file
 * Contains Drupal\restful_token_auth\Plugin\resource\RefreshToken__1_0.
 */

namespace Drupal\restful_token_auth\Plugin\resource;


use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Util\EntityFieldQuery;
use Drupal\restful_token_auth\Entity\RestfulTokenAuth;

/**
 * Class RefreshToken__1_0
 * @package Drupal\restful_token_auth\Plugin\resource
 *
 * @Resource(
 *   name = "refresh_token:1.0",
 *   resource = "refresh_token",
 *   label = "Refresh token authentication",
 *   description = "Export the refresh token authentication resource.",
 *   authenticationOptional = TRUE,
 *   dataProvider = {
 *     "entityType": "restful_token_auth",
 *     "bundles": {
 *       "access_token"
 *     },
 *   },
 *   formatter = "single_json",
 *   menuItem = "refresh-token",
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class RefreshToken__1_0 extends TokenAuthenticationBase implements ResourceInterface {

  /**
   * Overrides \RestfulBase::controllersInfo().
   */
  public function controllersInfo() {
    return array(
      '.*' => array(
        // Get or create a new token.
        RequestInterface::METHOD_GET => 'refreshToken',
      ),
    );
  }

  /**
   * Create a token for a user, and return its value.
   *
   * @param string $token
   *   The refresh token.
   *
   * @throws BadRequestException
   *
   * @return RestfulTokenAuth
   *   The new access token.
   */
  public function refreshToken($token) {
    // Check if there is a token that did not expire yet.
    /* @var \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntityInterface $data_provider */
    $data_provider = $this->getDataProvider();
    $query = $data_provider->EFQObject();
    $results = $query
      ->entityCondition('entity_type', $this->entityType)
      ->entityCondition('bundle', 'refresh_token')
      ->propertyCondition('token', $token)
      ->range(0, 1)
      ->execute();

    if (empty($results['restful_token_auth'])) {
      throw new BadRequestException('Invalid refresh token.');
    }

    // Remove the refresh token once used.
    $refresh_token = entity_load_single('restful_token_auth', key($results['restful_token_auth']));
    $uid = $refresh_token->uid;

    // Get the access token linked to this refresh token then do some cleanup.
    $access_token_query = new EntityFieldQuery();
    $access_token_reference = $access_token_query
      ->entityCondition('entity_type', 'restful_token_auth')
      ->entityCondition('bundle', 'access_token')
      ->fieldCondition('refresh_token_reference', 'target_id', $refresh_token->id)
      ->range(0, 1)
      ->execute();

    if (!empty($access_token_reference['restful_token_auth'])) {
      $access_token = key($access_token_reference['restful_token_auth']);
      entity_delete('restful_token_auth', $access_token);
    }

    $refresh_token->delete();

    // Create the new access token and return it.
    /* @var \Drupal\restful_token_auth\Entity\RestfulTokenAuthController $controller */
    $controller = entity_get_controller($this->getEntityType());
    $token = $controller->generateAccessToken($uid);
    return $this->view($token->id);
  }

}
