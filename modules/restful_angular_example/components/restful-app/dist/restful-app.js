/**
 * restful-app
 * @version v0.0.1 - 2015-10-29
 * @link 
 * @author  <>
 * @license MIT License, http://www.opensource.org/licenses/MIT
 */
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

'use strict';

angular.module('restfulApp')
  .controller('AdminCtrl', function($log) {
    $log.info('ng-admin loaded');
  }
);

'use strict';

angular.module('restfulApp')
  .controller('FormCtrl', function($scope, DrupalSettings, ArticlesResource, FileUpload, $http, $log) {
    $scope.data = DrupalSettings.getData('article');
    $scope.data.label = 'yes';
    $scope.data.body = 'Drupal stuff';
    $scope.serverSide = {};
    $scope.tagsQueryCache = [];

    /**
     * Get matching tags.
     *
     * @param query
     *   The query string.
     */
    $scope.tagsQuery = function (query) {
      if (query && query.length > 1) {
        var url = DrupalSettings.getBasePath() + 'api/v1/tags';
        var terms = {results: []};

        var lowerCaseTerm = query.toLowerCase();
        if (angular.isDefined($scope.tagsQueryCache[lowerCaseTerm])) {
          // Add caching.
          terms = $scope.tagsQueryCache[lowerCaseTerm];
          console.log(terms.results, 'CACHED');
          $scope.tagsChoices = terms.results;
          return;
        }

        $http.get(url, {
          params: {
            string: query
          }
        }).success(function(data) {

          if (data.count === 0) {
            terms.results.push({
              text: query,
              id: query,
              isNew: true
            });
          }
          else {
            angular.forEach(data.data, function (object) {
              terms.results.push({
                text: object.label,
                id: object.id,
                isNew: false
              });
            });
            $scope.tagsQueryCache[lowerCaseTerm] = terms;
          }
        $scope.tagsChoices = terms.results;
        });
      }
      else {
        $scope.tagsChoices = [];
      }
    };

    /**
     * Submit form (even if not validated via client).
     */
    $scope.submitForm = function() {
      // Prepare the tags, by removing the IDs that are not integer, so it will
      // use POST to create them.
      var submitData = angular.copy($scope.data);
      var tags = [];
      angular.forEach(submitData.tags, function (term, index) {
        if (term.isNew) {
          // New term.
          tags[index] = {};
          tags[index].label = term.id;
        }
        else {
          // Existing term.
          tags[index] = term.id;
        }
      });

      submitData.tags = tags;
      $log.log(submitData);

      ArticlesResource.createArticle(submitData)
        .success(function(data, status, headers, config) {
          $scope.serverSide.data = data;
          $scope.serverSide.status = status;
        })
        .error(function(data, status, headers, config) {
          $scope.serverSide.data = data;
          $scope.serverSide.status = status;
        })
      ;
    };

    $scope.onFileSelect = function($files) {
      var updateFileProperties = function(data) {
        $scope.data.image = data.data.data[0].id;
        $scope.serverSide.image = data.data.data[0];
      };
      //$files: an array of files selected, each file has name, size, and type.
      for (var i = 0; i < $files.length; i++) {
        var file = $files[i];
        FileUpload.upload(file).then(updateFileProperties);
      }
    };
  });

'use strict';

angular.module('restfulApp')
  .service('ArticlesResource', function(DrupalSettings, $http, $log) {

    /**
     * Create a new article.
     *
     * @param data
     *   The data object to POST.
     *
     * @returns {*}
     *   JSON of the newley created article.
     */
    this.createArticle = function(data) {
      var config = {
        withCredentials: true,
        headers: {
          'X-CSRF-Token': DrupalSettings.getCsrfToken()
          // Call the correct resource version (v1.5) that has the "body" and
          // "image" fields exposed.
        }
      };

      return $http.post(DrupalSettings.getBasePath() + 'api/v1.5/articles', data, config);
    };
  });

'use strict';

angular.module('restfulApp')
  .service('DrupalSettings', function($window) {
    var self = this;

    /**
     * Wraps inside AngularJs Drupal settings global object.
     *
     * @type {Drupal.settings}
     */
    this.settings = $window.Drupal.settings;


    /**
     * Get the base path of the Drupal installation.
     */
    this.getBasePath = function() {
      return (angular.isDefined(self.settings.restfulExample.basePath)) ? self.settings.restfulExample.basePath : undefined;
    };

    /**
     * Get the base path of the Drupal installation.
     */
    this.getCsrfToken = function() {
      return (angular.isDefined(self.settings.restfulExample.csrfToken)) ? self.settings.restfulExample.csrfToken : undefined;
    };

    /**
     * Return the form schema.
     *
     * @param int id
     *   The form ID.
     *
     * @returns {*}
     *   The form schema if exists, or an empty object.
     */
    this.getData = function(id) {
      return (angular.isDefined(self.settings.restfulExample.data[id])) ? self.settings.restfulExample.data[id] : {};
    };
  });

'use strict';

angular.module('restfulApp')
  .service('FileUpload', function(DrupalSettings, $upload, $log) {

    /**
     * Upload file.
     *
     * @param file
     *   The file to upload.
     *
     * @returns {*}
     *   The uplaoded file JSON.
     */
    this.upload = function(file) {
      return $upload.upload({
        url: DrupalSettings.getBasePath() + 'api/file-upload',
        method: 'POST',
        file: file,
        withCredentials:  true,
        headers: {
          'X-CSRF-Token': DrupalSettings.getCsrfToken()
        }
      });
    };

  });
