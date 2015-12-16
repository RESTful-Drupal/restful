<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\Field\ResourceFieldCollection.
 */

namespace Drupal\restful\Plugin\resource\Field;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface;
use Drupal\restful\Plugin\resource\Resource;

class ResourceFieldCollection implements ResourceFieldCollectionInterface {

  /**
   * The public fields.
   *
   * Every item contains a ResourceFieldInterface.
   *
   * @var array
   */
  protected $fields = array();

  /**
   * The data interpreter for all the fields in this field collection.
   *
   * @var DataInterpreterInterface $interpreter
   */
  protected $interpreter;

  /**
   * Contains the resource field representing the ID.
   *
   * @var ResourceFieldInterface $idField;
   */
  protected $idField;

  /**
   * The contexts for the field collection.
   *
   * @var ArrayCollection[]
   */
  protected $context;

  /**
   * List of fields that are allowed in the output.
   *
   * @var string[]
   */
  protected $limitFields = array();

  /**
   * Contains the resource ID this field collection is for.
   *
   * @var string
   */
  protected $resourceId;

  /**
   * Constructor.
   *
   * Creates the collection and each one of the field resource fields in it
   * based on the configuration array.
   *
   * @param array $fields
   *   Array with the optional values:
   *   - "access_callbacks": An array of callbacks to determine if user has access
   *     to the property. Note that this callback is on top of the access provided by
   *     entity API, and is used for convenience, where for example write
   *     operation on a property should be denied only on certain request
   *     conditions. The Passed arguments are:
   *     - op: The operation that access should be checked for. Can be "view" or
   *       "edit".
   *     - public_field_name: The name of the public field.
   *     - property_wrapper: The wrapped property.
   *     - wrapper: The wrapped entity.
   *   - "property": The entity property (e.g. "title", "nid").
   *   - "sub_property": A sub property name of a property to take from it the
   *     content. This can be used for example on a text field with filtered text
   *     input format where we would need to do $wrapper->body->value->value().
   *     Defaults to FALSE.
   *   - "formatter": Used for rendering the value of a configurable field using
   *     Drupal field API's formatter. The value is the $display value that is
   *     passed to field_view_field().
   *   - "wrapper_method": The wrapper's method name to perform on the field.
   *     This can be used for example to get the entity label, by setting the
   *     value to "label". Defaults to "value".
   *   - "wrapper_method_on_entity": A Boolean to indicate on what to perform
   *     the wrapper method. If TRUE the method will perform on the entity (e.g.
   *     $wrapper->label()) and FALSE on the property or sub property
   *     (e.g. $wrapper->field_reference->label()). Defaults to FALSE.
   *   - "column": If the property is a field, set the column that would be used
   *     in queries. For example, the default column for a text field would be
   *     "value". Defaults to the first column returned by field_info_field(),
   *     otherwise FALSE.
   *   - "callback": A callable callback to get a computed value. The wrapped
   *     entity is passed as argument. Defaults To FALSE.
   *     The callback function receive as first argument the entity
   *     EntityMetadataWrapper object.
   *   - "process_callbacks": An array of callbacks to perform on the returned
   *     value, or an array with the object and method. Defaults To empty array.
   *   - "resource": This property can be assigned only to an entity reference
   *     field. Array of restful resources keyed by the target bundle. For
   *     example, if the field is referencing a node entity, with "Article" and
   *     "Page" bundles, we are able to map those bundles to their related
   *     resource. Items with bundles that were not explicitly set would be
   *     ignored.
   *     It is also possible to pass an array as the value, with:
   *     - "name": The resource name.
   *     - "fullView": Determines if the referenced resource should be rendered
   *     or just the referenced ID(s) to appear. Defaults to TRUE.
   *     array(
   *       // Shorthand.
   *       'article' => 'articles',
   *       // Verbose
   *       'page' => array(
   *         'name' => 'pages',
   *         'fullView' => FALSE,
   *       ),
   *     );
   *   - "create_or_update_passthrough": Determines if a public field that isn't
   *     mapped to any property or field, may be passed upon create or update
   *     of an entity. Defaults to FALSE.
   * @param RequestInterface $request
   *   The request.
   */
  public function __construct(array $fields = array(), RequestInterface $request) {
    foreach ($fields as $public_name => $field_info) {
      $field_info['public_name'] = $public_name;
      // The default values are added.
      if (empty($field_info['resource'])) {
        $resource_field = ResourceField::create($field_info, $request);
      }
      else {
        $resource_field = ResourceFieldResource::create($field_info, $request);
      }
      $this->fields[$resource_field->id()] = $resource_field;
    }
    $this->idField = empty($fields['id']) ? NULL : $this->get('id');
  }

