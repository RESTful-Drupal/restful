<?php


/**
 * @file
 * Contains RestfulInterface.
 */

interface RestfulInterface {

  /**
   * Constructor for the RESTful handler.
   *
   * @param $plugin
   *   The notifier plugin object. Note the "options" values might have
   *   been overriden in message_notify_send_message().
   */
  public function __construct($plugin);

  /**
   * Entry point to process a request.
   *
   * @return
   *   TRUE or FALSE based on delivery status.
   */
  public function process($path = '', $request = NULL, $method = 'GET');

  /**
   * Return the fields and properties that should be public.
   *
   * @return array
   */
  public function getPublicFields();


  /**
   * Determine if user can access notifier.
   */
  public function access();
}
