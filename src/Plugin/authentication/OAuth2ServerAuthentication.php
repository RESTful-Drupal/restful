<?php

namespace Drupal\restful\Plugin\authentication;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Exception\UnauthorizedException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\ResourcePluginManager;

/**
 * Authentication support for oauth2_server.
 *
 * @Authentication(
 *   id = "oauth2_auth",
 *   label = "OAuth2 authentication",
 *   description = "Authenticate requests based on oauth2_server auth.",
 * )
 */
class OAuth2ServerAuthentication extends Authentication {

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request) {
    return module_exists('oauth2_server') && $this->getResourcePluginIdFromRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(RequestInterface $request) {
    $oauth2_info = $this->getOAuth2Info($request);
    if (!$oauth2_info) {
      return NULL;
    }

    $result = oauth2_server_check_access($oauth2_info['server'], $oauth2_info['scope']);
    if ($result instanceof \OAuth2\Response) {
      throw new UnauthorizedException($result->getResponseBody(), $result->getStatusCode());
    }
    elseif (is_array($result) && !empty($result['user_id'])) {
      return user_load($result['user_id']);
    }
  }

//  protected function getOAuth2Info() {
//    return [variable_get('oauth2_server_restful_server'), variable_get('oauth2_server_restful_scope')];
//  }

  protected function getOAuth2Info($request) {
    $plugin_id = $this->getResourcePluginIdFromRequest();
    $plugin = ResourcePluginManager::create('cache', $request)->getDefinition($plugin_id);

    $server = !empty($plugin['oauth2Server']) ? $plugin['oauth2Server'] : variable_get('oauth2_server_restful_server');
    if (!$server) {
      return NULL;
    }

    $scope = !empty($plugin['oauth2Scope']) ? $plugin['oauth2Scope'] : variable_get('oauth2_server_restful_scope');
    return ['server' => $server, 'scope' =>$scope];
  }

  protected function getResourcePluginIdFromRequest() {
    $resource_name = restful()->getResourceManager()->getResourceIdFromRequest();
    $version = restful()->getResourceManager()->getVersionFromRequest();

    if (!$resource_name || !$version) {
      return NULL;
    }

    return $resource_name . PluginBase::DERIVATIVE_SEPARATOR . $version[0] . '.' . $version[1];
  }

}
