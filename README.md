**7.x-1.x** [![Build Status](https://travis-ci.org/RESTful-Drupal/restful.svg?branch=7.x-1.x)](https://travis-ci.org/RESTful-Drupal/restful)

**7.x-2.x** [![Build Status](https://travis-ci.org/RESTful-Drupal/restful.svg?branch=7.x-2.x)](https://travis-ci.org/RESTful-Drupal/restful)

# RESTful best practices for Drupal

This module allows Drupal to be operated via RESTful HTTP requests, using best
practices for security, performance, and usability.


## Concept
These are the differences between RESTful and other modules, such as RestWs and
Services Entity:

* RESTful requires explicitly declaring the exposed API. When enabling
the module, nothing happens until a plugin declares it.
* Resources are exposed by bundle, rather than by entity.  This would allow a
developer to expose only nodes of a certain type, for example.
* The exposed properties need to be explicitly declared. This allows a _clean_
output without Drupal's internal implementation leaking out. This means the
consuming client doesn't need to know if an entity is a node or a term, nor will
 they be presented with the ``field_`` prefix.
* Resource versioning is built-in, so that resources can be reused with multiple
consumers.  The versions are at the resource level, for more flexibility and
control.
* It has configurable output formats. It ships with JSON (the default one), JSON+HAL and as an example also XML.
* Audience is developers and not site builders.
* Provide a key tool for a headless Drupal. See the [AngularJs form](https://github.com/Gizra/restful/blob/7.x-1.x/modules/restful_angular_example/README.md) example module.


## Module dependencies

  * [Entity API](https://drupal.org/project/entity), with the following patch:
  * [Prevent notice in entity_metadata_no_hook_node_access() when node is not saved](https://www.drupal.org/node/2086225#comment-9627407)

## Recipes
Read even more examples on how to use the RESTful module in the [module documentation
node](https://www.drupal.org/node/2380679) in Drupal.org. Make sure you read the _Recipes_
section. If you have any to share, feel free to add your own recipes.

## Declaring a REST Endpoint

A RESTful endpoint is declared via a custom module that includes a plugin which
describes the resource you want to make available.  Here are the bare
essentials from one of the multiple examples in
[the example module](./modules/restful_example):

####restful\_custom/restful\_custom.info
```ini
name = RESTful custom
description = Custom RESTful resource.
core = 7.x
dependencies[] = restful
```

####restful\_custom/restful\_custom.module
```php
/**
 * Implements hook_ctools_plugin_directory().
 */
 function restful_custom_ctools_plugin_directory($module, $plugin) {
   if ($module == 'restful') {
     return 'plugins/' . $plugin;
   }
 }
```

####restful\_custom/plugins/restful/articles.inc
```php
$plugin = array(
  'label' => t('Articles'),
  'resource' => 'articles',
  'name' => 'articles',
  'entity_type' => 'node',
  'bundle' => 'article',
  'description' => t('Export the article content type.'),
  'class' => 'RestfulCustomResource',
);
```
The `resource` key determines the root URL of the resource.  The `name` key must match
the filename of the plugin: in this case, the name is `articles`, and therefore, the
filename is `articles.inc`.

####restful\_custom/plugins/restful/RestfulCustomResource.class.php
```php
class RestfulCustomResource extends RestfulEntityBaseNode {

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

After declaring this plugin, the resource could be accessed at its root URL,
which would be `http://example.com/api/v1.0/articles`.

### Security, caching, output, and customization

See the [Defining a RESTful Plugin](./docs/plugin.md) document for more details.


## Using your API from within Drupal

The following examples use the _articles_ resource from the _restful\_example_
module.

#### Getting the default RESTful handler for a resource

```php
// Get handler v1.0
$handler = restful_get_restful_handler('articles');
```


#### Getting a specific version of a RESTful handler for a resource

```php
// Get handler v1.1
$handler = restful_get_restful_handler('articles', 1, 1);
```


#### Create and update an entity
```php
$handler = restful_get_restful_handler('articles');
// POST method, to create.
$result = $handler->post('', array('label' => 'example title'));
$id = $result['id'];

// PATCH method to update only the title.
$request['label'] = 'new title';
$handler->patch($id, $request);
```


#### List entities
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


### Sort, Filter, Range, and Sub Requests

See the [Using your API within drupal](./docs/api_drupal.md) documentation for
more details.


## Consuming your API

The following examples use the _articles_ resource from the _restful\_example_
module.

#### Consuming specific versions of your API
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


#### View multiple articles at once

```shell
# Handler v1.1
curl https://example.com/api/articles/1,2 \
  -H "X-API-Version: v1.1"
```


#### Returning autocomplete results

```shell
curl https://example.com/api/articles?autocomplete[string]=mystring
```


#### URL Query strings, HTTP headers, and HTTP requests

See the [Consuming Your API](./docs/api_url.md) document for more details.


## Documenting your API

Clients can access documentation about a resource by making an `OPTIONS` HTTP
request to its root URL. The resource will respond with the field information
in the body, and the information about the available output formats and the
permitted HTTP methods will be contained in the headers.


### Automatic documentation

If your resource is an entity, then it will be partially self-documented,
without you needing to do anything else. This information is automatically
derived from the Entity API and Field API.

Here is a snippet from a typical JSON response using only the automatic
documentation:

```javascript
{
  "myfield": {
    "info": {
      "label": "My Field",
      "description": "A field within my resource."
    },
    "data": {
      "type": "string",
      "read_only": false,
      "cardinality": 1,
      "required": false
    },
    "form_element": {
      "type": "textfield",
      "default_value": "",
      "placeholder": "",
      "size": 255,
      "allowed_values": null
    }
  }
  // { ... other fields would follow ... }
}
```

Each field you've defined in `publicFieldsInfo` will output an object similar
to the one listed above.


### Manual documentation
In addition to the automatic documentation provided to you out of the box, you
have the ability to manually document your resources.  See the [Documenting your API](./docs/documentation.md)
documentation for more details.


## Modules integration
* [Entity validator](https://www.drupal.org/project/entity_validator): Integrate
with a robust entity validation


## Credits

* [Gizra](http://gizra.com)
* [Mateu Aguiló Bosch](https://github.com/mateu-aguilo-bosch)
