<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\authentication\Authentication
 */

namespace Drupal\restful\Plugin\authentication;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Http\RequestInterface;

abstract class Authentication extends PluginBase implements AuthenticationInterface {

  /**
   * Token value for token generation functions.
   */
  const TOKEN_VALUE = 'rest';

  /**
   * Settings from the plugin definition.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function applies(RequestInterface $request) {
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
