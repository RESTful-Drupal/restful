# Consuming your API

The RESTful module allows your resources to be used by external clients via
HTTP requests.  This is the module's primary purpose.

You can manipulate the resources using different HTTP request types
(e.g. `POST`, `GET`, `DELETE`), HTTP headers, and special query strings
passed in the URL itself.


## Getting information about the resource


### Exploring the resource

Using a HTTP `GET` request on a resource's root URL will return information
about that resource, in addition to the data itself.

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
resource.  To do so, make an OPTIONS request to the resource's root URL.

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


## Working with authentication providers
Restful comes with ``cookie``, ``base_auth`` (user name and password in the HTTP
header) authentications providers, as well as a "RESTful token auth" module that
 has a `token` authentication provider.

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
