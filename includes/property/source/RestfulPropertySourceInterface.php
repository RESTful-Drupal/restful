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
   *
   * @return mixed
   *   The value.
   */
  public function get($key);

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
   * Get the iterator to traverse multiple elements.
   *
   * @return Iterator
   *   The iterator.
   */
  public function iterator();

}
