<?php

/**
 * @file
 * Contains Drupal\restful_token_auth\Plugin\resource\RefreshToken__1_0.
 */

namespace Drupal\restful_token_auth\Plugin\resource;


use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
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
 *   majorVersion = 1,
 *   minorVersion = 0
 * )
 */
class RefreshToken__1_0 extends TokenAuthenticationBase implements ResourceInterface {

  /**
   * Constructs a RefreshToken__1_0 object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Set dynamic options that cannot be set in the annotation.
    // Set the menuItem. restful_token_auth_menu_alter will add custom settings.
    $plugin_definition = $this->getPluginDefinition();
    $plugin_definition['menuItem'] = variable_get('restful_hook_menu_base_path', 'api') . '/refresh-token';

    // Store the plugin definition.
    $this->pluginDefinition = $plugin_definition;
  }

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
    $account = $this->getAccount();
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
    $refresh_token->delete();

    // Create the new access token and return it.
    /* @var \Drupal\restful_token_auth\Entity\RestfulTokenAuthController $controller */
    $controller = entity_get_controller($this->getEntityType());
    $token = $controller->generateAccessToken($account->uid);
    return $this->view($token->id);
  }

}
