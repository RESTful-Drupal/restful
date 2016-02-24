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

``` shell
curl https://example.com/api/
```
This will output all the available **latest** resources (of course, if you have enabled the "Discovery Resource" option). For example, if there are 3 different api version plugins for content type Article (1.0, 1.1, 2.0) it will display the latest only (2.0 in this case).

If you want to display all the versions of all the resources declared add the query **?all=true** like this.

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
specially useful when filtering on multiple value fields. The following example
will get all the articles with the integer multiple field that contains all 1, 3
and 5.

```
curl https://example.com/api/articles?filter[integer_multiple][value][0]=1&filter[integer_multiple][value][1]=3&filter[integer_multiple][value][2]=5
```

You can do more advanced filtering by providing values and operators. The
following example will get all the articles with an integer value less than 5
and another equal to 10.

```
curl https://example.com/api/articles?filter[integer_multiple][value][0]=5&filter[integer_multiple][value][1]=10&filter[integer_multiple][operator][0]=">"&filter[integer_multiple][operator][0]="="
```

## Applying a query sort
RESTful allows specifying of a sort property to the database query used to generate the list.

```php
# Handler v1.0
curl https://example.com/api/v1/articles?sort=label
```

The sort order will default to ascending, however it can be set to descending by prepending a minus (-) sign the sort parameter value.

```shell
# Handler v1.0
curl https://example.com/api/v1/articles?sort=-label
```

## Loading by an alternate ID.
Some times you need to load an entity by an alternate ID that is not the regular
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
you to make sure that that field is unique. Note that in case that more tha one
entity matches the provided ID the first record will be loaded.

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

Note: If you use basic auth under .htaccess password you might hit a flood exception, as the server is sending the .htaccess user name and password
 as the authentication. In such a case you may set the ``restful_skip_basic_auth`` to TRUE, in order to avoid using it. This will allow
 enabling and disabling the basic auth on different environments.

```bash
# (Change username and password)
curl -u "username:password" https://example.com/api/login

# Response has access token.
{"access_token":"YOUR_TOKEN"}

# Call a "protected" with token resource (Articles resource version 1.3 in "Restful example")
curl https://example.com/api/v1.3/articles/1?access_token=YOUR_TOKEN

# Or use access-token instead of access_token for ensuring header is not going to be
# dropped out from $_SERVER so it remains compatible with other webservers different than apache.
curl -H "access-token: YOUR_TOKEN" https://example.com/api/v1.3/articles/1
```

## Change request formatter

By default Restful module allows for any **Content-type** requests by setting the ```Accept: */*```. This means that you can make requests in the format you want (of course if this format is available on the restful plugins). 

For example let's say that we want to get a request in ```xml``` while by default we get the requests in ```hal+json```. All we have to do is set a Header parameter like this ```Accept: application/xml```.

```shell
curl -H 'Accept:application/xml' https://example.com/api/articles
```

And will return data in xml formatter.

```xml
<?xml version="1.0"?>
<api>
  <articles>
    <item0>
      <id>1</id>
      <label>Article title</label>
      <_links>
          <self>
            <href>http://example.com/api/articles/1</href>
          </self>
        </_links>
    </item0>
    <item1>
      <id>2</id>
      <label>Article title</label>
      <_links>
          <self>
            <href>http://example.com/api/articles/2</href>
          </self>
        </_links>
    </item1>
    <count>3</count>
    <_links>
      <self>
        <title>Self</title>
        <href>http://example.com/api/articles</href>
      </self>
    </_links>
</api>

```


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
