<?php

/**
 * @file
 * Contains \RestfulDataProviderEFQInterface
 */

interface RestfulDataProviderEFQInterface {

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
   * Get the total count of entities that match certain request.
   *
   * @return int
   *   The total number of results without including pagination.
   */
  public function getTotalCount();
}
