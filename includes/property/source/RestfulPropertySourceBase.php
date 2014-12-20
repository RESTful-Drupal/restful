<?php

/**
 * @file
 * Contains \RestfulPropertySourceInterface
 */

abstract class RestfulPropertySourceBase implements RestfulPropertySourceInterface {

  protected $source;
  protected $context;

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    return $this->source = $source;
  }

}
