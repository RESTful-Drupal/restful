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
