<?php

/**
 * @file
 * Authentication base class
 */

class RestfulAuthorizationBase implements RestfulAuthorizationInterface {

  /**
   * Settings from the plugin definition.
   *
   * @var array
   */
  protected $settings;

  /**
   * Indicates if the request needs to be authenticated before it can be
   * authorized.
   *
   * @var bool
   */
  protected $needsAuthentication;

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
    $this->needsAuthentication = !empty($plugin['authentication']);
    $this->plugin = $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function authorize() {
    // This implementation is rather shallow. It will authorize all requests if
    // they don't need authentication. If they do the request will be authorized
    // if the user can be authenticated.
    if ($this->needsAuthentication) {
      $account = $this->authenticate();
      return !empty($account);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    // Since not all authorization types require authentication, give a sensible
    // default.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->plugin['name'];
  }

}
