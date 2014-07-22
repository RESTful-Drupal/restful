'use strict';

angular.module('restfulApp')
  .controller('MainCtrl', function($scope, DrupalSettings, ArticlesResource, FileUpload, $http, $log) {
    $scope.data = DrupalSettings.getData('article');
    $scope.serverSide = {};

    /**
     * Get matching tags.
     *
     * @param query
     *   The query string.
     */
    $scope.tagsQuery = function (query) {
      var url = DrupalSettings.getBasePath() + 'taxonomy/autocomplete/field_tags/' + query.term;
      $log.log(url);

      $http.get(url).success(function(data) {
        var terms = {results: []};

        angular.forEach(data, function (term, index) {
          terms.results.push({
            text: term,
            id: index
          });
        });
        query.callback(terms);
      });
    };

    /**
     * Create a new term.
     *
     * @param term
     *
     * @returns {{text: *, id: number}}
     */
    $scope.createSearchChoice = function (term) {
      return {
        text: term,
        id: -10
      };
    };

    /**
     * Submit form (even if not valildated via client).
     */
    $scope.submitForm = function(){
      ArticlesResource.createArticle($scope.data)
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
      //$files: an array of files selected, each file has name, size, and type.
      for (var i = 0; i < $files.length; i++) {
        var file = $files[i];
        FileUpload.upload(file).then(function(data) {
          $scope.data.image = data.data.list[0].id;
          $scope.serverSide.image = data.data.list[0];
        });
      }
    };
  });
