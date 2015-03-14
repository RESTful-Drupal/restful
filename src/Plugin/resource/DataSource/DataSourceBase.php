<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataSource\DataSource.
 */

namespace Drupal\restful\Plugin\resource\DataSource;

abstract class DataSourceBase implements DataSourceInterface {

  /**
   * The account.
   *
   * @var object
   */
  protected $account;

  /**
   * The wrapper.
   *
   * @var mixed
   */
  protected $wrapper;

  /**
   * Constructs a DataSource object.
   *
   * @param object $account
   *   The fully loaded object.
   * @param mixed $wrapper
   *   The container wrapper.
   */
  public function __construct($account, $wrapper) {
    $this->account = $account;
    $this->wrapper = $wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapper() {
    return $this->wrapper;
  }

}
