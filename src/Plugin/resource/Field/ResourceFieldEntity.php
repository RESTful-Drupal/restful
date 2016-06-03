<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldEntity
 */

namespace Drupal\restful\Plugin\resource\Field;

use Drupal\restful\Exception\ServerConfigurationException;
use Drupal\restful\Exception\InaccessibleRecordException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Http\Request;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataProvider\DataProvider;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoEntity;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoEntityInterface;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoInterface;
use Drupal\restful\Plugin\resource\ResourceInterface;

/**
 * Class ResourceFieldEntity.
 *
 * @package Drupal\restful\Plugin\resource\Field
 */
class ResourceFieldEntity implements ResourceFieldEntityInterface {

  /**
   * Decorated resource field.
   *
   * @var ResourceFieldInterface
   */
  protected $decorated;

  /**
   * A copy of the underlying property.
   *
   * This is duplicated here for performance reasons.
   *
   * @var string
   */
  protected $property;

  /**
   * A sub property name of a property to take from it the content.
   *
   * This can be used for example on a text field with filtered text input
   * format where we would need to do $wrapper->body->value->value().
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
   * The bundle name.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Constructor.
   *
   * @param array $field
   *   Contains the field values.
   * @param RequestInterface $request
   *   The request.
   *
   * @throws ServerConfigurationException
   *   If the entity type is empty.
   */
  public function __construct(array $field, RequestInterface $request) {
    if ($this->decorated) {
      $this->setRequest($request);
    }
    if (empty($field['entityType'])) {
      throw new ServerConfigurationException(sprintf('Unknown entity type for %s resource field.', __CLASS__));
    }
    $this->setEntityType($field['entityType']);
    $this->wrapperMethod = isset($field['wrapper_method']) ? $field['wrapper_method'] : $this->wrapperMethod;
    $this->subProperty = isset($field['sub_property']) ? $field['sub_property'] : $this->subProperty;
    $this->formatter = isset($field['formatter']) ? $field['formatter'] : $this->formatter;
    $this->wrapperMethodOnEntity = isset($field['wrapper_method_on_entity']) ? $field['wrapper_method_on_entity'] : $this->wrapperMethodOnEntity;
    $this->column = isset($field['column']) ? $field['column'] : $this->column;
    $this->imageStyles = isset($field['image_styles']) ? $field['image_styles'] : $this->imageStyles;
    if (!empty($field['bundle'])) {
      // TODO: Document this usage.
      $this->setBundle($field['bundle']);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $field, RequestInterface $request = NULL, ResourceFieldInterface $decorated = NULL) {
    $request = $request ?: restful()->getRequest();
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
      $resource_field = new $class_name($field, $request);
    }

    // If no specific class was found then use the current one.
    if (!$resource_field) {
      // Create the current object.
      $resource_field = new static($field, $request);
    }
    if (!$resource_field) {
      throw new ServerConfigurationException('Unable to create resource field');
    }
    // Set the basic object to the decorated property.
    $resource_field->decorate($decorated ? $decorated : new ResourceField($field, $request));
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
        $values[] = $this->singleValue($item_wrapper, $wrapper, $interpreter->getAccount());
      }
      return $values;
    }
    return $this->singleValue($property_wrapper, $wrapper, $interpreter->getAccount());
  }

  /**
   * {@inheritdoc}
   */
  public function compoundDocumentId(DataInterpreterInterface $interpreter) {
    $collections = $this->render($interpreter);
    // Extract the document ID from the field resource collection.
    $process = function ($collection) {
      if (!$collection instanceof ResourceFieldCollectionInterface) {
        return $collection;
      }
      $id_field = $collection->getIdField();
      return $id_field->render($collection->getInterpreter());
    };
    // If cardinality is 1, then we don't have an array.
    return $this->getCardinality() == 1 ?
      $process($collections) :
      array_map($process, array_filter($collections));
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
      $identifier = $this->referencedId($property_wrapper);
      $resource = $this->getResource();
      // TODO: Make sure we still want to support fullView.
      if (!$resource || !$identifier || (isset($resource['fullView']) && $resource['fullView'] === FALSE)) {
        return $identifier;
      }
      // If there is a resource that we are pointing to, we need to use the id
      // field that that particular resource has in its configuration. Trying to
      // load by the entity id in that scenario will lead to a 404.
      // We'll load the plugin to get the idField configuration.
      $instance_id = sprintf('%s:%d.%d', $resource['name'], $resource['majorVersion'], $resource['minorVersion']);
      /* @var ResourceInterface $resource */
      $resource = restful()
        ->getResourceManager()
        ->getPluginCopy($instance_id, Request::create('', array(), RequestInterface::METHOD_GET));
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
   * @param object $account
   *   The user account.
   *
   * @return mixed
   *   A single value for the field.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \Drupal\restful\Exception\ServerConfigurationException
   */
  protected function singleValue(\EntityMetadataWrapper $property_wrapper, \EntityDrupalWrapper $wrapper, $account) {
    if ($resource = $this->getResource()) {
      // TODO: The resource input data in the field definition has changed.
      // Now it does not need to be keyed by bundle since you don't even need
      // an entity to use the resource based field.
      $embedded_identifier = $this->propertyIdentifier($property_wrapper);
      // Allow embedding entities with ID 0, like the anon user.
      if (empty($embedded_identifier) && $embedded_identifier !== 0) {
        return NULL;
      }
      if (isset($resource['fullView']) && $resource['fullView'] === FALSE) {
        return $embedded_identifier;
      }
      // We support dot notation for the sparse fieldsets. That means that
      // clients can specify the fields to show based on the "fields" query
      // string parameter.
      $parsed_input = array(
        'fields' => implode(',', $this->nestedDottedChildren('fields')),
        'include' => implode(',', $this->nestedDottedChildren('include')),
        'filter' => $this->nestedDottedChildren('filter'),
      );
      $request = Request::create('', array_filter($parsed_input), RequestInterface::METHOD_GET);

      // Get a plugin (that can be altered with decorators.
      $embedded_resource = restful()->getResourceManager()->getPluginCopy(sprintf('%s:%d.%d', $resource['name'], $resource['majorVersion'], $resource['minorVersion']));
      // Configure the plugin copy with the sub-request and sub-path.
      $embedded_resource->setPath($embedded_identifier);
      $embedded_resource->setRequest($request);
      $embedded_resource->setAccount($account);
      $metadata = $this->getMetadata($wrapper->getIdentifier());
      $metadata = $metadata ?: array();
      $metadata[] = $this->buildResourceMetadataItem($property_wrapper);
      $this->addMetadata($wrapper->getIdentifier(), $metadata);
      try {
        // Get the contents to embed in place of the reference ID.
        /* @var ResourceFieldCollection $embedded_entity */
        $embedded_entity = $embedded_resource
          ->getDataProvider()
          ->view($embedded_identifier);
      }
      catch (InaccessibleRecordException $e) {
        // If you don't have access to the embedded entity is like not having
        // access to the property.
        return NULL;
      }
      catch (UnprocessableEntityException $e) {
        // If you access a nonexistent embedded entity.
        return NULL;
      }
      // Test if the $embedded_entity meets the filter or not.
      if (empty($parsed_input['filter'])) {
        return $embedded_entity;
      }
      foreach ($parsed_input['filter'] as $filter) {
        // Filters only apply if the target is the current field.
        if (!empty($filter['target']) && $filter['target'] == $this->getPublicName() && !$embedded_entity->evalFilter($filter)) {
          // This filter is not met.
          return NULL;
        }
      }
      return $embedded_entity;
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
    if (!$property_wrapper = $this->propertyWrapper($interpreter)) {
      return FALSE;
    }
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
    $access = $interpreter->getWrapper()
        ->value() !== FALSE && $property_wrapper->access($op, $account);
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
    // This is the first method that gets called for all fields after loading
    // the entity. We'll use that opportunity to set the actual bundle of the
    // field.
    $this->setBundle($interpreter->getWrapper()->getBundle());

    // Exposing an entity field.
    $wrapper = $interpreter->getWrapper();
    // For entity fields the DataInterpreter needs to contain an EMW.
    if (!$wrapper instanceof \EntityDrupalWrapper) {
      throw new ServerConfigurationException('Cannot get a value without an entity metadata wrapper data source.');
    }
    $property = $this->getProperty();
    try {
      return ($property && !$this->isWrapperMethodOnEntity()) ? $wrapper->{$property} : $wrapper;
    }
    catch (\EntityMetadataWrapperException $e) {
      throw new UnprocessableEntityException(sprintf('The property %s could not be found in %s:%s.', $property, $wrapper->type(), $wrapper->getBundle()));
    }
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
    $output = field_view_field($this->getEntityType(), $wrapper->value(), $this->getProperty(), $this->getFormatter());

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
   * Get the children of a query string parameter that apply to the field.
   *
   * For instance: if the field is 'relatedArticles' and the query string is
   * '?relatedArticles.one.two,articles' it returns array('one.two').
   *
   * @param string $key
   *   The name of the key: include|fields
   *
   * @return string[]
   *   The list of fields.
   */
  protected function nestedDottedChildren($key) {
    // Filters are dealt with differently.
    if ($key == 'filter') {
      return $this->nestedDottedFilters();
    }
    $allowed_values = array('include', 'fields');
    if (!in_array($key, $allowed_values)) {
      return array();
    }
    $input = $this
      ->getRequest()
      ->getParsedInput();
    $limit_values = !empty($input[$key]) ? explode(',', $input[$key]) : array();
    $limit_values = array_filter($limit_values, function ($value) {
      $parts = explode('.', $value);
      return $parts[0] == $this->getPublicName() && $value != $this->getPublicName();
    });
    return array_map(function ($value) {
      return substr($value, strlen($this->getPublicName()) + 1);
    }, $limit_values);
  }

  /**
   * Process the filter query string for the relevant sub-query.
   *
   * Selects the filters that start with the field name.
   *
   * @return array
   *   The processed filters.
   */
  protected function nestedDottedFilters() {
    $input = $this
      ->getRequest()
      ->getParsedInput();
    if (empty($input['filter'])) {
      return array();
    }
    $output_filters = array();
    $filters = $input['filter'];
    foreach ($filters as $filter_public_name => $filter) {
      $filter = DataProvider::processFilterInput($filter, $filter_public_name);
      if (strpos($filter_public_name, $this->getPublicName() . '.') === 0) {
        // Remove the prefix and add it to the filters for the next request.
        $new_name = substr($filter_public_name, strlen($this->getPublicName()) + 1);
        $filter['public_field'] = $new_name;
        $output_filters[$new_name] = $filter;
      }
    }
    return $output_filters;
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
  public function getRequest() {
    return $this->decorated->getRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->decorated->setRequest($request);
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
   * {@inheritdoc}
   */
  public function getDefinition() {
    return $this->decorated->getDefinition();
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicFieldInfo() {
    return $this->decorated->getPublicFieldInfo();
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicFieldInfo(PublicFieldInfoInterface $public_field_info) {
    $this->decorated->setPublicFieldInfo($public_field_info);
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
    if ($this->getProperty() && $field = $this::fieldInfoField($this->getProperty())) {
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
  }

  /**
   * Gets the \EntityStructureWrapper for the entity type.
   *
   * @return mixed
   *   The \EntityStructureWrapper if the entity type exists.
   */
  protected function entityTypeWrapper() {
    static $entity_wrappers = array();
    $key = sprintf('%s:%s', $this->getEntityType(), $this->getBundle());
    if (isset($entity_wrappers[$key])) {
      return $entity_wrappers[$key];
    }
    $entity_wrappers[$key] = entity_metadata_wrapper($this->getEntityType(), NULL, array(
      'bundle' => $this->getBundle(),
    ));
    return $entity_wrappers[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle() {
    return $this->bundle;
  }

  /**
   * {@inheritdoc}
   */
  public function setBundle($bundle) {
    // Do not do pointless work if not needed.
    if (!empty($this->bundle) && $this->bundle == $bundle) {
      return;
    }
    $this->bundle = $bundle;

    // If this is an options call, then introspect Entity API to add more data
    // to the public field information.
    if ($this->getRequest()->getMethod() == RequestInterface::METHOD_OPTIONS) {
      $this->populatePublicInfoField();
    }
  }

  /**
   * {@inheritdoc}
   *
   * Almost all the defaults come are applied by the object's property defaults.
   */
  public function addDefaults() {
    // Set the defaults from the decorated.
    $this->setResource($this->decorated->getResource());

    // If entity metadata wrapper methods were used, then return the appropriate
    // entity property.
    if ($this->isWrapperMethodOnEntity() && $this->getWrapperMethod()) {
      $this->propertyOnEntity();
    }

    // Set the Entity related defaults.
    if (
      ($this->property = $this->decorated->getProperty()) &&
      ($field = $this::fieldInfoField($this->property)) &&
      $field['type'] == 'image' &&
      ($image_styles = $this->getImageStyles())
    ) {
      // If it's an image check if we need to add image style processing.
      $process_callbacks = $this->getProcessCallbacks();
      array_unshift($process_callbacks, array(
        array($this, 'getImageUris'),
        array($image_styles),
      ));
      $this->setProcessCallbacks($process_callbacks);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getImageUris(array $file_array, $image_styles) {
    // Return early if there are no image styles.
    if (empty($image_styles)) {
      return $file_array;
    }
    // If $file_array is an array of file arrays. Then call recursively for each
    // item and return the result.
    if (static::isArrayNumeric($file_array)) {
      $output = array();
      foreach ($file_array as $item) {
        $output[] = static::getImageUris($item, $image_styles);
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
    return (bool) static::fieldInfoField($name);
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
  public static function fieldClassName(array $field_definition) {
    if (!empty($field_definition['class']) && $field_definition['class'] != '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntity') {
      // If there is a class that is not the current, return it.
      return $field_definition['class'];
    }
    // If there is an extending class for the particular field use that class
    // instead.
    if (empty($field_definition['property']) || !$field_info = static::fieldInfoField($field_definition['property'])) {
      return NULL;
    }

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
        // If the field is treated as a resource, then default to the reference.
        if (!empty($field_definition['resource'])) {
          return '\Drupal\restful\Plugin\resource\Field\ResourceFieldFileEntityReference';
        }
        return '\Drupal\restful\Plugin\resource\Field\ResourceFieldEntityFile';

      default:
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
    return $this->property;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($property) {
    $this->property = $property;
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
  public function autoDiscovery() {
    if (method_exists($this->decorated, 'autoDiscovery')) {
      return $this->decorated->autoDiscovery();
    }
    return ResourceFieldBase::emptyDiscoveryInfo($this->getPublicName());
  }

  /**
   * {@inheritdoc}
   */
  public function getCardinality() {
    if (isset($this->cardinality)) {
      return $this->cardinality;
    }
    // Default to single cardinality.
    $this->cardinality = 1;
    if ($field_info = $this::fieldInfoField($this->getProperty())) {
      $this->cardinality = empty($field_info['cardinality']) ? $this->cardinality : $field_info['cardinality'];
    }
    return $this->cardinality;
  }

  /**
   * {@inheritdoc}
   */
  public function setCardinality($cardinality) {
    $this->cardinality = $cardinality;
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
   * It will add information about the referenced entity. NOTE: Do not type hint
   * the $wrapper argument to avoid PHP errors for the file entities. Those are
   * no true entity references, but file arrays (although they reference file
   * entities)
   *
   * @param \EntityDrupalWrapper $wrapper
   *   The wrapper to the referenced entity.
   *
   * @return array
   *   The metadata array item.
   */
  protected function buildResourceMetadataItem($wrapper) {
    if ($wrapper instanceof \EntityValueWrapper) {
      $wrapper = entity_metadata_wrapper($this->getEntityType(), $wrapper->value());
    }
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

  /**
   * Helper function to get the referenced entity ID.
   *
   * @param \EntityDrupalWrapper $property_wrapper
   *   The wrapper for the referenced file array.
   *
   * @return mixed
   *   The ID.
   */
  protected function referencedId($property_wrapper) {
    return $property_wrapper->getIdentifier() ?: NULL;
  }

  /**
   * Sets the resource field property to the schema field in the entity.
   *
   * @throws \EntityMetadataWrapperException
   */
  protected function propertyOnEntity() {
    // If there is no property try to get it based on the wrapper method and
    // store the value in the decorated object.
    $property = NULL;

    $wrapper_method = $this->getWrapperMethod();
    $wrapper = $this->entityTypeWrapper();
    if ($wrapper_method == 'label') {
      // Store the label key.
      $property = $wrapper->entityKey('label');
    }
    elseif ($wrapper_method == 'getBundle') {
      // Store the bundle key.
      $property = $wrapper->entityKey('bundle');
    }
    elseif ($wrapper_method == 'getIdentifier') {
      // Store the ID key.
      $property = $wrapper->entityKey('id');
    }

    // There are occasions when the wrapper property is not the schema
    // database field.
    if (!is_a($wrapper, '\EntityStructureWrapper')) {
      // The entity type does not exist.
      return;
    }

    /* @var $wrapper \EntityStructureWrapper */
    foreach ($wrapper->getPropertyInfo() as $wrapper_property => $property_info) {
      if (!empty($property_info['schema field']) && $property_info['schema field'] == $property) {
        $property = $wrapper_property;
        break;
      }
    }

    $this->setProperty($property);
  }

  /**
   * Populate public info field with Property API information.
   */
  protected function populatePublicInfoField() {
    $field_definition = $this->getDefinition();
    $discovery_info = empty($field_definition['discovery']) ? array() : $field_definition['discovery'];
    $public_field_info = new PublicFieldInfoEntity(
      $this->getPublicName(),
      $this->getProperty(),
      $this->getEntityType(),
      $this->getBundle(),
      $discovery_info
    );
    $this->setPublicFieldInfo($public_field_info);

    if ($field_instance = field_info_instance($this->getEntityType(), $this->getProperty(), $this->getBundle())) {
      $public_field_info->addSectionDefaults('info', array(
        'label' => $field_instance['label'],
        'description' => $field_instance['description'],
      ));
      $field_info = $this::fieldInfoField($this->getProperty());
      $section_info = array();
      $section_info['label'] = empty($field_info['label']) ? NULL : $field_info['label'];
      $section_info['description'] = empty($field_info['description']) ? NULL : $field_info['description'];
      $public_field_info->addSectionDefaults('info', $section_info);
      $type = $public_field_info instanceof PublicFieldInfoEntityInterface ? $public_field_info->getFormSchemaAllowedType() : NULL;
      $public_field_info->addSectionDefaults('form_element', array(
        'default_value' => isset($field_instance['default_value']) ? $field_instance['default_value'] : NULL,
        'type' => $type,
      ));
      // Loading allowed values can be a performance issue, load them only if
      // they are not provided in the field definition.
      $form_element_info = $public_field_info->getSection('form_element');
      if (!isset($form_element_info['allowed_values'])) {
        $allowed_values = $public_field_info instanceof PublicFieldInfoEntityInterface ? $public_field_info->getFormSchemaAllowedValues() : NULL;
        $public_field_info->addSectionDefaults('form_element', array(
          'allowed_values' => $allowed_values,
        ));
      }
    }
    else {
      // Extract the discovery information from the property info.
      try {
        $property_info = $this
          ->entityTypeWrapper()
          ->getPropertyInfo($this->getProperty());
      }
      catch(\EntityMetadataWrapperException $e) {
        return;
      }
      if (empty($property_info)) {
        return;
      }
      $public_field_info->addSectionDefaults('data', array(
        'type' => $property_info['type'],
        'required' => empty($property_info['required']) ? FALSE : $property_info['required'],
      ));
      $public_field_info->addSectionDefaults('info', array(
        'label' => $property_info['label'],
        'description' => $property_info['description'],
      ));
    }
  }

  /**
   * Gets statically cached information about a field.
   *
   * @param string $field_name
   *   The name of the field to retrieve. $field_name can only refer to a
   *   non-deleted, active field. For deleted fields, use
   *   field_info_field_by_id(). To retrieve information about inactive fields,
   *   use field_read_fields().
   *
   * @return array
   *   The field info.
   *
   * @see field_info_field()
   */
  protected static function fieldInfoField($field_name) {
    return field_info_field($field_name);
  }

}
