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
