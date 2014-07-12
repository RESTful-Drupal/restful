// Karma configuration
// http://karma-runner.github.io/0.10/config/configuration-file.html
module.exports = function(config) {
  'use strict';

  config.set({
    // base path, that will be used to resolve files and exclude
    basePath: '',

    // testing framework to use (jasmine/mocha/qunit/...)
    frameworks: ['jasmine'],

    // list of files / patterns to load in the browser
    files: [
      'bower_components/angular/angular.js',
      'bower_components/angular-mocks/angular-mocks.js',
      'bower_components/angular-animate/angular-animate.js',
      'bower_components/angular-cookies/angular-cookies.js',
      'bower_components/angular-auth/src/angular-auth.js',
      'bower_components/angular-local-storage/angular-local-storage.js',
      'bower_components/angular-modal/modal.js',
      'bower_components/angularLocalStorage/src/angularLocalStorage.js',
      'bower_components/danialfarid-angular-file-upload/dist/angular-file-upload.min.js',
      'bower_components/danialfarid-angular-file-upload/dist/angular-file-upload-shim.min.js',
      'bower_components/danialfarid-angular-file-upload/dist/angular-file-upload-html5-shim.min.js',
      'bower_components/ngInfiniteScroll/build/ng-infinite-scroll.js',
      'bower_components/eh-boxes/dist/eh-boxes.js',
      'src/restful-app.js',
      'src/controllers/login.js',
      'src/services/backendURL-interceptor.js',
      'src/services/job.js',
      'src/services/modal.js',
      'src/*.js',
      'test/spec/**/*.js'
    ],

    // list of files to exclude
    exclude: [],

    // test results reporter to use
    // possible values: dots || progress || growl
    reporters: ['progress'],

    // web server port
    port: 8081,

    // cli runner port
    runnerPort: 9101,

    // enable / disable colors in the output (reporters and logs)
    colors: true,

    // level of logging
    // possible values: LOG_DISABLE || LOG_ERROR || LOG_WARN || LOG_INFO || LOG_DEBUG
    logLevel: config.LOG_INFO,

    // enable / disable watching file and executing tests whenever any file changes
    autoWatch: false,

    // Start these browsers, currently available:
    // - Chrome
    // - ChromeCanary
    // - Firefox
    // - Opera
    // - Safari (only Mac)
    // - PhantomJS
    // - IE (only Windows)
    browsers: ['Chrome'],

    // If browser does not capture in given timeout [ms], kill it
    captureTimeout: 5000,

    // Continuous Integration mode
    // if true, it capture browsers, run tests and exit
    singleRun: false
  });
};
