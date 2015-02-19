<?php

/**
 * @file
 * Contains \Drupal\restful\Annotation\Formatter.
 */

namespace Drupal\restful\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Formatter annotation object.
 *
 * @ingroup plug_example_api
 *
 * @Annotation
 */
class Formatter extends Plugin {

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
   * Information about the curie
   *
   * @var array
   */
  public $curie;

}
