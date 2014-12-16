[![Build Status](https://travis-ci.org/RESTful-Drupal/restful.svg?branch=7.x-1.x)](https://travis-ci.org/RESTful-Drupal/restful)

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


## Declaring a REST Endpoint

A RESTful endpoint is declared via a custom module that includes a plugin which
describes the resource you want to make available.  Here are the bare
essentials from [the example module](./modules/restful_example):

restful_example/restful_example.info
```ini
name = RESTful example
description = Example module for the RESTful module.
core = 7.x
dependencies[] = restful
```

restful_example/restful_example.module
```php
/**
 * Implements hook_ctools_plugin_directory().
 */
 function restful_example_ctools_plugin_directory($module, $plugin) {
   if ($module == 'restful') {
     return 'plugins/' . $plugin;
   }
 }
```

restful_example/plugins/restful/myplugin.inc
```php
$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulExampleArticlesResource',
);
```

restful_example/plugins/restful/RestfulExampleArticlesResource.class.php
```php
class RestfulExampleArticlesResource extends RestfulEntityBaseNode {

  /**
   * Overrides RestfulEntityBaseNode::publicFieldsInfo().
   */
  public function publicFieldsInfo() {
    $public_fields = parent::publicFieldsInfo();

    $public_fields['body'] = array(
      'property' => 'body',
      'sub_property' => 'value',
    );

    return $public_fields;
  }
}
```

### Declaring image formats, references, view modes, and others

See the [Defining a RESTful Plugin](./docs/plugin.md) document for more details.


## Using your API from within Drupal

The following examples use the _articles_ resource from the example module.

### Getting the default RESTful handler for a resource

```php
// Get handler v1.0
$handler = restful_get_restful_handler('articles');
```


### Getting a specific version of a RESTful handler for a resource

```php
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


### Sort, Filter, Range, Sub Requests

See the [Using Your API Within Drupal](./docs/api_drupal.md) documentation for more
 details.


## Consuming your API

The following examples use the _articles_ resource from the example module.

### Consuming specific versions of your API
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


### Returning autocomplete results

```shell
curl https://example.com/api/articles?autocomplete[string]=mystring
```


### Security, Caching, and Output

See the [Consuming Your API](./docs/api_url.md) document for more details.
Also, there are quite a few URL parameters covered in that document.


## Modules integration
* [Entity validator](https://www.drupal.org/project/entity_validator): Integrate
with a robust entity validation


## Credits

* [Gizra](http://gizra.com)
* [Mateu Aguiló Bosch](https://github.com/mateu-aguilo-bosch)
