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
      $http.get('http://ws.spotify.com/search/1/track.json', {
        params: {
          q: query.term
        }
      }).success(function(data) {
        var tags = {results: []};

        angular.forEach(data.data.tracks, function (tag) {
          tags.results.push({
            text: tag.label,
            id: tag.id
          });
        });
        query.callback(tags);
      });
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
