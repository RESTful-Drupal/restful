<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\Resource\Resource.
 */

namespace Drupal\restful\Plugin\Resource;

use Drupal\Component\Plugin\PluginBase;

abstract class Resource extends PluginBase implements ResourceInterface {

  /**
   * The field definition object.
   *
   * @var ResourceFields
   */
  protected $fieldDefinitions;

  /**
   * Allows you to interact with the data source.
   *
   * Possible data sources are: entity system, db, plugin discovery, other
   * resources, ...
   *
   * @var DataProvider
   */
  protected $dataProvider;

  /**
   * Constructor.
   */
  public function __construct() {

  }

}
