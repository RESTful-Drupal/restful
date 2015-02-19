<?php

/**
 * @file
 * Contains \Drupal\restful\Http\HttpHeaderNull
 */

namespace Drupal\restful\Http;

class HttpHeaderNull implements HttpHeaderInterface {

  /**
   * Header ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Header name.
   *
   * @var string
   */
  protected $name;

  /**
   * Header values.
   *
   * @var array
   */
  protected $values = array();

  /**
   * Header extras.
   *
   * @var string
   */
  protected $extras;

  /**
   * Constructor.
   */
  public function __construct($name, array $values, $extras) {}

  /**
   * {@inheritdoc}
   */
  public static function create($key, $value) {
    return new static(NULL, array(), NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    return $this->values;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueString() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return NULL;
  }

  /**
   * Returns the string version of the header.
   *
   * @return string
   */
  public function __toString() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($values) {}

  /**
   * {@inheritdoc}
   */
  public function append($value) {}

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateId($name) {
    return NULL;
  }

}
