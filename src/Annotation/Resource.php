<?php

/**
 * @file
 * Contains \Drupal\restful\Annotation\Resource.
 */

namespace Drupal\restful\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Resource annotation object.
 *
 * @ingroup plug_example_api
 *
 * @Annotation
 */
class Resource extends Plugin {

  /**
   * Major version.
   *
   * @var int
   */
  public $majorVersion = 1;

  /**
   * Minor version.
   *
   * @var int
   */
  public $minorVersion = 0;

  /**
   * Resource name.
   *
   * @var string
   */
  public $name;

  /**
   * Resource.
   *
   * This is used for the resource URL.
   *
   * @var string
   */
  public $resource;

  /**
   * Description.
   *
   * @var string
   */
  public $description = '';

  /**
   * Authentication types.
   *
   * @var array|bool
   */
  public $authenticationTypes = array();

  /**
   * Authentication optional.
   *
   * @var bool
   */
  public $authenticationOptional = FALSE;

  /**
   * Data provider options.
   *
   * Contains all the information for the data provider.
   *
   * @var array
   */
  public $dataProvider = array();

  /**
   * Cache render.
   *
   * Cache render options.
   *
   * @var array|bool
   */
  public $cacheRender = array();

  /**
   * Hook menu. FALSE if the resource should declare a menu item automatically.
   *
   * @var bool
   */
  public $hookMenu = FALSE;

  /**
   * The path to be used as the menu item.
   *
   * Leave it empty to create one automatically.
   *
   * @var string
   */
  public $hookItem;

  /**
   * Autocomplete options.
   *
   * 'string' => 'foo',
   * 'operator' => 'STARTS_WITH',
   *
   * @var array
   */
  public $autocomplete = array();

  /**
   * Options. Used mainly for file resources.
   *
   * Set the default validators, scheme, and replace as used in
   * file_save_upload().
   *
   * @var array
   */
  public $options = array();

  /**
   * Access control using the HTTP Access-Control-Allow-Origin header.
   *
   * @var string
   */
  public $allowOrigin;

  /**
   * Discoverable.
   *
   * @var bool
   */
  public $discoverable = FALSE;

  /**
   * URL parameters.
   *
   * @var array
   */
  public $urlParams = array();

  /**
   * {@inheritdoc}
   */
  public function getId() {
    // Our ID property is 'name', not 'id'.
    return $this->definition['name'];
  }

}
