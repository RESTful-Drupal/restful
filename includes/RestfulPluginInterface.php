<?php

/**
 * @file
 * Contains RestfulPluginInterface
 */

interface RestfulPluginInterface {

  /**
   * Gets information about the restful plugin.
   *
   * @return array
   *   The plugin definition.
   */
  public function getPlugin();

  /**
   * Sets information about the restful plugin.
   *
   * @param array $plugin
   *   The plugin definition.
   */
  public function setPlugin($plugin);

  /**
   * Gets information about the restful plugin key.
   *
   * @param string $key
   *   The name of the key to return.
   *
   * @return mixed
   *   Depends on the requested value. NULL if the key is not found.
   */
  public function getPluginKey($key);

  /**
   * Gets information about the restful plugin key.
   *
   * @param string $key
   *   The name of the key to return.
   * @param mixed $value
   *   The value to set.
   */
  public function setPluginKey($key, $value);

}
