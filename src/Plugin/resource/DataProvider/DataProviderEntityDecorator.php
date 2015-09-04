<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntityDecorator.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\BadRequestException;

abstract class DataProviderEntityDecorator extends DataProviderDecorator implements DataProviderEntityInterface {

  /**
   * Decorated provider.
   *
   * @var DataProviderEntityInterface
   */
  protected $decorated;

  /**
   * Contstructs a DataProviderDecorator class.
   *
   * @param DataProviderEntityInterface $decorated
   *   The decorated data provider.
   */
  public function __construct(DataProviderEntityInterface $decorated) {
    // We are overriding the constructor only for the
    // DataProviderEntityInterface type hinting.
    parent::__construct($decorated);
  }

  /**
   * Allow manipulating the entity before it is saved.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The unsaved wrapped entity.
   */
  public function entityPreSave(\EntityDrupalWrapper $wrapper) {
    $this->decorated->entityPreSave($wrapper);
  }

  /**
   * Validate an entity before it is saved.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapped entity.
   *
   * @throws BadRequestException
   */
  public function entityValidate(\EntityDrupalWrapper $wrapper) {
    $this->decorated->entityValidate($wrapper);
  }

  /**
   * Gets a EFQ object.
   *
   * @return \EntityFieldQuery
   *   The object that inherits from \EntityFieldQuery.
   */
  public function EFQObject() {
    return $this->decorated->EFQObject();
  }

}
