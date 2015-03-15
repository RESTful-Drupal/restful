<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;

interface DataInterpreterInterface {

  /**
   * Get the account.
   *
   * @return object
   *   The fully loaded account.
   */
  public function getAccount();

  /**
   * Get the wrapper.
   *
   * @return mixed
   *   The entity metadata wrapper describing the entity. Every data source will
   *   return a different wrapper type. For instance a data source for entities
   *   will return an \EntityDrupalWrapper, a data source for DB query will
   *   return a DatabaseColumnWrapper, etc.
   */
  public function getWrapper();

}
