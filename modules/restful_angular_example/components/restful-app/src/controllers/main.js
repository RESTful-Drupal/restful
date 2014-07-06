'use strict';

angular.module('restfulApp')
  .controller('MainCtrl', function($scope, DrupalSettings, ArticlesResource, FileUpload, $log) {
    var autoFieldsData = DrupalSettings.getData('article');

    $scope.serverSide = {};

    $scope.data = autoFieldsData.data;


    /**
     * Submit form (even if not valildated via client).
     */
    $scope.submitForm = function(){
      if(!$scope.article.$valid) {
        $log.info('not valid, but checking server side');
      }
      else {
        $log.info('valid');
      }

      $log.log($scope.data);

      ArticlesResource.createArticle($scope.data)
        .success(function(data, status, headers, config) {
          $scope.serverSide.data = data;
          $scope.serverSide.status = status;
          $log.log($scope.serverSide);
        })
        .error(function(data, status, headers, config) {
          $scope.serverSide.data = data;
          $scope.serverSide.status = status;
          $log.log($scope.serverSide);
        })
      ;

      $log.log();
    };

    $scope.onFileSelect = function($files) {
      //$files: an array of files selected, each file has name, size, and type.
      for (var i = 0; i < $files.length; i++) {
        var file = $files[i];
        FileUpload.upload(file).then(function(data) {
          $scope.file = data.data.list[0].id;
          $scope.serverSide.file = data.data.list[0];
        });
      }
    };

  });
