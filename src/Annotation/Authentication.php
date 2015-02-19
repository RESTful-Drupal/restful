<?php

/**
 * @file
 * Contains \Drupal\restful\Annotation\RateLimit.
 */

namespace Drupal\restful\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a RateLimit annotation object.
 *
 * @ingroup plug_example_api
 *
 * @Annotation
 */
class Authentication extends Plugin {

  /**
   * The human readable name.
   *
   * @var string
   */
  public $label;

  /**
   * The description.
   *
   * @var string
   */
  public $description;

  /**
   * Extra options
   *
   * @var array
   */
  public $options;
}
