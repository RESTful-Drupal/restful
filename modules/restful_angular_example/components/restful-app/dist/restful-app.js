/**
 * restful-app
 * @version v0.0.1 - 2014-07-06
 * @link 
 * @author  <>
 * @license MIT License, http://www.opensource.org/licenses/MIT
 */
'use strict';
angular.module('restfulApp', [
  'angularFileUpload',
  'autofields',
  'ngResource'
], function ($httpProvider) {
  // Use x-www-form-urlencoded Content-Type
  $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
  /**
     * The workhorse; converts an object to x-www-form-urlencoded serialization.
     * @param {Object} obj
     * @return {String}
     */
  var param = function (obj) {
    var query = '', name, value, fullSubName, subName, subValue, innerObj, i;
    for (name in obj) {
      value = obj[name];
      if (value instanceof Array) {
        for (i = 0; i < value.length; ++i) {
          subValue = value[i];
          fullSubName = name + '[' + i + ']';
          innerObj = {};
          innerObj[fullSubName] = subValue;
          query += param(innerObj) + '&';
        }
      } else if (value instanceof Object) {
        for (subName in value) {
          subValue = value[subName];
          fullSubName = name + '[' + subName + ']';
          innerObj = {};
          innerObj[fullSubName] = subValue;
          query += param(innerObj) + '&';
        }
      } else if (value !== undefined && value !== null)
        query += encodeURIComponent(name) + '=' + encodeURIComponent(value) + '&';
    }
    return query.length ? query.substr(0, query.length - 1) : query;
  };
  // Override $http service's default transformRequest
  $httpProvider.defaults.transformRequest = [function (data) {
      var result = angular.isObject(data) && String(data) !== '[object File]' ? param(data) : data;
      console.log(data);
      return result;
    }];
});
'use strict';
angular.module('restfulApp').controller('MainCtrl', [
  '$scope',
  'DrupalSettings',
  'ArticlesResource',
  '$log',
  function ($scope, DrupalSettings, ArticlesResource, $log) {
    var autoFieldsData = DrupalSettings.getAutoFieldsData('article');
    $scope.serverSide = {};
    $scope.schema = autoFieldsData.schema;
    $scope.data = autoFieldsData.data;
    $scope.options = autoFieldsData.options;
    $scope.submitForm = function () {
      if (!$scope.article.$valid) {
        $log.info('not valid, but checking server side');
      } else {
        $log.info('valid');
      }
      $log.log($scope.data);
      ArticlesResource.createArticle($scope.data).success(function (data, status, headers, config) {
        $scope.serverSide.data = data;
        $scope.serverSide.status = status;
        $log.log($scope.serverSide);
      }).error(function (data, status, headers, config) {
        $scope.serverSide.data = data;
        $scope.serverSide.status = status;
        $log.log($scope.serverSide);
      });
      ;
      $log.log();
    };
  }
]);
'use strict';
angular.module('restfulApp').service('ArticlesResource', [
  'DrupalSettings',
  '$http',
  '$log',
  function (DrupalSettings, $http, $log) {
    /**
     * Create a new article.
     *
     * @param data
     *   The data object to POST.
     *
     * @returns {*}
     *   JSON of the newley created article.
     */
    this.createArticle = function (data) {
      var config = {
          withCredentials: true,
          headers: { 'X-CSRF-Token': DrupalSettings.getCsrfToken() }
        };
      console.log(DrupalSettings.getCsrfToken());
      return $http.post(DrupalSettings.getBasePath() + 'api/v1/articles', data, config);
    };
  }
]);
'use strict';
angular.module('restfulApp').service('DrupalSettings', [
  '$window',
  function ($window) {
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
    this.getBasePath = function () {
      return angular.isDefined(self.settings.restfulExample.basePath) ? self.settings.restfulExample.basePath : undefined;
    };
    /**
     * Get the base path of the Drupal installation.
     */
    this.getCsrfToken = function () {
      return angular.isDefined(self.settings.restfulExample.csrfToken) ? self.settings.restfulExample.csrfToken : undefined;
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
    this.getAutoFieldsData = function (id) {
      return angular.isDefined(self.settings.restfulExample.autoFieldsData[id]) ? self.settings.restfulExample.autoFieldsData[id] : {};
    };
  }
]);