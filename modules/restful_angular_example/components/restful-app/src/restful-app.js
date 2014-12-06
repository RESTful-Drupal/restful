/*global Drupal,restfulExampleModules*/
'use strict';

var app = angular.module('restfulApp', restfulExampleModules, function($httpProvider) {

    // Use x-www-form-urlencoded Content-Type
    $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
    $httpProvider.defaults.headers.common['X-API-Version'] = 'v1.5';

    /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */
    var param = function(obj) {
      var query = '', name, value, fullSubName, subName, subValue, innerObj, i;

      for(name in obj) {
        value = obj[name];

        if(value instanceof Array) {
          for(i=0; i<value.length; ++i) {
            subValue = value[i];
            fullSubName = name + '[' + i + ']';
            innerObj = {};
            innerObj[fullSubName] = subValue;
            query += param(innerObj) + '&';
          }
        }
        else if(value instanceof Object) {
          for(subName in value) {
            subValue = value[subName];
            fullSubName = name + '[' + subName + ']';
            innerObj = {};
            innerObj[fullSubName] = subValue;
            query += param(innerObj) + '&';
          }
        }
        else if(value !== undefined && value !== null)
          query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
      }

      return query.length ? query.substr(0, query.length - 1) : query;
    };

    // Override $http service's default transformRequest
    $httpProvider.defaults.transformRequest = [function(data) {
      var result = angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
      return result;
    }];
  });

if (restfulExampleModules.indexOf('ng-admin') !== -1) {
  // This configuration is specific for ng-admin. Do not add it for other
  // examples.
  app.config(function (NgAdminConfigurationProvider, Application, Entity, Field, Reference, ReferencedList, ReferenceMany) {
    // set the main API endpoint for this admin
    var app = new Application('RESTful Admin')
      .baseApiUrl(Drupal.settings.restfulExample.apiPath);

    // define an entity mapped by the http://<hostname>/api/articles endpoint
    var article = new Entity('articles');
    app
      .addEntity(article);


    // set the list of fields to map in each  view
    var truncate = function (value, entry) {
      return value + '(' + entry.values.subValue + ')';
    };
    var pagination = function(page, maxPerPage) {
      return {
        begin: (page - 1) * maxPerPage,
        end: page * maxPerPage
      };
    };
    article.dashboardView()
      .title('Recent articles')
      .order(1) // display the article panel first in the dashboard
      .limit(5) // limit the panel to the 5 latest articles
      .pagination(pagination) // use the custom pagination function to format the API request correctly
      .addField(new Field('label').isEditLink(true).map(truncate));

    article.listView()
      .title('All articles') // default title is "List of articles"
      .pagination(pagination)
      .addField(new Field('id').label('ID'))
      .addField(new Field('label'));

    article.creationView()
      .title('Add a new article') // default title is "Create a article"
      .addField(new Field('label')) // the default edit field type is "string", and displays as a text input
      .addField(new Field('body').type('wysiwyg')); // overriding the type allows rich text editing for the body

    article.editionView()
      .addField(new Field('label'))
      .addField(new Field('body').type('wysiwyg')
    );

    NgAdminConfigurationProvider.configure(app);
  });
  app.config(function(RestangularProvider) {
    // Add a response intereceptor.
    RestangularProvider.addResponseInterceptor(function(data, operation, what, url, response, deferred) {
      var extractedData;
      if (operation === 'getList') {
        extractedData = data.data;
      } else {
        extractedData = data.data[0];
      }
      return extractedData;
    });
  });
}
