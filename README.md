[![Build Status](https://travis-ci.org/Gizra/restful.svg?branch=7.x-1.x)](https://travis-ci.org/Gizra/restful)

# RESTful best practices for Drupal

This module achieves a practical RESTful for Drupal following best practices.

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
Using the ``?fields`` query string, you can declare which fields should be
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

The sort parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'sort' => FALSE,
  ),
);

You can also define default sort fields in your plugin, by overriding
`defaultSortInfo()` in your class definition.

This method should return an associative array, with each element having a key
that matches a field from `publicFieldsInfo()`, and a value of either 'ASC' or 'DESC'.

This default sort will be ignored if the request URL contains a sort query.

```php
class MyPlugin extends \RestfulEntityBaseTaxonomyTerm {
  /**
   * Overrides \RestfulEntityBase::defaultSortInfo().
   */
  public function defaultSortInfo() {
    // Sort by 'id' in descending order.
    return array('id' => 'DESC');
  }
}
```

### Filter
RESTful allows filtering of a list.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['filter'] = array('label' => 'abc');
$result = $handler->get('', $request);
```

The filter parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'filter' => FALSE,
  ),
);
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

### Range
RESTful allows you to cotrol the number of elements per page you want to show. This value will always be limited by the `$range` variable in your resource class. This variable, in turn, defaults to 50.

```php
$handler = restful_get_restful_handler('articles');
// Single value property.
$request['range'] = 25;
$result = $handler->get('', $request);
```

The range parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'range' => FALSE,
  ),
);
```

## API via URL

### View an Article

```shell
# Handler v1.0
curl https://example.com/api/articles/1 \
  -H "X-API-Version: v1.0"
# or
curl https://example.com/api/v1.0/articles/1

# Handler v1.1
curl https://example.com/api/articles/1 \
  -H "X-API-Version: v1.1"
# or
curl https://example.com/api/v1.1/articles/1
```

### View multiple Articles at once

```shell
# Handler v1.1
curl https://example.com/api/articles/1,2 \
  -H "X-API-Version: v1.1"
```

### Filtering fields
Using the ``?fields`` query string, you can declare which fields should be
returned.

```shell
# Handler v1.0
curl https://example.com/api/v1/articles/2?fields=id
```

Returns:

```javascript
{
  "data": [{
    "id": "2",
    "label": "Foo"
  }]
}
```

### Filter
RESTful allows filtering of a list.

```php
# Handler v1.0
curl https://example.com/api/v1/articles?filter[label]=abc
```

You can even filter results using basic operators. For instance to get all the
articles after a certain date:

