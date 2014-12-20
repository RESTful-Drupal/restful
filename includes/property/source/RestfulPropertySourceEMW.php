<?php

/**
 * @file
 * Contains \RestfulPropertySourceEMW.
 */

class RestfulPropertySourceEMW extends \RestfulPropertySourceBase implements \RestfulPropertySourceInterface, Iterator {

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
  public function get($key) {
    $context = $this->getContext();
    $property = $context['property'];
    $method = $context['wrapper_method'];
    $resource = $context['resource'] ?: NULL;
    $sub_wrapper = $this->subWrapper();

    if ($context['sub_property'] && $this->subWrapper()->value()) {
      $sub_wrapper = $sub_wrapper->{$context['sub_property']};
    }

    if ($resource) {
      $value = $this->getValueFromResource($sub_wrapper, $property, $resource, $public_field_name, $wrapper->getIdentifier());
    }
    else {
      // Wrapper method.
      $value = $sub_wrapper->{$method}();
    }

    return $value;
  }

  /**
   * Get value from a property.
   *
   * @param EntityMetadataWrapper $wrapper
   *   The wrapped entity.
   * @param EntityMetadataWrapper $sub_wrapper
   *   The wrapped property.
   * @param array $info
   *   The public field info array.
   * @param $public_field_name
   *   The field name.
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function getValueFromProperty(\EntityMetadataWrapper $wrapper, \EntityMetadataWrapper $sub_wrapper, array $info, $public_field_name) {
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiple() {
    return $this->subWrapper() instanceof EntityListWrapper;
  }

  /**
   * Get the sub_wrapper
   *
   * @return \EntityDrupalWrapper
   *   The sub wrapper.
   */
  protected function subWrapper() {
    $context = $this->getContext();
    $wrapper = $this->getSource();
    return $context['wrapper_method_on_entity'] ? $wrapper : $wrapper->{$context['property']};
  }

  /**
   * {@inheritdoc}
   *
   * @return \RestfulPropertySourceEMW
   */
  public function current() {
    $sub_wrapper = $this->subWrapper();
    $output = new static($sub_wrapper[$this->position]);
    $output->setContext($this->getContext());
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return $this->position;
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->position++;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    $this->position = 0;
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    $wrapper = $this->getSource();
    return isset($wrapper[$this->position]);
  }

}
