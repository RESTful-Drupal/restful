<?php
/**
 * @file
 * Contains Drupal\restful\Plugin\resource\DataProvider\RateLimitedDataProvider.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;
use Drupal\restful\RateLimit\RateLimitManagerInterface;

class RateLimitedDataProvider implements DataProviderInterface {

  /**
   * The decorated object.
   *
   * @var DataProviderInterface
   */
  protected $subject;

  /**
   * The rate limit manager.
   *
   * @var RateLimitManagerInterface
   */
  protected $rateLimitManager;

  /**
   * Constructs a CachedDataProvider object.
   *
   * @param DataProviderInterface $subject
   *   The data provider to add caching to.
   */
  public function __construct(DataProviderInterface $subject) {
    $this->subject = $subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getRange() {
    return $this->subject->getRange();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
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
  public function getLangCode() {
    return $this->subject->getLangCode();
  }

  /**
   * {@inheritdoc}
   */
  public function setLangCode($langcode) {
    $this->subject->setLangCode($langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->subject->getOptions();
  }

  /**
   * {@inheritdoc}
   */
  public function addOptions(array $options) {
    $this->subject->addOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function getContext($identifier) {
    return $this->subject->getContext($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    // Check the rate limits and carry on.
    $this->getRateLimitManager()->checkRateLimit($this->getRequest());

    return $this->subject->index();
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->subject->count();
  }

  /**
   * {@inheritdoc}
   */
  public function create($object) {
    // Check the rate limits and carry on.
    $this->getRateLimitManager()->checkRateLimit($this->getRequest());

    return $this->subject->create($object);
  }

  /**
   * {@inheritdoc}
   */
  public function view($identifier) {
    // Check the rate limits and carry on.
    $this->getRateLimitManager()->checkRateLimit($this->getRequest());

    return $this->subject->view($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $identifiers) {
    // Check the rate limits and carry on.
    $this->getRateLimitManager()->checkRateLimit($this->getRequest());
    $output = $this->subject->viewMultiple($identifiers);

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function update($identifier, $object, $replace = TRUE) {
    return $this->subject->update($identifier, $object, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function remove($identifier) {
    $this->subject->remove($identifier);
  }

  /**
   * {@inheritdoc}
   */
  public function methodAccess(ResourceFieldInterface $resource_field) {
    $this->subject->methodAccess($resource_field);
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    return $this->subject->canonicalPath($path);
  }

  /**
   * Get the rate limit manager lazily.
   *
   * @return RateLimitManagerInterface
   *   The rate limit manager.
   *
   * @throws ServerConfigurationException
   */
  protected function getRateLimitManager() {
    if ($this->rateLimitManager) {
      return $this->rateLimitManager;
    }
    if (empty($this->getOptions()['rateLimitManager'])) {
      throw new ServerConfigurationException('Rate Limit Manager not available for the rate limited data provider.');
    }
    $this->rateLimitManager = $this->getOptions()['rateLimitManager'];
    return $this->rateLimitManager;
  }

}
