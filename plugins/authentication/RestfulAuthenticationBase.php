<?php

/**
 * @file
 * Contains RestfulAuthenticationBase.
 */

abstract class RestfulAuthenticationBase implements RestfulAuthenticationInterface {

  /**
   * Settings from the plugin definition.
   *
   * @var array
   */
  protected $settings;

  /**
   * The plugin definition.
   *
   * @var array
   */
  protected $plugin;

  /**
   * Constructor.
   */
  public function __construct($plugin) {
    $this->settings = $plugin['settings'];
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    // By default assume that the request can be checked for authentication.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->plugin['name'];
  }

}
