<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\RateLimitedResource
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\RateLimit\RateLimitManager;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

class RateLimitedResource extends PluginBase implements ResourceInterface {

  /**
   * The decorated resource.
   *
   * @var ResourceInterface
   */
  protected $subject;

  /**
   * Authentication manager.
   *
   * @var RateLimitManager
   */
  protected $rateLimitManager;

  /**
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param ResourceInterface $subject
   *   The decorated object.
   * @param RateLimitManager $rate_limit_manager
   *   Injected rate limit manager.
   */
  public function __construct(ResourceInterface $subject, RateLimitManager $rate_limit_manager) {
    $this->subject = $subject;
    $this->rateLimitManager = $rate_limit_manager;
  }

  /**
   * Setter for $rateLimitManager.
   *
   * @param RateLimitManager $rate_limit_manager
   *   The rate limit manager.
   */
  protected function setRateLimitManager(RateLimitManager $rate_limit_manager) {
    $this->rateLimitManager = $rate_limit_manager;
  }

  /**
   * Getter for $rate_limit_manager.
   *
   * @return RateLimitManager
   *   The rate limit manager.
   */
  protected function getRateLimitManager() {
    return $this->rateLimitManager;
  }

  /**
   * Data provider factory.
   *
   * @return DataProviderInterface
   *   The data provider for this resource.
   *
   * @throws NotImplementedException
   */
  public function dataProviderFactory() {
    return $this->subject->dataProviderFactory();
  }

  /**
   * Proxy method to get the account from the rateLimitManager.
   *
   * {@inheritdoc}
   */
  public function getAccount($cache = TRUE) {
    return $this->subject->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->subject->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->subject->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($path) {
    $this->subject->setPath($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    return $this->subject->getFieldDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getDataProvider() {
    return $this->subject->getDataProvider();
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceName() {
    return $this->subject->getResourceName();
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
    // This will throw the appropriate exception if needed.
    $this->getRateLimitManager()->checkRateLimit($this->getRequest());
    return $this->subject->process();
  }

  /**
   * {@inheritdoc}
   */
  public function controllersInfo() {
    return $this->subject->controllersInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function getControllers() {
    return $this->subject->getControllers();
  }

  /**
   * {@inheritdoc}
   */
  public function index($path) {
    return $this->subject->index($path);
  }

  /**
   * {@inheritdoc}
   */
  public function view($path) {
    return $this->subject->view($path);
  }

  /**
   * {@inheritdoc}
   */
  public function create($path) {
    return $this->subject->create($path);
  }

  /**
   * {@inheritdoc}
   */
  public function update($path) {
    return $this->subject->update($path);
  }

  /**
   * {@inheritdoc}
   */
  public function replace($path) {
    return $this->subject->replace($path);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($path) {
    $this->subject->remove($path);
  }

  /**
   * {@inheritdoc}
   */
  public function getVersion() {
    return $this->subject->getVersion();
  }

}
