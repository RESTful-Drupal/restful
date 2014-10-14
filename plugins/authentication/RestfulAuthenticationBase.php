<?php

/**
 * @file
 * Contains RestfulAuthenticationBase.
 */

abstract class RestfulAuthenticationBase extends \RestfulPluginBase implements \RestfulAuthenticationInterface {

  /**
   * Settings from the plugin definition.
   *
   * @var array
   */
  protected $settings;

  /**
   * Constructor.
   */
  public function __construct($plugin) {
    $this->setPlugin($plugin);
    $this->settings = $this->getPluginKey('settings');
  }

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
    return $this->getPluginKey('name');
  }

}
