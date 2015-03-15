<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreter.
 */

namespace Drupal\restful\Plugin\resource\DataInterpreter;

abstract class DataInterpreterBase implements DataInterpreterInterface {

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
   * Constructs a DataInterpreter object.
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
