<?php


/**
 * @file
 * Contains RestfulEntityInterface.
 */

interface RestfulEntityInterface extends RestfulInterface {

  /**
   * Return the properties that should be public.
   *
   * @return array
   */
  public function publicFieldsInfo();

  /**
   * Return the properties that should be public after processing.
   *
   * Default values would be assigned to the properties declared in
   * \RestfulInterface::publicFieldsInfo().
   *
   * @return array
   */
  public function getPublicFields();
}
