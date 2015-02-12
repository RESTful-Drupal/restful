<?php

/**
 * @file
 * Contains \Drupal\restful\Plugin\resource\DataProvider\DataProvider.
 */

namespace Drupal\restful\Plugin\resource\DataProvider;

use Drupal\restful\Exception\BadRequestException;
use Drupal\restful\Http\RequestInterface;
use Drupal\restful\Plugin\resource\Field\ResourceFieldCollectionInterface;

abstract class DataProvider implements DataProviderInterface {

  // TODO: Create the interface for this class.

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
   * Constructor.
   *
   * @param RequestInterface $request
   *   The request.
   * @param ResourceFieldCollectionInterface $field_definitions
   *   The field definitions.
   * @param object $account
   *   The authenticated account.
   * @param int $range
   *   The range
   */
  public function __construct(RequestInterface $request, ResourceFieldCollectionInterface $field_definitions, $account, $range) {
    $this->request = $request;
    $this->fieldDefinitions = $field_definitions;
    $this->account = $account;
    $this->range = $range;
  }

  /**
   * Gets the range.
   *
   * @return int
   */
  public function getRange() {
    return $this->range;
  }

  /**
   * Gets the authenticated account.
   *
   * @return object
   */
  public function getAccount() {
    return $this->account;
  }

  /**
   * Gets the request.
   *
   * @return RequestInterface
   *   The request
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Parses the request to get the sorting options.
   *
   * @return array
   *   With the different sorting options.
   *
   * @throws BadRequestException
   */
  protected function parseRequestForListSort() {
    $input = $this->getRequest()->getParsedInput();

    if (empty($input['sort'])) {
      return array();
    }
    // TODO: Find a way to pass in the plugin definition options without having business logic leaks between classes.
    // TODO: Maybe create an adapter to make the getPluginKey functional?
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['sort']) {
      throw new BadRequestException('Sort parameters have been disabled in server configuration.');
    }

    $sorts = array();
    foreach (explode(',', $input['sort']) as $sort) {
      $direction = $sort[0] == '-' ? 'DESC' : 'ASC';
      $sort = str_replace('-', '', $sort);
      // Check the sort is on a legal key.
      if (empty($this->fieldDefinitions[$sort])) {
        throw new BadRequestException(format_string('The sort @sort is not allowed for this path.', array('@sort' => $sort)));
      }

      $sorts[$sort] = $direction;
    }
    return $sorts;
  }

  /**
   * Filter the query for list.
   *
   * @throws BadRequestException
   *
   * @returns array
   *   An array of filters to apply.
   *
   * @see \RestfulEntityBase::getQueryForList
   */
  protected function parseRequestForListFilter() {
    if (!$this->request->isListRequest()) {
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
    // TODO: Find a way to pass in the plugin definition options without having business logic leaks between classes.
    // TODO: Maybe create an adapter to make the getPluginKey functional?
    $url_params = $this->getPluginKey('url_params');
    if (!$url_params['filter']) {
      throw new BadRequestException('Filter parameters have been disabled in server configuration.');
    }

    $filters = array();

    foreach ($input['filter'] as $public_field => $value) {
      if (empty($this->fieldDefinitions[$public_field])) {
        throw new BadRequestException(format_string('The filter @filter is not allowed for this path.', array('@filter' => $public_field)));
      }

      // Filtering can be achieved in different ways:
      //   1. filter[foo]=bar
      //   2. filter[foo][0]=bar&filter[foo][1]=baz
      //   3. filter[foo][value]=bar
      //   4. filter[foo][value][0]=bar&filter[foo][value][1]=baz
      if (!is_array($value)) {
        // Request uses the shorthand form for filter. For example
        // filter[foo]=bar would be converted to filter[foo][value] = bar.
        $value = array('value' => $value);
      }
      if (!is_array($value['value'])) {
        $value['value'] = array($value['value']);
      }
      // Add the property
      $value['public_field'] = $public_field;

      // Set default operator.
      $value += array('operator' => array_fill(0, count($value['value']), '='));
      if (!is_array($value['operator'])) {
        $value['operator'] = array($value['operator']);
      }

      // Make sure that we have the same amount of operators than values.
      if (!in_array(strtoupper($value['operator'][0]), array('IN', 'BETWEEN')) && count($value['value']) != count($value['operator'])) {
        throw new BadRequestException('The number of operators and values has to be the same.');
      }

      $value += array('conjunction' => 'AND');

      // Clean the operator in case it came from the URL.
      // e.g. filter[minor_version][operator]=">="
      $value['operator'] = str_replace(array('"', "'"), '', $value['operator']);

      static::isValidOperatorsForFilter($value['operator']);
      static::isValidConjunctionForFilter($value['conjunction']);

      $filters[] = $value;
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
   */
  protected function parseRequestForListPagination() {
    $input = $this->getRequest()->getParsedInput();
    $page = isset($input['page']) ? $input['page'] : 1;

    if (!ctype_digit((string) $page) || $page < 1) {
      throw new BadRequestException('"Page" property should be numeric and equal or higher than 1.');
    }

    $range = $this->getRange();
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
      'IN',
      'BETWEEN',
    );

    foreach ($operators as $operator) {
      if (!in_array($operator, $allowed_operators)) {
        throw new BadRequestException(format_string('Operator "@operator" is not allowed for filtering on this resource. Allowed operators are: !allowed', array(
          '@operator' => $operators,
          '!allowed' => implode(', ', $allowed_operators),
        )));
      }
    }
  }

  /**
   * Check if a conjunction is valid for filtering.
   *
   * @param string $conjunction
   *   The operator.
   *
   * @throws BadRequestException
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

}
