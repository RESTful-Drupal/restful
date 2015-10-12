<?php

/**
 * @file
 * Contains \Drupal\restful\RenderCache\Entity\CacheFragment.
 */

namespace Drupal\restful\RenderCache\Entity;

class CacheFragment extends \Entity {

  /**
   * The identifier hash for the tag.
   *
   * @var string
   */
  public $hash;

  /**
   * Tag type.
   *
   * @var string
   */
  public $type;

  /**
   * Tag value.
   *
   * @var string
   */
  public $value;

  /**
   * The hash to be used as the cache ID.
   *
   * @return string
   *   The hash.
   */
  public function getHash() {
    return $this->hash;
  }

  /**
   * The hash to be used as the cache ID.
   *
   * @param string $hash
   *   The hash.
   */
  public function setHash($hash) {
    $this->hash = $hash;
  }

  /**
   * Get the type.
   *
   * @return string
   *   The type.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set the type.
   *
   * @param string $type
   *   The type.
   */
  public function setType($type) {
    $this->type = $type;
  }

  /**
   * Get the value.
   *
   * @return string
   *   The value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Set the value.
   *
   * @param string $value
   *   The value.
   */
  public function setValue($value) {
    $this->value = $value;
  }

}
