<?php

/**
 * @file
 * Contains RestfulTestArticlesResource__1_1.
 */

class RestfulTestArticlesResource__1_1 extends RestfulExampleArticlesResource {
  /**
   * Overrides RestfulDataProviderEFQ::defaultSortInfo().
   */
  public function defaultSortInfo() {
    return array(
      'label' => 'ASC',
      'id' => 'DESC'
    );
  }
}
