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
          "X-CSRF-Token": DrupalSettings.getCsrfToken()
        }
      };

      console.log(DrupalSettings.getCsrfToken());

      return $http.post(DrupalSettings.getBasePath() + 'api/v1/articles', data, config);
    }
  });
