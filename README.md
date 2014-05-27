[![Build Status](https://travis-ci.org/Gizra/restful.png?branch=7.x-1.x)](https://travis-ci.org/Gizra/restful)

# RESTful best practices for Drupal

This module follows [this post](http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api) to achieve a _practical_ RESTful for Drupal.
The aim of the module, is to allow exposing an API, without Drupal's data structure leaking to it.

## Concept
The following also describes the difference between other modules such as RestWs and Services Entity.

* Restful module requires explicitly declaring the exposed API. When enabling the
module nothing will happen until the implementing developer will declare it
* Instead of exposing resources by entity type (e.g. node, taxonomy term), Restful
cares about bundles. So for example you may expose the ``Article`` content type, but
not the ``Page`` content type
* The exposed properties need to be explicitly declared. This allows a _clean_ output
without Drupal's internal implementation leaking out. This means the consuming
client doesn't need to know if an entity is a node or a term, nor will they be presented
with the ``field_`` prefix
* One of the core features is versioning. While it's debatable if this feature
 is indeed a pure REST, we believe it's a best practice one
* Only JSON format is supported
* Audience is developers and not site builders


## API via Drupal

Assuming you have enabled the RESTful example module

### Getting handlers

```php
// Get handler v1.0
$handler = restful_get_restful_handler('articles');

// Get handler v1.1
$handler = restful_get_restful_handler('articles', 1, 1);
```

### Create and update an entity
```php
$handler = restful_get_restful_handler('articles');
// POST method, to create.
$result = $handler->post('', array('label' => 'example title'));
$id = $result['id'];

// PUT method, to update.
$request['label'] = 'new title';
$handler->put($id, $request);
```

### View an entity
```php
// Handler v1.0
$handler = restful_get_restful_handler('articles');
// GET method.
$result = $handler->get(1);

// Output:
array(
  'id' => 1,
  'label' => 'example title',
  'self' => 'https://example.com/node/1',
);

// Handler v1.1 extends v1.0, and removes the "self" property from the
// exposed properties.
$handler = restful_get_restful_handler('articles', 1, 1);
$result = $handler->get(1);

// Output:
array(
  'id' => 1,
  'label' => 'example title',
);
```

#### Filtering fields
Using the ``?fields`` query string, you can decalre which fields should be
returned.

```php
$handler = restful_get_restful_handler('articles');

// Define the fields.
$request['fields'] = 'id,label';
$result = $handler->get(2, $request);

// Output:
array(
  'id' => 2,
  'label' => 'another title',
);
```

### List entities
```php
$handler = restful_get_restful_handler('articles');
$result = $handler->get();

// Output:
array(
  'list' => array(
    array(
      'id' => 1,
      'label' => 'example title',
      'self' => 'https://example.com/node/1',
    );
    array(
      'id' => 2,
      'label' => 'another title',
      'self' => 'https://example.com/node/2',
    );
  ),
);
```

#### Sort
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
  'list' => array(
    array(
      'id' => 2,
      'label' => 'another title',
      'self' => 'https://example.com/node/2',
    );
    array(
      'id' => 1,
      'label' => 'example title',
      'self' => 'https://example.com/node/1',
    );
  ),
);
```

## API via URL

### View an Article

```shell
# Handler v1.0
curl https://example.com/api/v1/articles/1

# Handler v1.1
curl https://example.com/api/v1/articles/1 \
  -H "Restful-Minor-Version: 1"
```

## Authentication providers

Restful comes with ``cookie``, ``base_auth`` (user name and password in the HTTP header) authentications providers, as well as a "RESTful token auth" module that has a ``token`` authentication provider.


```bash
# (Change username and password)
curl -u "username:password" https://example.com/api/login

# Response has access token.
{"access_token":"YOUR_TOKEN"}

# Call a "protected" with token resource (Articles resource version 1.3 in "Restful example")
curl https://example.com/api/v1/articles/1?access_token=YOUR_TOKEN \
  -H "Restful-Minor-Version: 3"
```

### Error handling
While a PHP ``Exception`` is thrown when using the API via Drupal, this is not the
case when consuming the API externally. Instead of the exception a valid JSON
with ``code``, ``message`` and ``description`` would be returned.

For example, trying to sort a list by an invalid key

```shell
curl https://example.com/api/v1/articles?sort=wrong_key
```

Will result with an HTTP code 400, and the following JSON:

```javascript
{
  code: 400,
  message: "The sort wrong_key is not allowed for this path.",
  description: "Bad Request."
}
```

## Module dependencies
* [Entity API](https://drupal.org/project/entity), with the following patches:
  * [$wrapper->access() might be wrong for single entity reference field](https://drupal.org/node/2264079#comment-8768581)
  * [Prevent notice in entity_metadata_no_hook_node_access() when node is not saved](https://drupal.org/node/2086225#comment-8768373)

## Credits

Developed by [Gizra](http://gizra.com)
