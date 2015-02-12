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
   * Create operation.
   *
   * @param mixed $object
   *   The ID of thing to be created.
   *
   * @return array
   *   An array of structured data for the thing that was created.
   */
  public function create($object);

  /**
   * Read operation.
   *
   * @param mixed $subject
   *   The ID of thing being viewed.
   *
   * @return array
   *   An array of data for the thing being viewed.
   */
  public function view($subject);

  /**
   * Read operation.
   *
   * @param array $subjects
   *   The array of IDs of things being viewed.
   *
   * @return array
   *   An array of structured data for the things being viewed.
   */
  public function viewMultiple(array $subjects);

  /**
   * Update operation.
   *
   * @param mixed $subject
   *   The ID of thing to be updated.
   * @param mixed $object
   *   The thing that will be set.
   * @param bool $replace
   *   TRUE if the contents of $object will replace $subject entirely. FALSE if
   *   only what is set in $object will replace those properties in $subject.
   *
   * @return array
   *   An array of structured data for the thing that was updated.
   */
  public function update($subject, $object, $replace = TRUE);

  /**
   * Delete operation.
   *
   * @param mixed $subject
   *   The ID of thing to be removed.
   */
  public function remove($subject);

}
