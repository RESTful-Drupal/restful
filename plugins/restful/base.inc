<?php


/**
 * @file
 * Contains RestfulBase.
 */

/**
 * An abstract implementation of RestfulInterface.
 */
abstract class RestfulBase implements RestfulInterface {

  /**
   * The plugin definition.
   */
  protected $plugin;

  /**
   * The request.
   */
  protected $request;


  public function __construct($plugin, $request) {
    $this->plugin = $plugin;
    $this->request = $request;
  }

  public function process() {
  }

  public function access() {
    return TRUE;
  }
}
