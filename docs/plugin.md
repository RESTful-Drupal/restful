# Defining a RESTful Plugin

## Resources with multiple bundles
One of the things that your API design should have is entity uniformity. That
means that you should be able to describe the contents of a single item with a
schema. That in turn, refers to the ability to set expectation in the fields that
are going to be present for a given item, by explaining «I'm going to have fields
a, b and c and they are all strings».

If you have a resource for a Drupal entity type (ex: a node) that should output
different bundles (ex: article and page), then you have to make sure that you
only expose fields that are common to the both of them so the resulting payload
is uniform. Another way to expose them, in case that you need to expose different
fields, is to treat them as references. In that case you would have a public
field per bundle that contains a reference to each specific bundle (one for the
page and another one for the article, linked to the page and article resources).
You will need to set the field class manually for that field. See an example of
that in [EntityTests__1_0](../tests/modules/restful_test/src/Plugin/resource/entity_test/EntityTests__1_0.php).

## Defining the exposed fields
By default the RESTful module will expose the ID, label and URL of the entity.
You probably want to expose more than that. To do so you will need to implement
the `publicFields` method defining the names in the output array and how
those are mapped to the queried entity. For instance the following example will
retrieve the basic fields plus the body, tags and images from an article node.
The RESTful module will know to use the `MyArticlesResource` class because your
plugin definition will say so.

```php
class MyArticles__1_0 extends ResourceEntity {

  /**
   * Overrides ResourceEntity::publicFields().
   */
  public function publicFields() {
    $public_fields = parent::publicFields();

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

See [the inline documentation](https://github.com/RESTful-Drupal/restful/blob/7.x-1.x/plugins/restful/RestfulEntityBase.php)
for `publicFields` to get more details on exposing field data to your
resource.

If you need even more flexibility, you can use the `'callback'` key to name a
custom function to compute the field data.


## Defining a view mode
You can leverage Drupal core's view modes to render an entity and expose it as a
resource with RESTful. All you need is to set up a view mode that renders the
output you want to expose and tell RESTful to use it. This simplifies the
workflow of exposing your resource a lot, since you don't even need to create a
resource class, but it also offers you less features that are configured in the
`publicFields` method.

Use this method when you don't need any of the extra features that are added via
`publicFields` (like the discovery metadata, image styles for images,
process callbacks, custom access callbacks for properties, etc.). This is also a
good way to stub a resource really quick and then move to the more fine grained
method.

To use this method, set the `'view_mode'` key in the plugin definition file:

```php
$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles__1_7',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type using view modes.'),
  'class' => 'RestfulEntityBaseNode',
  'authentication_types' => TRUE,
  'authentication_optional' => TRUE,
  'minor_version' => 7,
  // Add the view mode information.
  'view_mode' => array(
    'name' => 'default',
    'field_map' => array(
      'body' => 'body',
      'field_tags' => 'tags',
      'field_image' => 'image',
    ),
  ),
);
```


## Disable filter capability
The filter parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'filter' => FALSE,
  ),
);
```


## Defining a default sort
You can also define default sort fields in your plugin, by overriding
`defaultSortInfo()` in your class definition.

This method should return an associative array, with each element having a key
that matches a field from `publicFields()`, and a value of either 'ASC' or
'DESC'. Bear in mind that for entity based resources, only those fields with a
`'property'` (matching to an entity property or a Field API field) can be used
for sorting.

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


## Disabling sort capability
The sort parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'sort' => FALSE,
  ),
);
```

## Setting the default range
The range can be specified by setting `$this->range` in your plugin definition.


### Disabling the range parameter
The range parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'range' => FALSE,
  ),
);
```


## Image derivatives
Many client side technologies have lots of problems resizing images to serve
them optimized and thus avoiding browser scaling. For that reason the RESTful
module will let you specify an array of image style names to get an array of
image derivatives for your image fields. Just add an `'image_styles'` key in
your public field info (as shown above) with the list of styles to use and be
done with it.


## Reference fields and properties
It is considered a best practice to map a reference field (i.e. entity
reference or taxonomy term reference) or a reference property (e.g. the ``uid``
property on the node entity) to the resource it belongs to.

```php
public function publicFields() {
  $public_fields = parent::publicFields();
  // ...
  $public_fields['user'] = array(
    'property' => 'author',
    'resource' => array(
      // The bundle of the entity.
      'user' => array(
      // The name of the resource to map to.
      'name' => 'users',
      // Determines if the entire resource should appear, or only the ID.
      'fullView' => TRUE,
    ),
  );
  // ...
  return $public_fields;
}
```

Note that when you use the ``resource`` property, behind the scenes RESTful  
initializes a second handler and calls that resource. In order to pass information  
to the second handler (e.g. the access token), we pipe the original request  
array with some parameters removed. If you need to strip further parameters you can
override ``\RestfulBase::getRequestForSubRequest``.

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


### Changing the default output format
If you need to change the output format for everything at once then you just
have to set a special variable with the name of the new output format plugin.
When you do that all the resources that don't specify a `'formatter'` key in the
plugin definition will use that output format by default. Ex:

```php
variable_set('restful_default_output_formatter', 'my_formatter');
```


## Render cache
In addition to providing its own basic caching, the RESTful module is compatible
 with the [Entity Cache](https://drupal.org/project/entitycache) module. Two
 requests made by the same user requesting the same fields on the same entity
 will benefit from the render cache layer. This means that no entity will need
 to be loaded if it was rendered in the past under the same conditions.

Developers have absolute control where the cache is stored and the expiration
for every resource, meaning that very volatile resources can skip cache entirely
while other resources can have its cache in MemCached or the database. To
configure this developers just have to specify the following keys in their
_restful_ plugin definition:

```php
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
    'simpleInvalidate' => TRUE,
    // Use a different cache backend for this resource. Defaults to variable_get('cache_default_class', 'DrupalDatabaseCache') (optional).
    'class' => 'MemCacheDrupal',
    // Account cache granularity. Instead of caching per user you can choose to cache per role. Default: DRUPAL_CACHE_PER_USER.
    'granularity' => DRUPAL_CACHE_PER_ROLE,
  ),
);
```

Additionally you can define a cache backend for a given cache bin by setting the
 variable `cache_class_<cache-bin-name>` to the class to be used. This way all
the resouces caching to that particular bin will use that cache backend instead
of the default one.


## Rate limit
RESTful provides rate limit functionality out of the box. A rate limit is a way
to protect your API service from flooding, basically consisting on checking is
the number of times an event has happened in a given period is greater that the
maximum allowed.


### Rate limit events
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


### Configuring your rate limits
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
        'administrator' => \Drupal\restful\RateLimit\RateLimitManager::UNLIMITED_RATE_LIMIT,
      ),
    ),
  ),
…
```

As you can see in the example you can set the rate limit differently depending
on the role of the visiting user.

Since the global event is not tied to any resource the limit and period is
specified by setting the following variables:
  - `restful_global_rate_limit`: The number of allowed hits. This is global for
    all roles.
  - `restful_global_rate_period`: The period string compatible with
    \DateInterval.



## Documenting your resources.
A resource can be documented in the plugin definition using the `'label'`
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
