# Documenting your API

## Documenting your fields
When declaring a public field and its mappings, you can also provide information
about the field itself. This includes basic information about the field,
information about the data the field holds, and information about the form
element to generate on the client side for this field.

By declaring this information, you make it possible for clients to provide
form elements for your API using reusable form components.

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
      // A custom piece of information we want to add to the documentation.
      'custom' => t('This is custom documentation'),
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

Note the `'custom'` key; you can add your own information to the `'discovery'`
property and it will be exposed as well.

Here is a snippet from the JSON response to an HTTP OPTIONS request made to the
above resource:

```json
"text_multiple": {
  "info": {
    "label": "",
    "description": "This field holds different text inputs.",
    "name": "Text multiple",
    "custom": "This is custom documentation"
  },
  "data": {
    "type": "string",
    "read_only": false,
    "cardinality": -1,
    "required": false
  },
  "form_element": {
    "type": "textfield",
    "default_value": "",
    "placeholder": "This is helpful.",
    "size": 255,
    "allowed_values": null
  }
},
```
