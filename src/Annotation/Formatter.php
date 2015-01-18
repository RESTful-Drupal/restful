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
   * The plugin ID. Machine name.
   *
   * @var string
   */
  public $name;

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

}
