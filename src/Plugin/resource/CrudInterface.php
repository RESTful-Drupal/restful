<?php

/**
 * @file
 * Contains \Drupal\restful\Resource\CrudInterface.
 */

namespace Drupal\restful\Plugin\resource;

interface CrudInterface {

  /**
   * List operation.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function index();

  /**
   * Counts the total results for the index call.
   *
   * @return int
   *   The total number of results for the index call.
   */
  public function count();

  /**
   * Create operation.
   *
   * @param mixed $object
   *   The thing to be created.
   *
   * @return array
   *   An array of structured data for the thing that was created.
   */
  public function create($object);

  /**
   * Read operation.
   *
   * @param mixed $identifier
   *   The ID of thing being viewed.
   *
   * @return array
   *   An array of data for the thing being viewed.
   */
  public function view($identifier);

  /**
   * Read operation.
   *
   * @param array $identifiers
   *   The array of IDs of things being viewed.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function viewMultiple(array $identifiers);

  /**
   * Update operation.
   *
   * @param mixed $identifier
   *   The ID of thing to be updated.
   * @param mixed $object
   *   The thing that will be set.
   * @param bool $replace
   *   TRUE if the contents of $object will replace $identifier entirely. FALSE
   *   if only what is set in $object will replace those properties in
   *   $identifier.
   *
   * @return array
   *   An array of structured data for the thing that was updated.
   */
  public function update($identifier, $object, $replace = FALSE);

  /**
   * Delete operation.
   *
   * @param mixed $identifier
   *   The ID of thing to be removed.
   */
  public function remove($identifier);

}
