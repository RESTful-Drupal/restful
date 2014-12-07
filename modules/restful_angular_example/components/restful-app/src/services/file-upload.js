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
