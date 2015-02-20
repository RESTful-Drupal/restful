<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntity
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;

class ResourceFieldEntity extends ResourceFieldBase implements ResourceFieldEntityInterface {

  /**
   * Decorated resource field.
   *
   * @var ResourceFieldInterface
   */
  protected $decorated;

  /**
   * A sub property name of a property to take from it the content. This can be
   * used for example on a text field with filtered text input format where we
   * would need to do $wrapper->body->value->value().
   *
   * @var string
   */
  protected $subProperty;

  /**
   * Used for rendering the value of a configurable field using Drupal field
   * API's formatter. The value is the $display value that is passed to
   * field_view_field().
   *
   * @var string
   */
  protected $formatter;

  /**
   * The wrapper's method name to perform on the field. This can be used for
   * example to get the entity label, by setting the value to "label". Defaults
   * to "value".
   *
   * @var string
   */
  protected $wrapperMethod = 'value';

  /**
   * A Boolean to indicate on what to perform the wrapper method. If TRUE the
   * method will perform on the entity (e.g. $wrapper->label()) and FALSE on the
   * property or sub property (e.g. $wrapper->field_reference->label()).
   *
   * @var bool
   */
  protected $wrapperMethodOnEntity = FALSE;

  /**
   * If the property is a field, set the column that would be used in queries.
   * For example, the default column for a text field would be "value". Defaults
   * to the first column returned by field_info_field(), otherwise FALSE.
   *
   * @var string
   */
  protected $column;

  /**
   * Array of image styles to apply to this resource field maps to an image
   * field.
   *
   * @var array
   */
  protected $imageStyles = array();

  /**
   * {@inheritdoc}
   */
  public static function create(array $fields, ResourceFieldInterface $decorated = NULL) {
    // Create the current object.
    $resource_field = new static($fields);

    // Set the basic object to the decorated property.
    $resource_field->decorate($decorated ? $decorated : ResourceField::create($fields));

    // Add the default specifics for the current object.
    $resource_field->addDefaults();

    return $resource_field;
  }

  /**
   * {@inheritdoc}
   */
  public function decorate(ResourceFieldInterface $decorated) {
    $this->decorated = $decorated;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubProperty() {
    return $this->subProperty;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubProperty($subProperty) {
    $this->subProperty = $subProperty;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatter() {
    return $this->formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormatter($formatter) {
    $this->formatter = $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapperMethod() {
    return $this->wrapperMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function setWrapperMethod($wrapperMethod) {
    $this->wrapperMethod = $wrapperMethod;
  }

  /**
   * {@inheritdoc}
   */
  public function isWrapperMethodOnEntity() {
    return $this->wrapperMethodOnEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function setWrapperMethodOnEntity($wrapperMethodOnEntity) {
    $this->wrapperMethodOnEntity = $wrapperMethodOnEntity;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumn() {
    return $this->column;
  }

  /**
   * {@inheritdoc}
   */
  public function setColumn($column) {
    $this->column = $column;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageStyles() {
    return $this->imageStyles;
  }

  /**
   * {@inheritdoc}
   */
  public function setImageStyles($imageStyles) {
    $this->imageStyles = $imageStyles;
  }

  /**
   * {@inheritdoc}
   */
  public function addDefaults() {
    // Almost all the defaults come are applied by the object's property
    // defaults.

    // Set the defaults from the decorated.
    $this->setResource($this->decorated->getResource());

    // Set the Entity related defaults.
    if ($this->property && $field = field_info_field($this->property)) {
      // If it's an image check if we need to add image style processing.
      $image_styles = $this->getImageStyles();
      if ($field['type'] == 'image' && !empty($image_styles)) {
        array_unshift($this->processCallbacks, array(array($this, 'getImageUris'), array($image_styles)));
      }
      if (!$this->getColumn()) {
        // Set the column name.
        $this->setColumn(key($field['columns']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getImageUris(array $file_array, $image_styles) {
    // Return early if there are no image styles.
    if (empty($image_styles)) {
      return $file_array;
    }
    // If $file_array is an array of file arrays. Then call recursively for each
    // item and return the result.
    if (static::isArrayNumeric($file_array)) {
      $output = array();
      foreach ($file_array as $item) {
        $output[] = $this->getImageUris($item, $image_styles);
      }
      return $output;
    }
    $file_array['image_styles'] = array();
    foreach ($image_styles as $style) {
      $file_array['image_styles'][$style] = image_style_url($style, $file_array['uri']);
    }
    return $file_array;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyIsField($name) {
    $field_info = field_info_field($name);
    return !empty($field_info);
  }

}
