<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProvider.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Exception\UnprocessableEntityException;
use Drupal\restful\Http\HttpHeader;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Decorators\CacheDecoratedResource;
use Drupal\restful\Plugin\resource\Field\PublicFieldInfo\PublicFieldInfoBase;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollection;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldInterface;

abstract class DataProvider implements DataProviderInterface {

  /**
   * The field definitions.
   *
   * @var ResourceFieldCollectionInterface
   */
  protected $fieldDefinitions;

  /**
   * The request
   *
   * @var RequestInterface
   */
  protected $request;

  /**
   * Determines the number of items that should be returned when viewing lists.
   *
   * @var int
   */
  protected $range = 50;

  /**
   * The account authenticated from the request for entity access checks.
   *
   * @var object
   */
  protected $account;

  /**
   * Determines the language of the items that should be returned.
   *
   * @var string
   */
  protected $langcode;

  /**
   * User defined options.
   *
   * @var array
   */
  protected $options = array();

  /**
   * The resource path.
   *
   * @var string
   */
  protected $resourcePath;

  /**
   * Array of metadata. Use this as a mean to pass info to the render layer.
   *
   * @var ArrayCollection
   */
  protected $metadata;

  /**
   * Resource identifier.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * {@inheritdoc}
   */
  public static function processFilterInput($filter, $public_field) {
    // Filtering can be achieved in different ways:
    // 1. filter[foo]=bar
    // 2. filter[foo][0]=bar&filter[foo][1]=baz
    // 3. filter[foo][value]=bar
    // 4. filter[foo][value][0]=bar&filter[foo][value][1]=baz
    if (!is_array($filter)) {
      // Request uses the shorthand form for filter. For example
      // filter[foo]=bar would be converted to filter[foo][value] = bar.
      $filter = array('value' => $filter);
    }
    if (!isset($filter['value'])) {
      throw new BadRequestException(sprintf('Value not present for the "%s" filter. Please check the URL format.', $public_field));
    }
    if (!is_array($filter['value'])) {
      $filter['value'] = array($filter['value']);
    }
    // Add the property.
    $filter['public_field'] = $public_field;

    // Set default operator.
    $filter += array('operator' => array_fill(0, count($filter['value']), '='));
    if (!is_array($filter['operator'])) {
      $filter['operator'] = array($filter['operator']);
    }

    // Make sure that we have the same amount of operators than values.
    $first_operator = strtoupper($filter['operator'][0]);
    if (!in_array($first_operator, array(
        'IN',
        'NOT IN',
        'BETWEEN',
      )) && count($filter['value']) != count($filter['operator'])
    ) {
      throw new BadRequestException('The number of operators and values has to be the same.');
    }
    // Make sure that the BETWEEN operator gets only 2 values.
    if ($first_operator == 'BETWEEN' && count($filter['value']) != 2) {
      throw new BadRequestException('The BETWEEN operator takes exactly 2 values.');
    }

    $filter += array('conjunction' => 'AND');

    // Clean the operator in case it came from the URL.
    // e.g. filter[minor_version][operator][0]=">="
    // str_replace will process all the elements in the array.
    $filter['operator'] = str_replace(array('"', "'"), '', $filter['operator']);

    static::isValidOperatorsForFilter($filter['operator']);
    static::isValidConjunctionForFilter($filter['conjunction']);
    return $filter;
  }

  /**
   * Constructor.
   *
   * @param RequestInterface $request
   *   The request.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   * @param object $account
   *   The authenticated account.
   * @param string $plugin_id
   *   The resource ID.
   * @param string $resource_path
   *   The resource path.
   * @param array $options
   *   The plugin options for the data provider.
   * @param string $langcode
   *   (Optional) The entity language code.
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $plugin_id, $resource_path = NULL, array $options = array(), $langcode = NULL) {
    $this->request = $request;
    $this->fieldDefinitions = $field_definitions;
    $this->account = $account;
    $this->pluginId = $plugin_id;
    $this->options = $options;
    $this->resourcePath = $resource_path;
    if (!empty($options['range'])) {
      // TODO: Document that the range is now overridable in the annotation.
      $this->range = $options['range'];
    }
    $this->langcode = $langcode ?: static::getLanguage();
    $this->metadata = new ArrayCollection();
  }

  /**
   * {@inheritdoc}
   */
  public function getRange() {
    return $this->range;
  }

