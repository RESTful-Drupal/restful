[![Build Status](https://travis-ci.org/Gizra/restful.png?branch=7.x-1.x)](https://travis-ci.org/Gizra/restful)

# RESTful best practices for Drupal

This module follows [this post](http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api) to achieve a _practical_ RESTful for Drupal.
The aim of the module, is to allow exposing an API, without Drupal's data structure leaking to it.

## Difference between other modules (e.g. RestWs and Services Entity)

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


## API overview
Assuming you have enabled the REstful example module

Create a new Article (POST method)

```php
$handler = restful_get_restful_handler('articles');
$handler->post('', array('label' => 'example title'));
```

View an Article (GET method)

```php
$handler = restful_get_restful_handler('articles');
$result = $handler->get(1);

array(
  'id' => 1,
  'label' => 'example title',
  'self' => 'http://example.com/node/1',
);

```

## Credits

Developed by [Gizra](http://gizra.com)
