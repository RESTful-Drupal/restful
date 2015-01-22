# Using Your API Within Drupal

The RESTful module allows your resources to be used within Drupal itself. For
example, you could define a resource, and then operate it within another
custom module.

In general, this is accomplished by using `restful_get_restful_handler` to get a
handler for your resource, and then calling methods such as `get` or `post` to
make a request, which will operate the resource.

The request itself can be customized by passing in an array of key/value pairs.



## Read Contexts

The following keys apply to read contexts, in which you are using the `get`
method to return results from a resource.

### Sort
You can use the `'sort'` key to sort the list of entities by multiple
properties.  List every property in a comma-separated string, in the order that
you want to sort by.  Prefixing the property name with a dash (``-``) will sort
 by that property in a descending order; the default is ascending.

Bear in mind that for entity based resources, only those fields with a
`'property'` (matching to an entity property or a Field API field) can be used
for sorting.

If no sorting is specified the default sorting is by the entity ID.

```php
$handler = restful_get_restful_handler('articles');

// Define the sorting by ID (descending) and label (ascending).
$request['sort'] = '-id,label';
$result = $handler->get('', $request);

// Output:
array(
  'data' => array(
    array(
      'id' => 2,
      'label' => 'another title',
      'self' => 'https://example.com/node/2',
    ),
    array(
      'id' => 1,
      'label' => 'example title',
      'self' => 'https://example.com/node/1',
    ),
  ),
);
```


### Filter
Use the `'filter'` key to filter the list. You can provide as many filters as
you need.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['filter'] = array('label' => 'abc');
$result = $handler->get('', $request);
```

Bear in mind that for entity based resources, only those fields with a
`'property'` (matching to an entity property or a Field API field) can be used
for filtering.

Additionally you can provide multiple filters for the same field. That is
specially useful when filtering on multiple value fields. The following example
will get all the articles with the integer multiple field that contains all 1, 3
and 5.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['filter'] = array('integer_multiple' => array(
  'values' => array(1, 3, 5),
));
$result = $handler->get('', $request);
```

You can do more advanced filtering by providing values and operators. The
following example will get all the articles with an integer value more than 5
and another equal to 10.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['filter'] = array('integer_multiple' => array(
  'values' => array(5, 10),
  'operator' => array('>', '='),
));
$result = $handler->get('', $request);
```

### Autocomplete
By using the `'autocomplete'` key and supplying a query string, it is possible
to change the normal listing behavior into autocomplete.  This also changes
the normal output objects into key/value pairs which can be fed directly into
a Drupal autocomplete field.

The following is the API equivalent of
`https://example.com?autocomplete[string]=foo&autocomplete[operator]=STARTS_WITH`

```php
$handler = restful_get_restful_handler('articles');

$request = array(
  'autocomplete' => array(
    'string' => 'foo',
    // Optional, defaults to "CONTAINS".
    'operator' => 'STARTS_WITH',
  ),
);

$handler->get('', $request);
```


### Range
Using the `'range'` key, you can control the number of elements per page you
want to show. This value will always be limited by the `$range` variable in your
 resource class. This variable defaults to 50.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['range'] = 25;
$result = $handler->get('', $request);
```

## Write Contexts

The following techniques apply to write contexts, in which you are using the
`post` method to create an entity defined by a resource.

### Sub-requests
It is possible to create multiple referencing entities in a single request. A
typical example would be a node referencing a new taxonomy term. For example if
there was a taxonomy reference or entity reference field called ``field_tags``
on the  Article bundle (node) with an ``articles`` and a Tags bundle (taxonomy
term) with a ``tags`` resource, we would define the relation via the
``RestfulEntityBase::publicFieldsInfo()``

```php
public function publicFieldsInfo() {
  $public_fields = parent::publicFieldsInfo();
  // ...
  $public_fields['tags'] = array(
    'property' => 'field_tags',
    'resource' => array(
      'tags' => 'tags',
    ),
  );
  // ...
  return $public_fields;
}

```

And create both entities with a single request:

```php
$handler = restful_get_restful_handler('articles');
$request = array(
  'label' => 'parent',
  'body' => 'Drupal',
  'tags' => array(
    array(
      // Create a new term.
      'label' => 'child1',
    ),
    array(
      // PATCH an existing term.
      'label' => 'new title by PATCH',
    ),
    array(
      '__application' => array(
        'method' => \RestfulInterface::PUT,
      ),
      // PUT an existing term.
      'label' => 'new title by PUT',
    ),
  ),
);

$handler->post('', $request);
```


## Error handling
If an error occurs while using the API within Drupal, a custom exception is
thrown.  All the exceptions thrown by the RESTful module extend the
`\RestfulException` class.
