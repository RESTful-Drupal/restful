<?php

/**
 * @file
 * Contains \RestfulDataProviderDbQueryInterface
 */

interface RestfulDataProviderDbQueryInterface {

  /**
   * Prepare a query for RestfulEntityBase::getList().
   *
   * @return \SelectQuery
   *   The query object.
   */
  public function getQueryForList();

  /**
   * Prepare a query for RestfulEntityBase::getTotalCount().
   *
   * @return \SelectQuery
   *   The query object.
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

  /**
   * Prepares the output array from the database row object.
   *
   * @param object $row
   *   The database row object.
   *
   * @return array
   *   The structured array ready to be formatted.
   */
  public function mapDbRowToPublicFields($row);
}
