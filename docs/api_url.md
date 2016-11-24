# Consuming your API

The RESTful module allows your resources to be used by external clients via
HTTP requests.  This is the module's primary purpose.

You can manipulate the resources using different HTTP request types
(e.g. `POST`, `GET`, `DELETE`), HTTP headers, and special query strings
passed in the URL itself.

## Write operations
Write operations can be performed via the `POST` (to create items), `PUT` or `PATCH`
(to update items) HTTP methods.

### Basic example
The following request will create an article using the `articles` resource:

```http
POST /articles HTTP/1.1
Content-Type: application/json
Accept: application/json

{
  "title": "My article",
  "body": "<p>This is a short one</p>",
  "tags": [1, 6, 12]
}
```

Note how we are setting the properties that we want to set using JSON. The
provided payload format needs to match the contents of the `Content-Type` header
(in this case _application/json_).

It's also worth noting that when setting reference fields with multiple values,
you can submit an array of IDs or a string of IDs separated by commas.

### Advanced example
You use sub-requests to manipulate (create or alter) the relationships in a single request. The following example will:

  1. Update the title of the article to be _To TDD or Not_.
  1. Update the contents of tag 6 to replace it with the provided content.
  1. Create a new tag and assign it to the updated article.

```
PATCH /articles/1 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "title": "To TDD or Not",
  "tags": [
    {
      "id": "6",
      "body": {
        "label": "Batman!",
        "description": "The gadget owner."
      },
      "request": {
        "method": "PATCH"
      }
    },
    {
      "body": {
        "label": "everything",
        "description": "I can only say: 42."
      },
      "request": {
        "method": "POST",
        "headers": {"Authorization": "Basic Yoasdkk1="}
      }
    }
  ]
}
```

See the
[extension specification](https://gist.github.com/e0ipso/cc95bfce66a5d489bb8a)
for an example using JSON API.

## Getting information about the resource

### Exploring the resource

Using a HTTP `GET` request on a resource's root URL will return information
about that resource, in addition to the data itself.

``` shell
curl https://example.com/api/
```
This will output all the available **latest** resources (of course, if you have
enabled the "Discovery Resource" option). For example, if there are 3 different
API version plugins for content type Article (1.0, 1.1, 2.0) it will display the
latest only (2.0 in this case).

If you want to display all the versions of all the resources declared, then add the
query **?all=true** like this.

``` shell
curl https://example.com/api?all=true
```

The data results are stored in the `data` property of the JSON response, while
the `self` and `next` objects contain information about the resource.

```javascript
{
  "data": [
    {
      "self": "https://example.com/api/v1.0/articles/123",
      "field": "A field value",
      "field2": "Another field value"
    },
    // { ... more results follow ... }
  ],
  "count": 100,
  "self": {
    "title": "Self",
    "href": "https://example.com/api/v1.0/articles"
  },
  "next": {
    "title": "Next",
    "href": "https://example.com/api/v1.0/articles?page=2"
  }
}
```


### Returning documentation about the resource

Using an HTTP `OPTIONS` request, you can return documentation about the
resource.  To do so, make an `OPTIONS` request to the resource's root URL.

```shell
curl -X OPTIONS -i https://example.com/api/v1.0/articles
```

The resource will respond with a JSON object that contains documentation for
each field defined by the resource.

See the _Documenting your API_ section of the [README file](../README.md)
for examples of the types of information returned by such a request.


## Returning specific fields
Using the ``?fields`` query string, you can declare which fields should be
returned.  Note that you can only return fields already being returned by
`publicFields()`.  This is used, for example, if you have many fields
in `publicFields()`, but your client only needs a few specific ones.

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

Bear in mind that for entity based resources, only those fields with a
`'property'` (matching to an entity property or a Field API field) can be used
for filtering.

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

Additionally you can provide multiple filters for the same field. That is
especially useful when filtering on multiple value fields. The following example
will get all the articles with the `integer_multiple` field that contains all 1, 3
and 5.

```
curl https://example.com/api/articles?filter[integer_multiple][value][0]=1&filter[integer_multiple][value][1]=3&filter[integer_multiple][value][2]=5
```

You can do more advanced filtering by providing values and operators. The
following example will get all the articles with an `integer_multiple` value less than 5
and another equal to 10.

```
curl https://example.com/api/articles?filter[integer_multiple][value][0]=5&filter[integer_multiple][value][1]=10&filter[integer_multiple][operator][0]=">"&filter[integer_multiple][operator][1]="="
```

## Loading by an alternate ID.
Sometimes you need to load an entity by an alternate ID that is not the regular
entity ID, for example a unique ID title. All that you need to do is provide the
alternate ID as the regular resource ID and inform that the passed in ID is not
the regular entity ID but a different field. To do so use the `loadByFieldName`
query parameter.

```
curl -H 'X-API-version: v1.5' https://www.example.org/articles/1234-abcd-5678-efg0?loadByFieldName=uuid
```

That will load the article node and output it as usual. Since every REST
resource object has a canonical URL (and we are using a different one) a _Link_
header will be added to the response with the canonical URL so the consumer can
use it in future requests.

```
HTTP/1.1 200 OK
Date: Mon, 22 Dec 2014 08:08:53 GMT
Content-Type: application/hal+json; charset=utf-8
...
Link: https://www.example.org/articles/12; rel="canonical"

{
  ...
}
```

The only requirement to use this feature is that the value for your
`loadByFieldName` field needs to be one of your exposed fields. It is also up to
you to make sure that that field is unique. Note that in case that more than one
entity matches the provided ID, the first record will be loaded.

## Working with authentication providers
RESTful comes with ``cookie``, ``base_auth`` (user name and password in the HTTP
header) authentications providers, as well as a "RESTful token auth" module that
 has a `token` authentication provider.

Note: if you use cookie-based authentication then you also need to set the
HTTP ``X-CSRF-Token`` header on all writing requests (`POST`, `PUT` and `DELETE`).
You can retrieve the token from ``/api/session/token`` with a standard HTTP
`GET` request.

See [this](https://github.com/Gizra/angular-restful-auth) AngularJs example that
shows a login from a fully decoupled web app to a Drupal backend.

Note: If you use basic auth under `.htaccess` password you might hit a flood
exception, as the server is sending the `.htaccess` user name and password as the
authentication. In such a case you may set the ``restful_skip_basic_auth`` to
TRUE, in order to avoid using it. This will allow enabling and disabling the
basic auth on different environments.

```bash
# (Change username and password)
curl -u "username:password" https://example.com/api/login-token

# Response has access token.
{"access_token":"YOUR_TOKEN","refresh_token":"OTHER_TOKEN",...}

# Call a "protected" with token resource (Articles resource version 1.3 in "RESTful example")
curl https://example.com/api/v1.3/articles/1?access_token=YOUR_TOKEN

# Or use access-token instead of access_token for ensuring header is not going to be
# dropped out from $_SERVER so it remains compatible with other webservers different than apache.
curl -H "access-token: YOUR_TOKEN" https://example.com/api/v1.3/articles/1
```

## Error handling
If an error occurs when operating the REST endpoint via URL, a valid JSON object
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
