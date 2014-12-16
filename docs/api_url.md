# Consuming your API

## Returning specific fields
Using the ``?fields`` query string, you can declare which fields should be
returned.  Note that you can only return fields already being returned by
`publicFieldsInfo()`.  This is used, for example, if you have many fields
in `publicFieldsInfo()`, but your client only needs a few specific ones.

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


## Applying a query filter
RESTful allows applying filters to the database query used to generate the list.

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
    // Account cache granularity. Instead of caching per user you can choose to cache per role. Default: DRUPAL_CACHE_PER_USER.
    'granularity' => DRUPAL_CACHE_PER_ROLE,
  ),
);
```

Additionally you can define a cache backend for a given cache bin by setting the
 variable `cache_class_<cache-bin-name>` to the class to be used. This way all
the resouces caching to that particular bin will use that cache backend instead
of the default one.


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

## Error handling
If an error occurs when operating the REST endpoint via URL, A valid JSON object
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


## Documenting your API
It is of most importance to document your API, this is why the RESTful module
provides a way to comprehensively document your resources and endpoints. This
documentation can be accessed through the HTTP methods and through extending
modules. The API will be documented both for humans and for machine consumption,
allowing client implementations to know about the API without explicit
programming.
