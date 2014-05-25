<?php

/**
 * @file
 * Contains RestfulAuthTokenAuthTokens.
 */

class RestfulAuthTokenAuthTokens extends RestfulEntityBaseNode {

  /**
   * Nested array that provides information about what method to call for each
   * route pattern.
   *
   * @var array $controllers
   */
  protected $controllers = array(
    '' => array(
      // GET returns a list of entities.
      'get' => 'createAndGetToken',
    ),
  );

  public function createAndGetToken() {
    $account = $this->getAccount();

    // Check if there is a token that did not expire yet.

    // Return the token.

  }
}
