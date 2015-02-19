<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\authentication\Authentication
 */

namespace Drupal\restful\Plugin\authentication;

use Drupal\Component\Plugin\PluginBase;

abstract class Authentication extends PluginBase implements AuthenticationInterface {

  /**
   * Settings from the plugin definition.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function applies(array $request = array(), $method = \RestfulInterface::GET) {
    // By default assume that the request can be checked for authentication.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->getPluginId();
  }

}
