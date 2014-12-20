<?php

/**
 * @file
 * Contains \RestfulPropertySourceEMW.
 */

class RestfulPropertySourceEMW extends \RestfulPropertySourceBase implements \RestfulPropertySourceInterface {

  private $position = 0;

  /**
   * Constructor.
   *
   * @param \EntityDrupalWrapper $source
   *   Contains the data object.
   */
  public function __construct(\EntityDrupalWrapper $source) {
    $this->source = $source;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $delta = NULL) {
    $context = $this->getContext();
    $method = $context['wrapper_method'];
    $resource = $context['resource'] ?: NULL;
    $item_wrapper = $this->itemWrapper($key, $delta);

    if ($resource) {
      $value = NULL;
      // value = $this->getValueFromResource($item_wrapper, $property, $resource, $public_field_name, $wrapper->getIdentifier());
    }
    else {
      // Wrapper method.
      $value = $item_wrapper->{$method}();
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return $this->itemWrapper() instanceof EntityListWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    if ($this->isMultiple()) {
      return $this->itemWrapper()->count();
    }
    return 1;
  }

  /**
   * Get the item wrapper.
   *
   * @param string $property
   *   The name of the property to get.
   * @param int $delta
   *   The delta.
   *
   * @return \EntityDrupalWrapper
   *   The sub wrapper.
   */
  protected function itemWrapper($property = NULL, $delta = NULL) {
    $context = $this->getContext();
    if (!isset($property)) {
      $property = $context['property'];
    }
    $wrapper = $this->getSource();
    $item_wrapper = $context['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$property};
    if (isset($delta)) {
      $item_wrapper = $item_wrapper->get($delta);
    }
    if ($context['sub_property'] && $item_wrapper->{$context['wrapper_method']}()) {
      $item_wrapper = $item_wrapper->{$context['sub_property']};
    }
    return $item_wrapper;
  }

}
