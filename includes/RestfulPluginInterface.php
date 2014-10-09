<?php

/**
 * @file
 * Contains RestfulPluginInterface
 */

interface RestfulPluginInterface {

  /**
   * Gets information about the restful plugin.
   *
   * @param string $key
   *   (optional) The name of the key to return.
   *
   * @return mixed
   *   Depends on the requested value.
   */
  public function getPluginInfo($key = NULL);

  /**
   * Checks if the key is populated in the plugin definition.
   *
   * @param string $key
   *   The plugin property to get. NULL to get all properties.
   *
   * @return boolean
   *   TRUE if the key is populated. FALSE otherwise.
   */
  public function isNull($key);

  /**
   * Checks if the key empty in the plugin definition.
   *
   * @param string $key
   *   The plugin property to get. NULL to get all properties.
   *
   * @return boolean
   *   TRUE if the key is empty. FALSE otherwise.
   */
  public function isEmpty($key);

}
