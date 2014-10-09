[![Build Status](https://travis-ci.org/Gizra/restful.svg?branch=7.x-1.x)](https://travis-ci.org/Gizra/restful)

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
* It has configurable output formats. It ships with JSON and XML as examples. HAL+JSON is the recommended default.
* Audience is developers and not site builders
* Provide a key tool for a headless Drupal. See the [AngularJs form](https://github.com/Gizra/restful/blob/7.x-1.x/modules/restful_angular_example/README.md) example module.

## Module dependencies
* [Entity API](https://drupal.org/project/entity), with the following patches:
  * [$wrapper->access() might be wrong for single entity reference field](https://www.drupal.org/node/2264079#comment-8911637)
  * [Prevent notice in entity_metadata_no_hook_node_access() when node is not saved](https://drupal.org/node/2086225#comment-8768373)


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

// PATCH method to update only the title.
$request['label'] = 'new title';
$handler->patch($id, $request);
```

### View an entity
By default the RESTful module will expose the ID, label and URL of the entity.
You probably want to expose more than that. To do so you will need to implement
the `publicFieldsInfo` method defining the names in the output array and how
those are mapped to the queried entity. For instance the following example will
retrieve the basic fields plus the body, tags and images from an article node.
The RESTful module will know to use the `MyRestfulPlugin` class because your
plugin definition will say so.

```php
class MyArticlesResource extends \RestfulEntityBase {

  /**
   * Overrides \RestfulEntityBase::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    $public_fields['tags'] = array(
      'property' => 'field_tags',
      'resource' => array(
        'tags' => 'tags',
      ),
    );

    $public_fields['image'] = array(
      'property' => 'field_image',
      'process_callbacks' => array(
        array($this, 'imageProcess'),
      ),
      // This will add 3 image variants in the output.
      'image_styles' => array('thumbnail', 'medium', 'large'),
    );

    return $public_fields;
  }

}
```

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

#### Image derivatives
Many client side technologies have lots of problems resizing images to serve
them optimized and thus avoiding browser scaling. For that reason the RESTful
module will let you specify an array of image style names to get an array of
image derivatives for your image fields. Just add an `'image_styles'` key in
your public field info (as shown above) with the list of styles to use and be
done with it.

### List entities
```php
$handler = restful_get_restful_handler('articles');
$result = $handler->get();

// Output:
array(
  'data' => array(
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
RESTful allows filtering of a list.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['filter'] = array('label' => 'abc');
$result = $handler->get('', $request);
```

### Autocomplete
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

## API via URL

### View an Article

```shell
# Handler v1.0
curl https://example.com/api/v1/articles/1

# Handler v1.1
curl https://example.com/api/v1/articles/1 \
  -H "X-Restful-Minor-Version: 1"
```

### View multiple Articles at once

```shell
# Handler v1.1
curl https://example.com/api/v1/articles/1,2 \
  -H "X-Restful-Minor-Version: 1"
```

## Authentication providers

Restful comes with ``cookie``, ``base_auth`` (user name and password in the HTTP header)
authentications providers, as well as a "RESTful token auth" module that has a
``token`` authentication provider.

Note: if you use cookie-based authentication then you also need to set the
HTTP ``X-CSRF-Token`` header on all writing requests (POST, PUT and DELETE).
You can retrieve the token from ``/api/session/token`` with a standard HTTP
GET request.

See [this](https://github.com/Gizra/angular-restful-auth) AngularJs example that shows a login from a fully decoupled web app
to a Drupal backend.


```bash
# (Change username and password)
curl -u "username:password" https://example.com/api/login

# Response has access token.
{"access_token":"YOUR_TOKEN"}

# Call a "protected" with token resource (Articles resource version 1.3 in "Restful example")
curl https://example.com/api/v1/articles/1?access_token=YOUR_TOKEN \
  -H "X-Restful-Minor-Version: 3"
```

### Error handling
While a PHP ``Exception`` is thrown when using the API via Drupal, this is not the
case when consuming the API externally. Instead of the exception a valid JSON
with ``code``, ``message`` and ``description`` would be returned.

The RESTful module adheres to the [Problem Details for HTTP
APIs](http://tools.ietf.org/html/draft-nottingham-http-problem-06) draft to
improve DX when dealing with HTTP API errors. Download and enable the [Advanced
Help](https://drupal.org/project/advanced_help) module for more information
about the errors.

For example, trying to sort a list by an invalid key

```shell
curl https://example.com/api/v1/articles?sort=wrong_key
```

Will result with an HTTP code 400, and the following JSON:

```javascript
{
  'type' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.4.1',
  'title' => 'The sort wrong_key is not allowed for this path.',
  'status' => 400,
  'detail' => 'Bad Request.',
}
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
## Output formats

The RESTful module outputs all resources by using HAL+JSON encoding by default.
That means that when you have the following data:

```php
array(
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
);
```

Then the following output is generated (using the header
`ContentType:application/hal+json; charset=utf-8`):

```javascript
{
  "data": [
    {
      "id": 2,
      "label": "another title",
      "self": "https:\/\/example.com\/node\/2"
    },
    {
      "id": 1,
      "label": "example title",
      "self": "https:\/\/example.com\/node\/1"
    }
  ],
  "count": 2,
  "_links": []
}
```

You can change that to be anything that you need. You have a plugin that will
allow you to output XML instead of JSON in
[the example module](./modules/restful_example/plugins/formatter). Take that
example and create you custom module that contains the formatter plugin the you
need (maybe you need to output JSON but following a different data structure,
you may even want to use YAML, ...). All that you will need is to create a
formatter plugin and tell your restful resource to use that in the restful
plugin definition:

```php
$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'description' => t('Export the article content type in my cool format.'),
  ...
  'formatter' => 'my_formatter', // <-- The name of the formatter plugin.
);
```

### Changing the default output format.
If you need to change the output format for everything at once then you just
have to set a special variable with the name of the new output format plugin.
When you do that all the resources that don't specify a `'formatter'` key in the
plugin definition will use that output format by default. Ex:

```php
variable_set('restful_default_output_formatter', 'my_formatter');
```

## Cache layer
The RESTful module is compatible and leverages the popular
[Entity Cache](https://drupal.org/project/entitycache) module and adds a new
cache layer on its own for the rendered entity. Two requests made by the same
user requesting the same fields on the same entity will benefit from the render
cache layer. This means that no entity will need to be loaded if it was rendered
in the past under the same conditions.

Developers have absolute control where the cache is stored and the expiration
for every resource, meaning that very volatile resources can skip cache entirely
while other resources can have its cache in MemCached or the database. To
configure this developers just have to specify the following keys in their
_restful_ plugin definition.

## Rate Limit
RESTful provides rate limit functionality out of the box. A rate limit is a way
to protect your API service from flooding, basically consisting on checking is
the number of times an event has happened in a given period is greater that the
maximum allowed.

### Rate Limit events
You can define your own rate limit events for your resources and define the
limit an period for those, for that you only need to create a new _rate\_limit_
CTools plugin and implement the `isRequestedEvent` method. Every request the
`isRequestedEvent` will be evaluated and if it returns true that request will
increase the number of hits -for that particular user- for that event. If the
number of hits is bigger than the allowed limit an exception will be raised.

Two events are provided out of the box: the request event -that is always true
for every request- and the global event -that is always true and is not
contained for a given resource, all resources will increment the hit counter-.

This way, for instance, you could define different limit for read operations
than for write operations by checking the HTTP method in `isRequestedEvent`.

### Configuring your Rate Limits
You can configure the declared Rate Limit events in every resource by providing
a configuration array. The following is taken from the example resource articles
1.4 (articles\_\_1\_4.inc):

```php
…
  'rate_limit' => array(
    // The 'request' event is the basic event. You can declare your own events.
    'request' => array(
      'event' => 'request',
      // Rate limit is cleared every day.
      'period' => new \DateInterval('P1D'),
      'limits' => array(
        'authenticated user' => 3,
        'anonymous user' => 2,
        'administrator' => \RestfulRateLimitManager::UNLIMITED_RATE_LIMIT,
      ),
    ),
  ),
…
```

As you can see in the example you can set the rate limit differently depending
on the role of the visiting user.

Since the global event is not tied to any resource the limit and period is specified by setting the following variables:
  - `restful_global_rate_limit`: The number of allowed hits. This is global for
    all roles.
  - `restful_global_rate_period`: The period string compatible with
    \DateInterval.

## Modules integration
* [Entity validator](https://www.drupal.org/project/entity_validator): Integrate
with a robust entity validation

## Credits

* [Gizra](http://gizra.com)
* [Mateu Aguiló Bosch](https://github.com/mateu-aguilo-bosch)
