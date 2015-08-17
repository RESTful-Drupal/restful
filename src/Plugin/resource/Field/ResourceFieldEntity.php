<?php

/**
 * @file
* Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntity
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProviderResource;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;
use Drupal\restful\Plugin\ResourcePluginManager;
use Drupal\restful\Util\String;

class ResourceFieldEntity implements ResourceFieldEntityInterface {

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
   * @var array
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
   * The entity type.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The bundles.
   *
   * @var array
   */
  protected $bundles;

  /**
   * Constructor.
   *
   * @param array $field
   *   Contains the field values.
   */
  public function __construct(array $field) {
    $this->wrapperMethod = isset($field['wrapper_method']) ? $field['wrapper_method'] : $this->wrapperMethod;
    $this->subProperty = isset($field['sub_property']) ? $field['sub_property'] : $this->subProperty;
    $this->formatter = isset($field['formatter']) ? $field['formatter'] : $this->formatter;
    $this->wrapperMethodOnEntity = isset($field['wrapper_method_on_entity']) ? $field['wrapper_method_on_entity'] : $this->wrapperMethodOnEntity;
    $this->column = isset($field['column']) ? $field['column'] : $this->column;
    $this->imageStyles = isset($field['image_styles']) ? $field['image_styles'] : $this->imageStyles;
    if (!empty($field['bundles'])) {
      $this->bundles = $field['bundles'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $field, ResourceFieldInterface $decorated = NULL) {
    $resource_field = NULL;
    $class_name = static::fieldClassName($field);
    // If the class exists and is a ResourceFieldEntityInterface use that one.
    if (
      $class_name &&
      class_exists($class_name) &&
      in_array(
        'Drupal\restful\Plugin\resource\Field\ResourceFieldEntityInterface',
        class_implements($class_name)
      )
    ) {
      $resource_field = new $class_name($field);
    }

    // If no specific class was found then use the current one.
    if (!$resource_field) {
      // Create the current object.
      $resource_field = new static($field);
    }

    // Set the basic object to the decorated property.
    $resource_field->decorate($decorated ? $decorated : new ResourceField($field));
    $resource_field->decorated->addDefaults();

    // Add the default specifics for the current object.
    $resource_field->addDefaults();

    return $resource_field;
  }

  /**
   * {@inheritdoc}
   */
  public function value(DataInterpreterInterface $interpreter) {
    $value = $this->decorated->value($interpreter);
    if (isset($value)) {
      // Let the decorated resolve callbacks.
      return $value;
    }

    // Check user has access to the property.
    if (!$this->access('view', $interpreter)) {
      return NULL;
    }

    $property_wrapper = $this->propertyWrapper($interpreter);
    $wrapper = $interpreter->getWrapper();

    if ($property_wrapper instanceof \EntityListWrapper) {
      $values = array();
      // Multiple values.
      foreach ($property_wrapper->getIterator() as $item_wrapper) {
        $values[] = $this->singleValue($item_wrapper, $wrapper);
      }
      return $values;
    }
    return $this->singleValue($property_wrapper, $wrapper);
  }

  /**
   * {@inheritdoc}
   */
  public function compoundDocumentId(DataInterpreterInterface $interpreter) {
    $property_wrapper = $this->propertyWrapper($interpreter);

    if ($property_wrapper instanceof \EntityListWrapper) {
      $values = array();
      // Multiple values.
      foreach ($property_wrapper->getIterator() as $item_wrapper) {
        $values[] = $this->propertyIdentifier($item_wrapper);
      }
      return $values;
    }
    return $this->propertyIdentifier($property_wrapper);
  }

  /**
   * Helper function to get the identifier from a property wrapper.
   *
   * @param \EntityMetadataWrapper $property_wrapper
   *   The property wrapper to get the ID from.
   *
   * @return string
   *   An identifier.
   */
  protected function propertyIdentifier(\EntityMetadataWrapper $property_wrapper) {
    if ($property_wrapper instanceof \EntityDrupalWrapper) {
      // The property wrapper is a reference to another entity get the entity
      // ID.
      $identifier = $property_wrapper->getIdentifier() ?: NULL;
      $resource = $this->getResource();
      if (!$resource || !$identifier) {
        return $identifier;
      }
      // If there is a resource that we are pointing to, we need to use the id
      // field that that particular resource has in its configuration. Trying to
      // load by the entity id in that scenario will lead to a 404.
      // We'll load the plugin to get the idField configuration.
      $instance_id = sprintf('%s:%d.%d', $resource['name'], $resource['majorVersion'], $resource['minorVersion']);
      $plugin_manager = ResourcePluginManager::create('cache', Request::create('', array(), RequestInterface::METHOD_GET));
      /* @var ResourceInterface $resource */
      $resource = $plugin_manager->createInstance($instance_id);
      $plugin_definition = $resource->getPluginDefinition();
      if (empty($plugin_definition['dataProvider']['idField'])) {
        return $identifier;
      }
      try {
        return $property_wrapper->{$plugin_definition['dataProvider']['idField']}->value();
      }
      catch (\EntityMetadataWrapperException $e) {
        return $identifier;
      }
    }
    // The property is a regular one, get the value out of it and use it as
    // the embedded identifier.
    return $this->fieldValue($property_wrapper);
  }

  /**
   * {@inheritdoc}
   */
  public function set($value, DataInterpreterInterface $interpreter) {
    try {
      $property_wrapper = $interpreter->getWrapper()->{$this->getProperty()};
      $property_wrapper->set($value);
    }
    catch (\Exception $e) {
      $this->decorated->set($value, $interpreter);
    }
  }

  /**
   * Returns the value for the current single field.
   *
   * This implementation will also add some metadata to the resource field
   * object about the entity it is referencing.
   *
   * @param \EntityMetadataWrapper $property_wrapper
   *   The property wrapper. Either \EntityDrupalWrapper or \EntityListWrapper.
   * @param \EntityDrupalWrapper $wrapper
   *   The entity wrapper.
   *
   * @return mixed
   *   A single value for the field.
   */
  protected function singleValue(\EntityMetadataWrapper $property_wrapper, \EntityDrupalWrapper $wrapper) {
    if ($resource = $this->getResource()) {
      // TODO: The resource input data in the field definition has changed.
      // Now it does not need to be keyed by bundle since you don't even need
      // an entity to use the resource based field.

      $embedded_identifier = $this->propertyIdentifier($property_wrapper);
      // Allow embedding entities with ID 0, like the anon user.
      if (empty($embedded_identifier) && $embedded_identifier !== 0) {
        return NULL;
      }
      if (isset($resource['full_view']) && $resource['full_view'] === FALSE) {
        return $embedded_identifier;
      }
      $request = Request::create('', array(), RequestInterface::METHOD_GET);
      // Remove the $_GET options for the sub-request.
      $request->setParsedInput(array());
      // TODO: Get version automatically to avoid setting it in the plugin definition. Ideally we would fill this when processing the plugin definition defaults.
      $resource_data_provider = DataProviderResource::init($request, $resource['name'], array(
        $resource['majorVersion'],
        $resource['minorVersion'],
      ));

      $metadata = $this->getMetadata($wrapper->getIdentifier());
      $metadata ?: array();
      $metadata[] = $this->buildResourceMetadataItem($property_wrapper);
      $this->addMetadata($wrapper->getIdentifier(), $metadata);
      return $resource_data_provider->view($embedded_identifier);
    }

    if ($this->getFormatter()) {
      // Get value from field formatter.
      $value = $this->formatterValue($property_wrapper, $wrapper);
    }
    else {
      // Single value.
      $value = $this->fieldValue($property_wrapper);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \EntityMetadataWrapperException
   */
  public function access($op, DataInterpreterInterface $interpreter) {
    // Perform basic access checks.
    if (!$this->decorated->access($op, $interpreter)) {
      return FALSE;
    }

    if (!$this->getProperty()) {
      // If there is no property we cannot check for property access.
      return TRUE;
    }

    // Perform field API access checks.
    $property_wrapper = $this->propertyWrapper($interpreter);
    if ($this->isWrapperMethodOnEntity() && $this->getWrapperMethod() && $this->getProperty()) {
      // Sometimes we define fields as $wrapper->getIdentifier. We need to
      // resolve that to $wrapper->nid to call $wrapper->nid->info().
      $property_wrapper = $property_wrapper->{$this->getProperty()};
    }
    $account = $interpreter->getAccount();

    // Check format access for text fields.
    if (
      $op == 'edit' &&
      $property_wrapper->type() == 'text_formatted' &&
      $property_wrapper->value() &&
      $property_wrapper->format->value()
    ) {
      $format = (object) array('format' => $property_wrapper->format->value());
      // Only check filter access on write contexts.
      if (!filter_access($format, $account)) {
        return FALSE;
      }
    }

    $info = $property_wrapper->info();
    if ($op == 'edit' && empty($info['setter callback'])) {
      // Property does not allow setting.
      return FALSE;
    }

    // If $interpreter->getWrapper()->value() === FALSE it means that the entity
    // could not be loaded, thus checking properties on it will result in
    // errors.
    // Ex: this happens when the embedded author is the anonymous user. Doing
    // user_load(0) returns FALSE.
    $access = $interpreter->getWrapper()->value() !== FALSE && $property_wrapper->access($op, $account);
    return $access !== FALSE;
  }

  /**
   * Get the wrapper for the property associated to the current field.
   *
   * @param DataInterpreterInterface $interpreter
   *   The data source.
   *
   * @return \EntityMetadataWrapper
   *   Either a \EntityStructureWrapper or a \EntityListWrapper.
   *
   * @throws ServerConfigurationException
   */
  protected function propertyWrapper(DataInterpreterInterface $interpreter) {
    // Exposing an entity field.
    $wrapper = $interpreter->getWrapper();
    // For entity fields the DataInterpreter needs to contain an EMW.
    if (!$wrapper instanceof \EntityDrupalWrapper) {
      throw new ServerConfigurationException('Cannot get a value without an entity metadata wrapper data source.');
    }
    $property = $this->getProperty();
    return ($property && !$this->isWrapperMethodOnEntity()) ? $wrapper->{$property} : $wrapper;
  }

  /**
   * Get value from a property.
   *
   * @param \EntityMetadataWrapper $property_wrapper
   *   The property wrapper. Either \EntityDrupalWrapper or \EntityListWrapper.
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function fieldValue(\EntityMetadataWrapper $property_wrapper) {
    if ($this->getSubProperty() && $property_wrapper->value()) {
      $property_wrapper = $property_wrapper->{$this->getSubProperty()};
    }

    // Wrapper method.
    return $property_wrapper->{$this->getWrapperMethod()}();
  }

  /**
   * Get value from a field rendered by Drupal field API's formatter.
   *
   * @param \EntityMetadataWrapper $property_wrapper
   *   The property wrapper. Either \EntityDrupalWrapper or \EntityListWrapper.
   * @param \EntityDrupalWrapper $wrapper
   *   The entity wrapper.
   *
   * @return mixed
   *   A single or multiple values.
   *
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  protected function formatterValue(\EntityMetadataWrapper $property_wrapper, \EntityDrupalWrapper $wrapper) {
    $value = NULL;

    if (!ResourceFieldEntity::propertyIsField($this->getProperty())) {
      // Property is not a field.
      throw new ServerConfigurationException(format_string('@property is not a configurable field, so it cannot be processed using field API formatter', array('@property' => $this->getProperty())));
    }

    // Get values from the formatter.
    $output = field_view_field($this->entityType, $wrapper->value(), $this->getProperty(), $this->getFormatter());

    // Unset the theme, as we just want to get the value from the formatter,
    // without the wrapping HTML.
    unset($output['#theme']);


    if ($property_wrapper instanceof \EntityListWrapper) {
      // Multiple values.
      foreach (element_children($output) as $delta) {
        $value[] = drupal_render($output[$delta]);
      }
    }
    else {
      // Single value.
      $value = drupal_render($output);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function addMetadata($key, $value) {
    $this->decorated->addMetadata($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata($key) {
    return $this->decorated->getMetadata($key);
  }

  /**
   * {@inheritdoc}
   */
  public function executeProcessCallbacks($value) {
    return $this->decorated->executeProcessCallbacks($value);
  }

  /**
   * {@inheritdoc}
   */
  public function render(DataInterpreterInterface $interpreter) {
    return $this->executeProcessCallbacks($this->value($interpreter));
  }

  /**
   * Get value for a field based on another resource.
   *
   * @param DataInterpreterInterface $source
   *   The data source.
   *
   * @return mixed
   *   A single or multiple values.
   */
  protected function resourceValue(DataInterpreterInterface $source) {}

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
  public function setSubProperty($sub_property) {
    $this->subProperty = $sub_property;
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
  public function setWrapperMethod($wrapper_method) {
    $this->wrapperMethod = $wrapper_method;
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
  public function setWrapperMethodOnEntity($wrapper_method_on_entity) {
    $this->wrapperMethodOnEntity = $wrapper_method_on_entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumn() {
    if (isset($this->column)) {
      return $this->column;
    }
    if ($this->getProperty() && $field = field_info_field($this->getProperty())) {
      if ($field['type'] == 'text_long') {
        // Do not default to format.
        $this->setColumn('value');
      }
      else {
        // Set the column name.
        $this->setColumn(key($field['columns']));
      }
    }
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
  public function setImageStyles($image_styles) {
    $this->imageStyles = $image_styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityType($entity_type) {
    $this->entityType = $entity_type;

    // If there is no property try to get it based on the wrapper method and
    // store the value in the decorated object.
    $property = NULL;
    // If entity metadata wrapper methods were used, then return the appropriate
    // entity property.
    if (!$this->isWrapperMethodOnEntity() || !($wrapper_method = $this->getWrapperMethod())) {
      return NULL;
    }
    $entity_info = entity_get_info($entity_type);
    if ($wrapper_method == 'label') {
      // Store the label key.
      $property = empty($entity_info['entity keys']['label']) ? NULL : $entity_info['entity keys']['label'];
    }
    elseif ($wrapper_method == 'getBundle') {
      // Store the label key.
      $this->decorated->setProperty($property);
    }
    elseif ($wrapper_method == 'getIdentifier') {
      // Store the ID key.
      $property = empty($entity_info['entity keys']['id']) ? NULL : $entity_info['entity keys']['id'];
    }

    // There are occasions when the wrapper property is not the schema
    // database field.
    $wrapper = entity_metadata_wrapper($entity_type);
    foreach ($wrapper->getPropertyInfo() as $wrapper_property => $property_info) {
      if (!empty($property_info['schema field']) && $property_info['schema field'] == $property) {
        $property = $wrapper_property;
        break;
      }
    }

    $this->decorated->setProperty($property);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundles() {
    return $this->bundles;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundles($bundles) {
    $this->bundles = $bundles;
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
    if ($this->getProperty() && $field = field_info_field($this->getProperty())) {
      // If it's an image check if we need to add image style processing.
      $image_styles = $this->getImageStyles();
      if ($field['type'] == 'image' && !empty($image_styles)) {
        $process_callbacks = $this->getProcessCallbacks();
        array_unshift($process_callbacks, array(
          array($this, 'getImageUris'),
          array($image_styles),
        ));
        $this->setProcessCallbacks($process_callbacks);
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

  /**
   * {@inheritdoc}
   */
  public function preprocess($value) {
    // By default assume that there is no preprocess and allow extending classes
    // to implement this.
    return $value;
  }

  /**
   * Get the class name to use based on the field definition.
   *
   * @param array $field_definition
   *   The processed field definition with the user values.
   *
   * @return string
   *   The class name to use. If the class name is empty or does not implement
   *   ResourceFieldInterface then ResourceField will be used. NULL if nothing
   *   was found.
   */
  protected static function fieldClassName(array $field_definition) {
    if (!empty($field_definition['class']) && $field_definition['class'] != '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntity') {
      // If there is a class that is not the current, return it.
      return $field_definition['class'];
    }
    // If there is an extending class for the particular field use that class
    // instead.
    if (empty($field_definition['property']) || !$field_info = field_info_field($field_definition['property'])) {
      return NULL;
    }

    $resource_field = NULL;
    switch ($field_info['type']) {
      case 'entityreference':
      case 'taxonomy_term_reference':
        return '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntityReference';

      case 'text':
      case 'text_long':
      case 'text_with_summary':
        return '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntityText';

      case 'file':
      case 'image':
        return '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntityFile';

      default:
        $class_name = 'ResourceFieldEntity' . String::camelize($field_info['type']);
        if (class_exists($class_name)) {
          return $class_name;
        }
        return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicName() {
    return $this->decorated->getPublicName();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicName($public_name) {
    $this->decorated->setPublicName($public_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessCallbacks() {
    return $this->decorated->getAccessCallbacks();
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessCallbacks($access_callbacks) {
    $this->decorated->setAccessCallbacks($access_callbacks);
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty() {
    return $this->decorated->getProperty();
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($property) {
    $this->decorated->setProperty($property);
  }

  /**
   * {@inheritdoc}
   */
  public function getCallback() {
    return $this->decorated->getCallback();
  }

  /**
   * {@inheritdoc}
   */
  public function setCallback($callback) {
    $this->decorated->setCallback($callback);
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessCallbacks() {
    return $this->decorated->getProcessCallbacks();
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessCallbacks($process_callbacks) {
    $this->decorated->setProcessCallbacks($process_callbacks);
  }

  /**
   * {@inheritdoc}
   */
  public function getResource() {
    return $this->decorated->getResource();
  }

  /**
   * {@inheritdoc}
   */
  public function setResource($resource) {
    $this->decorated->setResource($resource);
  }

  /**
   * {@inheritdoc}
   */
  public function getMethods() {
    return $this->decorated->getMethods();
  }

  /**
   * {@inheritdoc}
   */
  public function setMethods($methods) {
    $this->decorated->setMethods($methods);
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->decorated->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isComputed() {
    return $this->decorated->isComputed();
  }

  /**
   * {@inheritdoc}
   */
  public function cardinality() {
    // Default to single cardinality.
    $default = 1;
    if (!$field_info = field_info_field($this->getProperty())) {
      return $default;
    }
    return empty($field_info['cardinality']) ? $default : $field_info['cardinality'];
  }

  /**
   * Helper method to determine if an array is numeric.
   *
   * @param array $input
   *   The input array.
   *
   * @return bool
   *   TRUE if the array is numeric, false otherwise.
   */
  public static function isArrayNumeric(array $input) {
    return ResourceFieldBase::isArrayNumeric($input);
  }

  /**
   * Builds a metadata item for a field value.
   *
   * It will add information about the referenced entity.
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapper to the referenced entity.
   *
   * @return array
   *   The metadata array item.
   */
  protected function buildResourceMetadataItem(\EntityDrupalWrapper $wrapper) {
    $id = $wrapper->getIdentifier();
    $bundle = $wrapper->getBundle();
    $resource = $this->getResource();
    return array(
      'id' => $id,
      'entity_type' => $wrapper->type(),
      'bundle' => $bundle,
      'resource_name' => $resource['name'],
    );
  }

}
