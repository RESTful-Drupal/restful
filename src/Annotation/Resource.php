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
 * @ingroup resource_api
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
   * Formatter.
   *
   * @var string
   */
  public $formatter;

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
  public $renderCache = array();

  /**
   * Hook menu. TRUE if the resource should declare a menu item automatically.
   *
   * @var bool
   */
  public $hookMenu = TRUE;

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
   * Access control using the HTTP Access-Control-Allow-Origin header.
   *
   * @var string
   */
  public $allowOrigin;

  /**
   * Determines if a resource should be discoverable, and appear under /api.
   *
   * @var bool
   */
  public $discoverable = TRUE;

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
    // The ID of the resource plugin is its name.
    return $this->definition['name'];
  }

}
