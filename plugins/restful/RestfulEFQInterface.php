<?php

/**
 * @file
 * Contains \RestfulEFQInterface
 */

interface RestfulEFQInterface {

  /**
   * Prepare a query for RestfulEntityBase::getList().
   *
   * @return EntityFieldQuery
   *   The EntityFieldQuery object.
   */
  public function getQueryForList();

  /**
   * Prepare a query for RestfulEntityBase::getTotalCount().
   *
   * @return EntityFieldQuery
   *   The EntityFieldQuery object.
   *
   * @throws RestfulBadRequestException
   */
  public function getQueryCount();

  /**
   * Helper method to get the total count of entities that match certain
   * request.
   *
   * @return int
   *   The total number of results without including pagination.
   */
  public function getTotalCount();
}
