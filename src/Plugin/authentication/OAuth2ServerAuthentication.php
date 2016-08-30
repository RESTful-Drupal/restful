<?php

namespace Drupal\restful\Plugin\authentication;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\ResourcePluginManager;

/**
 * Authentication support for oauth2_server.
 *
 * @Authentication(
 *   id = "oauth2",
 *   label = "OAuth2 authentication",
 *   description = "Authenticate requests based on oauth2_server auth.",
 * )
 */
class OAuth2ServerAuthentication extends Authentication {

  /**
   * The resource manager.
   *
   * @var \Drupal\restful\Resource\ResourceManagerInterface
   */
  protected $resourceManager;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->resourceManager = restful()->getResourceManager();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request) {
    return module_exists('oauth2_server') && $this->getOAuth2Info($request);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(RequestInterface $request) {
    $oauth2_info = $this->getOAuth2Info($request);
    if (!$oauth2_info) {
      throw new ServerConfigurationException('The resource uses OAuth2 authentication but does not specify the OAuth2 server.');
    }

    $result = oauth2_server_check_access($oauth2_info['server'], $oauth2_info['scope']);
    if ($result instanceof \OAuth2\Response) {
      throw new UnauthorizedException($result->getResponseBody(), $result->getStatusCode());
    }
    elseif (empty($result['user_id'])) {
      return NULL;
    }
    return user_load($result['user_id']);
  }

  /**
   * Get OAuth2 information from the request.
   *
   * @param \Drupal\restful\Http\RequestInterface $request
   *   The request.
   *
   * @return array|null
   *   Simple associative array with the following keys:
   *   - server: The OAuth2 server to authenticate against.
   *   - scope: The scope required for the resource.
   */
  protected function getOAuth2Info(RequestInterface $request) {
    $plugin_id = $this->getResourcePluginIdFromRequest();
    if (!$plugin_id) {
      // If the plugin can't be determined, it is probably not a request to the
      // resource but something else that is just loading all the plugins.
      return NULL;
    }

    $plugin_definition = ResourcePluginManager::create('cache', $request)->getDefinition($plugin_id);

    if (empty($plugin_definition['oauth2Server'])) {
      return NULL;
    }

    $server = $plugin_definition['oauth2Server'];
    $scope = !empty($plugin_definition['oauth2Scope']) ? $plugin_definition['oauth2Scope'] : '';
    return ['server' => $server, 'scope' => $scope];
  }

  /**
   * Get the resource plugin id requested.
   *
   * @return null|string
   *   The plugin id of the resource that was requested.
   */
  protected function getResourcePluginIdFromRequest() {
    $resource_name = $this->resourceManager->getResourceIdFromRequest();
    $version = $this->resourceManager->getVersionFromRequest();

    if (!$resource_name || !$version) {
      return NULL;
    }

    return $resource_name . PluginBase::DERIVATIVE_SEPARATOR . $version[0] . '.' . $version[1];
  }

}
