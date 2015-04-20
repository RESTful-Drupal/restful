<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProviderEntityInterface.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\BadRequestException;

interface DataProviderEntityInterface extends DataProviderInterface {

  /**
   * Allow manipulating the entity before it is saved.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The unsaved wrapped entity.
   */
  public function entityPreSave(\EntityDrupalWrapper $wrapper);

  /**
   * Validate an entity before it is saved.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapped entity.
   *
   * @throws BadRequestException
   */
  public function entityValidate(\EntityDrupalWrapper $wrapper);

  /**
   * Gets a EFQ object.
   *
   * @return \EntityFieldQuery
   *   The object that inherits from \EntityFieldQuery.
   */
  public function EFQObject();

}
