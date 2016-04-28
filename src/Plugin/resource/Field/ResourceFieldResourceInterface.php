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
   * Gets the destination resource plugin.
   *
   * @return ResourceInterface
   *   The plugin.
   */
  public function getResourcePlugin();

  /**
   * Gets the table column for joins.
   *
   * @return string
   *   The column to make a join for nested filters.
   */
  public function getTargetColumn();

}
