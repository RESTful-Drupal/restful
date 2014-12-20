<?php

/**
 * @file
 * Contains \RestfulPropertySourceInterface.
 */

interface RestfulPropertySourceInterface {

  /**
   * Simple key value getter.
   *
   * @param string $key
   *   The key to get.
   * @param int $delta
   *   The delta for multivalue properties.
   *
   * @return mixed
   *   The value.
   */
  public function get($key, $delta = NULL);

  /**
   * Gets the raw source.
   *
   * @return mixed
   *   The data source.
   */
  public function getSource();

  /**
   * Sets the raw source.
   *
   * @param mixed $source
   *   The data source.
   */
  public function setSource($source);

  /**
   * Gets the source context.
   *
   * @return mixed
   *   The context.
   */
  public function getContext();

  /**
   * Sets the context.
   *
   * @param mixed $context
   *   The context.
   */
  public function setContext($context);

  /**
   * Property is multiple.
   *
   * @return boolean
   *   Returns TRUE if the property is multiple (cardinality > 1).
   */
  public function isMultiple();

  /**
   * Number of elements for the property.
   *
   * @return int
   *   Returns the number of elements of the property.
   */
  public function count();

}
