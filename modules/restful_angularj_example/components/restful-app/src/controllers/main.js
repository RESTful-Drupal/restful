'use strict';

angular.module('restfulApp')
  .controller('MainCtrl', function($scope, DrupalSettings, ArticlesResource, $log) {
    var autoFieldsData = DrupalSettings.getAutoFieldsData('article');

    $scope.serverSide = {};

    $scope.schema = autoFieldsData.schema;
    $scope.data = autoFieldsData.data;
    $scope.options = autoFieldsData.options;


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
    }
  });
