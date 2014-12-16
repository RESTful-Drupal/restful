# Using Your API Within Drupal


## Sort
You can sort the list of entities by multiple properties. Prefixing the property
with a dash (``-``) will sort is in a descending order.
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


## Filter
RESTful allows filtering of a list.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['filter'] = array('label' => 'abc');
$result = $handler->get('', $request);
```

## Autocomplete
By passing the autocomplete query string in the request, it is possible to change
the normal listing behavior into autocomplete.

The following is the API equivilent of
``https://example.com?autocomplete[string]=foo&autocomplete[operator]=STARTS_WITH``

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


## Range
RESTful allows you to cotrol the number of elements per page you want to show. This value will always be limited by the `$range` variable in your resource class. This variable, in turn, defaults to 50.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['range'] = 25;
$result = $handler->get('', $request);
```

## Sub-requests
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


### Error handling
If an error occurs while using the API within Drupal, a PHP ``Exception``
is thrown.