```shell
# Handler v1.0
curl https://example.com/api/articles?filter[created][value]=1417591992&filter[created][operator]=">="
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
curl https://example.com/api/v1.3/articles/1?access_token=YOUR_TOKEN
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

## Reference fields and properties

It is considered a best practice to map a reference field (i.e. entity
reference or taxonomy term reference) or a reference property (e.g. the ``uid``
property on the node entity) to the resource it belongs to.


```php
public function publicFieldsInfo() {
  $public_fields = parent::publicFieldsInfo();
  // ...
  $public_fields['user'] = array(
    'property' => 'author',
    'resource' => array(
      // The bundle of the entity.
      'user' => array(
      // The name of the resource to map to.
      'name' => 'users',
      // Determines if the entire resource should appear, or only the ID.
      'full_view' => TRUE,
    ),
  );
  // ...
  return $public_fields;
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

## Render Cache.
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
_restful_ plugin definition:

```php
<?php

$plugin = array(
  ...
  'render_cache' => array(
    // Enables the render cache.
    'render' => TRUE,
    // Defaults to 'cache_restful' (optional).
    'bin' => 'cache_bin_name',
    // Expiration logic. Defaults to CACHE_PERMANENT (optional).
    'expire' => CACHE_TEMPORARY,
    // Enable cache invalidation for entity based resources. Defaults to TRUE (optional).
    'simple_invalidate' => TRUE,
    // Use a different cache backend for this resource. Defaults to variable_get('cache_default_class', 'DrupalDatabaseCache') (optional).
    'class' => 'MemCacheDrupal',
  ),
);
```

Additionally you can define a cache backend for a given cache bin by setting the variable `cache_class_<cache-bin-name>` to the class to be used. This way all the resouces caching to that particular bin will use that cache backend instead of the default one.

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

## Documenting your API
It is of most importance to document your API, this is why the RESTful module
provides a way to comprehensively document your resources and endpoints. This
documentation can be accessed through the HTTP methods and through extending
modules. The API will be documented both for humans and for machine consumption,
allowing client implementations to know about the API without explicit
programming.

### Documenting your resources.
A resource can will be documented in the plugin definition using the `'label'`
and `'description'` keys:

```php
$plugin = array(
  // This is the human readable name of the resource.
  'label' => t('User'),
  // Use de description to provide more extended information about the resource.
  'description' => t('Export the "User" entity.'),
  'resource' => 'users',
  'class' => 'RestfulEntityBaseUser',
  ...
);
```

This should not include any information about the endpoints or the allowed HTTP
methods on them, since those will be accessed directly on the aforementioned
endpoint. This information aims to describe what the accessed resource
represents.

To access this information just use the `discovery` resource at the api
homepage:

```shell
# List resources
curl -u user:password https://example.org/api
```

### Documenting your fields.
When declaring your public field and their mappings you will have the
opportunity to also provide information about the field itself. This includes
basic information about the field, information about the data the field holds
and about how to generate a form element in the client side for this particular
field. By declaring this information a client can write an implementation that
reads this information and provide form elements _for free_ via reusable form
components.

```php
$public_fields['text_multiple'] = array(
  'property' => 'text_multiple',
  'discovery' => array(
    // Basic information about the field for human consumption.
    'info' => array(
      // The name of the field. Defaults to: ''.
      'name' => t('Text multiple'),
      // The description of the field. Defaults to: ''.
      'description' => t('This field holds different text inputs.'),
    ),
    // Information about the data that the field holds. Typically used to help the client to manage the data appropriately.
    'data' => array(
      // The type of data. For instance: 'int', 'string', 'boolean', 'object', 'array', ... Defaults to: NULL.
      'type' => 'string',
      // The number of elements that this field can contain. Defaults to: 1.
      'cardinality' => FIELD_CARDINALITY_UNLIMITED,
      // Avoid updating/setting this field. Typically used in fields representing the ID for the resource. Defaults to: FALSE.
      'read_only' => FALSE,
    ),
    'form_element' => array(
      // The type of the input element as in Form API. Defaults to: NULL.
      'type' => 'textfield',
      // The default value for the form element. Defaults to: ''.
      'default_value' => '',
      // The placeholder text for the form element. Defaults to: ''.
      'placeholder' => t('This is helpful.'),
      // The size of the form element (if applies).
      'size' => 255, Defaults to: NULL.
      // The allowed values for form elements with a limited set of options. Defaults to: NULL.
      'allowed_values' => NULL,
    ),
  ),
);
```

This is the default set of information provided by RESTful. You can add your own
information to the `'discovery'` property and it will be exposed as well.

To access the information about an specific endpoint just make an `OPTIONS` call
to it. You will get the field information in the body, the information about the
available output formats and the permitted HTTP methods will be contained in the
corresponding headers.

#### Auto-documented fields.
If your resource is an entity then some of this information will be populated
for you out of the box, without you needing to do anything else. This
information will be derived from the Entity API and Field API. The following
will be populated automatically:

  - `$discovery_info['info']['label']`
  - `$discovery_info['info']['description']`
  - `$discovery_info['data']['type']`
  - `$discovery_info['data']['required']`
  - `$discovery_info['form_element']['default_value']`
  - `$discovery_info['form_element']['allowed_values']` for text lists.

## Modules integration
* [Entity validator](https://www.drupal.org/project/entity_validator): Integrate
with a robust entity validation

## Credits

* [Gizra](http://gizra.com)
* [Mateu Aguiló Bosch](https://github.com/mateu-aguilo-bosch)