  /**
   * {@inheritdoc}
   */
  public function setRange($range) {
    $this->range = $range;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount($account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequest(RequestInterface $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public function getLangCode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function setLangCode($langcode) {
    $this->langcode = $langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function addOptions(array $options) {
    $this->options = array_merge($this->options, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheFragments($identifier) {
    // If we are trying to get the context for multiple ids, join them.
    if (is_array($identifier)) {
      $identifier = implode(',', $identifier);
    }
    $fragments = new ArrayCollection(array(
      'resource' => CacheDecoratedResource::serializeKeyValue($this->pluginId, $this->canonicalPath($identifier)),
    ));
    $options = $this->getOptions();
    switch ($options['renderCache']['granularity']) {
      case DRUPAL_CACHE_PER_USER:
        if ($uid = $this->getAccount()->uid) {
          $fragments->set('user_id', (int) $uid);
        }
        break;
      case DRUPAL_CACHE_PER_ROLE:
        $fragments->set('user_role', implode(',', $this->getAccount()->roles));
        break;
    }
    return $fragments;
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    if (!$ids = $this->getIndexIds()) {
      return array();
    }

    return $this->viewMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function discover($path = NULL) {
    // Alter the field definition by adding a callback to get the auto
    // discover information in render time.
    foreach ($this->fieldDefinitions as $public_field_name => $resource_field) {
      /* @var ResourceFieldInterface $resource_field */
      if (method_exists($resource_field, 'autoDiscovery')) {
        // Adding the autoDiscover method to the resource field class will allow
        // you to be smarter about the auto discovery information.
        $callable = array($resource_field, 'autoDiscovery');
      }
      else {
        // If the given field does not have discovery information, provide the
        // empty one instead of an error.
        $callable = array('\Drupal\restful\Plugin\resource\Field\ResourceFieldBase::emptyDiscoveryInfo', array($public_field_name));
      }
      $resource_field->setCallback($callable);
      // Remove the process callbacks, those don't make sense during discovery.
      $resource_field->setProcessCallbacks(array());
      $definition = $resource_field->getDefinition();
      $discovery_info = empty($definition['discovery']) ? array() : $definition['discovery'];
      $resource_field->setPublicFieldInfo(new PublicFieldInfoBase($resource_field->getPublicName(), $discovery_info));
    }
    return $path ? $this->viewMultiple(array($path)) : $this->index();
  }

  /**
   * {@inheritdoc}
   */
  public function canonicalPath($path) {
    // Assume that there is no alias.
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function methodAccess(ResourceFieldInterface $resource_field) {
    return in_array($this->getRequest()->getMethod(), $resource_field->getMethods());
  }

  /**
   * Parses the request to get the sorting options.
   *
   * @return array
   *   With the different sorting options.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \Drupal\restful\Exception\UnprocessableEntityException
   */
  protected function parseRequestForListSort() {
    $input = $this->getRequest()->getParsedInput();

    if (empty($input['sort'])) {
      return array();
    }

    $url_params = $this->options['urlParams'];
    if (!$url_params['sort']) {
      throw new UnprocessableEntityException('Sort parameters have been disabled in server configuration.');
    }

    $sorts = array();
    foreach (explode(',', $input['sort']) as $sort) {
      $direction = $sort[0] == '-' ? 'DESC' : 'ASC';
      $sort = str_replace('-', '', $sort);
      // Check the sort is on a legal key.
      if (!$this->fieldDefinitions->get($sort)) {
        throw new BadRequestException(format_string('The sort @sort is not allowed for this path.', array('@sort' => $sort)));
      }

      $sorts[$sort] = $direction;
    }
    return $sorts;
  }

  /**
   * Filter the query for list.
   *
   * @returns array
   *   An array of filters to apply.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   * @throws \Drupal\restful\Exception\UnprocessableEntityException
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function parseRequestForListFilter() {
    if (!$this->request->isListRequest($this->getResourcePath())) {
      // Not a list request, so we don't need to filter.
      // We explicitly check this, as this function might be called from a
      // formatter plugin, after RESTful's error handling has finished, and an
      // invalid key might be passed.
      return array();
    }
    $input = $this->getRequest()->getParsedInput();
    if (empty($input['filter'])) {
      // No filtering is needed.
      return array();
    }

    $url_params = empty($this->options['urlParams']) ? array() : $this->options['urlParams'];
    if (isset($url_params['filter']) && !$url_params['filter']) {
      throw new UnprocessableEntityException('Filter parameters have been disabled in server configuration.');
    }

    $filters = array();

    foreach ($input['filter'] as $public_field => $value) {
      if (!static::isNestedField($public_field) && !$this->fieldDefinitions->get($public_field)) {
        throw new BadRequestException(format_string('The filter @filter is not allowed for this path.', array('@filter' => $public_field)));
      }
      $filter = static::processFilterInput($value, $public_field);
      $filters[] = $filter + array('resource_id' => $this->pluginId);
    }

    return $filters;
  }

  /**
   * Parses the request object to get the pagination options.
   *
   * @return array
   *   A numeric array with the offset and length options.
   *
   * @throws BadRequestException
   * @throws UnprocessableEntityException
   */
  protected function parseRequestForListPagination() {
    $pager_input = $this->getRequest()->getPagerInput();

    $page = isset($pager_input['number']) ? $pager_input['number'] : 1;
    if (!ctype_digit((string) $page) || $page < 1) {
      throw new BadRequestException('"Page" property should be numeric and equal or higher than 1.');
    }

    $range = isset($pager_input['size']) ? (int) $pager_input['size'] : $this->getRange();
    $range = $range > $this->getRange() ? $this->getRange() : $range;
    if (!ctype_digit((string) $range) || $range < 1) {
      throw new BadRequestException('"Range" property should be numeric and equal or higher than 1.');
    }

    $url_params = empty($this->options['urlParams']) ? array() : $this->options['urlParams'];
    if (isset($url_params['range']) && !$url_params['range']) {
      throw new UnprocessableEntityException('Range parameters have been disabled in server configuration.');
    }

    $offset = ($page - 1) * $range;
    return array($offset, $range);
  }

  /**
   * Adds query tags and metadata to the EntityFieldQuery.
   *
   * @param \EntityFieldQuery|\SelectQuery $query
   *   The query to enhance.
   */
  protected function addExtraInfoToQuery($query) {
    // Add a generic tags to the query.
    $query->addTag('restful');
    $query->addMetaData('account', $this->getAccount());
  }

  /**
   * Check if an operator is valid for filtering.
   *
   * @param array $operators
   *   The array of operators.
   *
   * @throws BadRequestException
   */
  protected static function isValidOperatorsForFilter(array $operators) {
    $allowed_operators = array(
      '=',
      '>',
      '<',
      '>=',
      '<=',
      '<>',
      '!=',
      'NOT IN',
      'BETWEEN',
      'CONTAINS',
      'IN',
      'NOT IN',
      'STARTS_WITH',
    );

    foreach ($operators as $operator) {
      if (!in_array($operator, $allowed_operators)) {
        throw new BadRequestException(sprintf('Operator "%s" is not allowed for filtering on this resource. Allowed operators are: %s', $operator, implode(', ', $allowed_operators)));
      }
    }
  }

  /**
   * Check if a conjunction is valid for filtering.
   *
   * @param string $conjunction
   *   The operator.
   *
   * @throws \Drupal\restful\Exception\BadRequestException
   */
  protected static function isValidConjunctionForFilter($conjunction) {
    $allowed_conjunctions = array(
      'AND',
      'OR',
      'XOR',
    );

    if (!in_array(strtoupper($conjunction), $allowed_conjunctions)) {
      throw new BadRequestException(format_string('Conjunction "@conjunction" is not allowed for filtering on this resource. Allowed conjunctions are: !allowed', array(
        '@conjunction' => $conjunction,
        '!allowed' => implode(', ', $allowed_conjunctions),
      )));
    }
  }

  /**
   * Gets the global language.
   *
   * @return string
   *   The language code.
   */
  protected static function getLanguage() {
    // Move to its own method to allow unit testing.
    return $GLOBALS['language']->language;
  }

  /**
   * Sets an HTTP header.
   *
   * @param string $name
   *   The header name.
   * @param string $value
   *   The header value.
   */
  protected function setHttpHeader($name, $value) {
    $this
      ->getRequest()
      ->getHeaders()
      ->add(HttpHeader::create($name, $value));
  }

  /**
   * {@inheritdoc}
   */
  public function setResourcePath($resource_path) {
    $this->resourcePath = $resource_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getResourcePath() {
    return $this->resourcePath;
  }

  /**
   * {@inheritdoc}
   */
  public static function isNestedField($field_name) {
    return strpos($field_name, '.') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * Initialize the empty resource field collection to bundle the output.
   *
   * @param mixed $identifier
   *   The ID of thing being viewed.
   *
   * @return ResourceFieldCollectionInterface
   *   The collection of fields.
   *
   * @throws \Drupal\restful\Exception\NotFoundException
   */
  protected function initResourceFieldCollection($identifier) {
    $resource_field_collection = new ResourceFieldCollection(array(), $this->getRequest());
    $interpreter = $this->initDataInterpreter($identifier);
    $resource_field_collection->setInterpreter($interpreter);
    $id_field_name = empty($this->options['idField']) ? 'id' : $this->options['idField'];
    $resource_field_collection->setIdField($this->fieldDefinitions->get($id_field_name));
    $resource_field_collection->setResourceId($this->pluginId);
    return $resource_field_collection;
  }

  /**
   * Get the data interpreter.
   *
   * @param mixed $identifier
   *   The ID of thing being viewed.
   *
   * @return \Drupal\restful\Plugin\resource\DataInterpreter\DataInterpreterInterface
   *   The data interpreter.
   */
  abstract protected function initDataInterpreter($identifier);

}
