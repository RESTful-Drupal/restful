<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldResourceInterface.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

interface ResourceFieldResourceInterface extends ResourceFieldInterface {

  /**
   * Gets the ID on the referenced resource.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data interpreter to get the compound ID.
   *
   * @return string|string[]
   *   The identifier(s) used to access the resource.
   */
  public function getResourceId(DataInterpreterInterface $interpreter);

  /**
   * Gets the machine name, without version, of the referenced resource.
   *
   * @return string
   *   The name.
   */
  public function getResourceMachineName();

  /**
   * Gets the resource plugin for more complex interactions in render time.
   *
   * @param ResourceInterface $resource_plugin
   *   The plugin to set.
   */
  public function setResourcePlugin($resource_plugin);

  /**
   * Gets the destination resource plugin.
   *
   * @return ResourceInterface
   *   The plugin.
   */
  public function getResourcePlugin();

}
