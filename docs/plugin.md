# Defining a RESTful Plugin

## View an entity
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


## View an entity using a view mode
You can leverage Drupal core's view modes to render an entity and expose it as a
resource with RESTful. All you need is to set up a view mode that renders the
output you want to expose and tell RESTful to use it. This simplifies the
workflow of exposing your resource a lot, since you don't even need to create a
resource class, but it also offers you less features that are configured in the
`publicFieldsInfo` method.

Use this method when you don't need any of the extra features that are added via
`publicFieldsInfo` (like the discovery metadata, image styles for images,
process callbacks, custom access callbacks for properties, etc.). This is also a
good way to stub a resource really quick and then move to the more fine grained
method.

To use this method just set it in the plugin definition file, as demonstrated in
[this example](https://github.com/Gizra/restful/blob/7.x-1.x/modules/restful_example/plugins/restful/node/articles/1.7/articles__1_7.inc#L3-L23).


## Filtering fields
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


## Disabling sort capability
The sort parameter can be disabled in your resource plugin definition:

```php
$plugin = array(
  ...
  'url_params' => array(
    'sort' => FALSE,
  ),
);


## Setting the default range
The range can be specified by setting $this->range in your plugin definition.


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


## Documenting your fields
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
      // The size of the form element (if applies). Defaults to: NULL.
      'size' => 255,
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


### Auto-documented fields
If your resource is an entity then some of this information will be populated
for you out of the box, without you needing to do anything else. This
information will be derived from the Entity API and Field API. The following
will be populated automatically:

  - `$discovery_info['info']['label']`.
  - `$discovery_info['info']['description']`.
  - `$discovery_info['data']['type']`.
  - `$discovery_info['data']['required']`.
  - `$discovery_info['form_element']['default_value']`.
  - `$discovery_info['form_element']['allowed_values']` for text lists.