  /**
   * {@inheritdoc}
   */
  public static function factory(array $fields = array(), RequestInterface $request = NULL) {
    // TODO: Explore the possibility to change factory methods by using FactoryInterface.
    return new static($fields, $request ?: restful()->getRequest());
  }

  /**
   * {@inheritdoc}
   */
  public static function create() {
    static::factory(static::getInfo());
  }

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return isset($this->fields[$key]) ? $this->fields[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, ResourceFieldInterface $field) {
    $this->fields[$key] = $field;
  }

  /**
   * {@inheritdoc}
   */
  public function current() {
    return current($this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    return next($this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return key($this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    $key = key($this->fields);
    return $key !== NULL && $key !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    return reset($this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->fields);
  }

  /**
   * {@inheritdoc}
   */
  public function getInterpreter() {
    return $this->interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function setInterpreter($interpreter) {
    $this->interpreter = $interpreter;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdField() {
    return $this->idField;
  }

  /**
   * {@inheritdoc}
   */
  public function setIdField($id_field) {
    $this->idField = $id_field;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceName() {
    $resource_id = $this->getResourceId();
    $pos = strpos($resource_id, Resource::DERIVATIVE_SEPARATOR);
    return $pos === FALSE ? $resource_id : substr($resource_id, 0, $pos);
  }

  /**
   * {@inheritdoc}
   */
  public function getResourceId() {
    return $this->resourceId;
  }

  /**
   * {@inheritdoc}
   */
  public function setResourceId($resource_id) {
    $this->resourceId = $resource_id;
  }

  /**
   * {@inheritdoc}
   */
  public function evalFilter(array $filter) {
    // Initialize to TRUE for AND and FALSE for OR (neutral value).
    $match = $filter['conjunction'] == 'AND';
    for ($index = 0; $index < count($filter['value']); $index++) {
      if (!$resource_field = $this->get($filter['public_field'])) {
        // If the field is unknown don't use se filter.
        return TRUE;
      }
      $filter_value = $resource_field->value($this->getInterpreter());
      if (is_null($filter_value)) {
        // Property doesn't exist on the plugin, so filter it out.
        return FALSE;
      }

      if ($filter['conjunction'] == 'OR') {
        $match = $match || $this::evaluateExpression($filter_value, $filter['value'][$index], $filter['operator'][$index]);
        if ($match) {
          break;
        }
      }
      else {
        $match = $match && $this::evaluateExpression($filter_value, $filter['value'][$index], $filter['operator'][$index]);
        if (!$match) {
          break;
        }
      }
    }
    return $match;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext($context_id, ArrayCollection $context) {
    $this->context[$context_id] = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function getLimitFields() {
    return $this->limitFields;
  }

  /**
   * {@inheritdoc}
   */
  public function setLimitFields($limit_fields) {
    $this->limitFields = $limit_fields;
    // Make sure that the nested fields are added appropriately.
    foreach ($limit_fields as $limit_field) {
      $parts = explode('.', $limit_field);
      $this->limitFields[] = $parts[0];
    }
    $this->limitFields = array_unique($this->limitFields);
  }

  /**
   * Evaluate a simple expression.
   *
   * @param mixed $value1
   *   The first value.
   * @param mixed $value2
   *   The second value.
   * @param string $operator
   *   The operator.
   *
   * @return bool
   *   TRUE or FALSE based on the evaluated expression.
   *
   * @throws BadRequestException
   */
  protected static function evaluateExpression($value1, $value2, $operator) {
    switch($operator) {
      case '=':
        return $value1 == $value2;

      case '<':
        return $value1 < $value2;

      case '>':
        return $value1 > $value2;

      case '>=':
        return $value1 >= $value2;

      case '<=':
        return $value1 <= $value2;

      case '<>':
      case '!=':
        return $value1 != $value2;

      case 'IN':
        return in_array($value1, $value2);

      case 'BETWEEN':
        return $value1 >= $value2[0] && $value1 >= $value2[1];
    }
    return FALSE;
  }

}
