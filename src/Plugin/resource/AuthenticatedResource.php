<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\AuthenticatedResource
 */

namespace Drupal\restful\Plugin\resource;

use Drupal\Component\Plugin\PluginBase;
use Drupal\restful\Authentication\AuthenticationManager;
use Drupal\restful\Exception\NotImplementedException;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderInterface;

class AuthenticatedResource extends PluginBase implements AuthenticatedResourceInterface {

  /**
   * The decorated resource.
   *
   * @var ResourceInterface
   */
  protected $subject;

  /**
   * Authentication manager.
   *
   * @var AuthenticationManager
   */
  protected $authenticationManager;

  /**
   * {@inheritdoc}
   */
  public function setAuthenticationManager(AuthenticationManager $authentication_manager) {
    $this->authenticationManager = $authentication_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthenticationManager() {
    return $this->authenticationManager;
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
   * Proxy method to get the account from the authenticationManager.
   *
   * {@inheritdoc}
   */
  public function getAccount($cache = TRUE) {
    // The request.
    $request = $this->subject->getRequest();

    $account = $this->getAuthenticationManager()->getAccount($request, $cache);

    return $account;
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
   * Constructs a Drupal\Component\Plugin\PluginBase object.
   *
   * @param ResourceInterface $subject
   *   The decorated object.
   * @param AuthenticationManager $auth_manager
   *   (optional) Injected authentication manager.
   */
  public function __construct(ResourceInterface $subject, AuthenticationManager $auth_manager = NULL) {
    $this->subject = $subject;
    $this->authenticationManager = $auth_manager ? $auth_manager : new AuthenticationManager();
  }

  /**
   * {@inheritdoc}
   */
  public function process() {
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
  public function getResourceName() {
    return $this->subject->getResourceName();
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
