'use strict';

angular.module('restfulApp')
  .controller('MainCtrl', function($scope, DrupalSettings, ArticlesResource, FileUpload, $http, $log) {
    $scope.data = DrupalSettings.getData('article');
    $scope.data.label = 'yes',
    $scope.data.body = 'Drupal stuff',
    $scope.serverSide = {};

    /**
     * Get matching tags.
     *
     * @param query
     *   The query string.
     */
    $scope.tagsQuery = function (query) {
      var url = DrupalSettings.getBasePath() + 'api/v1/tags';

      $http.get(url, {
        params: {
          string: query.term
        }
      }).success(function(data) {
        var terms = {results: []};

        if (data.length == 0) {
          terms.results.push({
            text: query.term,
            id: query.term
          });
        }
        else {
          angular.forEach(data, function (label, id) {
            terms.results.push({
              text: label,
              id: id
            });
          });
        }

        query.callback(terms);
      });
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
        tags[index] = {};
        tags[index].label = term.text;
        if (term.id === parseInt(term.id)) {
          tags[index].id = term.id;
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
