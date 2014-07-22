// Generated on 2014-05-18 using generator-angular-component 0.2.3
'use strict';

module.exports = function(grunt) {

  // Configurable paths
  var yeomanConfig = {
    livereload: 35729,
    src: 'src',
    dist: 'dist'
  };

  // Livereload setup
  var lrSnippet = require('connect-livereload')({port: yeomanConfig.livereload});
  var mountFolder = function (connect, dir) {
    return connect.static(require('path').resolve(dir));
  };

  // Load all grunt tasks
  require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

  // Project configuration
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    yeoman: yeomanConfig,
    meta: {
      banner: '/**\n' +
      ' * <%= pkg.name %>\n' +
      ' * @version v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>\n' +
      ' * @link <%= pkg.homepage %>\n' +
      ' * @author <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
      ' * @license MIT License, http://www.opensource.org/licenses/MIT\n' +
      ' */\n'
    },
    open: {
      server: {
        path: 'http://localhost:<%= connect.options.port %>'
      }
    },
    clean: {
      dist: {
        files: [{
          dot: true,
          src: [
            '.tmp',
            '<%= yeoman.dist %>/*',
            '!<%= yeoman.dist %>/.git*'
          ]
        }]
      },
      server: '.tmp'
    },
    watch: {
      gruntfile: {
        files: '<%= jshint.gruntfile.src %>',
        tasks: ['jshint:gruntfile']
      },
      less: {
        files: ['<%= yeoman.src %>/{,*/}*.less'],
        tasks: ['less:dist']
      },
      app: {
        files: [
          '<%= yeoman.src %>/{,*/}*.html',
          '{.tmp,<%= yeoman.src %>}/{,*/}*.css',
          '{.tmp,<%= yeoman.src %>}/{,*/}*.js'
        ],
        options: {
          livereload: yeomanConfig.livereload
        }
      },
      test: {
        files: '<%= jshint.test.src %>',
        tasks: ['jshint:test', 'qunit']
      }
    },
    connect: {
      options: {
        port: 9000,
        hostname: '0.0.0.0' // Change this to '0.0.0.0' to access the server from outside.
      },
      livereload: {
        options: {
          middleware: function (connect) {
            return [
              lrSnippet,
              mountFolder(connect, '.tmp'),
              mountFolder(connect, yeomanConfig.src)
            ];
          }
        }
      }
    },
    less: {
      options: {
        // dumpLineNumbers: 'all',
        paths: ['<%= yeoman.src %>']
      },
      dist: {
        files: {
          '<%= yeoman.src %>/<%= yeoman.name %>.css': '<%= yeoman.src %>/<%= yeoman.name %>.less'
        }
      }
    },
    jshint: {
      gruntfile: {
        options: {
          jshintrc: '.jshintrc'
        },
        src: 'Gruntfile.js'
      },
      src: {
        options: {
          jshintrc: '.jshintrc'
        },
        src: ['<%= yeoman.src %>/{,*/}*.js']
      },
      test: {
        options: {
          jshintrc: 'test/.jshintrc'
        },
        src: ['test/**/*.js']
      }
    },
    karma: {
      options: {
        configFile: 'karma.conf.js',
        browsers: ['PhantomJS']
      },
      unit: {
        singleRun: true
      },
      server: {
        autoWatch: true
      }
    },
    ngmin: {
      options: {
        banner: '<%= meta.banner %>'
      },
      dist: {
        src: ['<%= yeoman.dist %>/<%= pkg.name %>.js'],
        dest: '<%= yeoman.dist %>/<%= pkg.name %>.js'
      }
      // dist: {
      //   files: {
      //     '/.js': '/.js'
      //   }
      // }
    },
    concat: {
      options: {
        banner: '<%= meta.banner %>',
        stripBanners: true
      },
      dist: {
        src: ['<%= yeoman.src %>/restful-app.js', '<%= yeoman.src %>/**/*.js'],
        dest: '<%= yeoman.dist %>/<%= pkg.name %>.js'
      }
    },
    uglify: {
      options: {
        banner: '<%= meta.banner %>'
      },
      dist: {
        src: '<%= concat.dist.dest %>',
        dest: '<%= yeoman.dist %>/<%= pkg.name %>.min.js'
      }
    },
    copy: {
      dist: {
        files: [
          {
            expand: true,
            cwd: '<%= yeoman.src %>',
            src: [
              'views/**/*.html',
              'css/**/*.css',
            ],
            dest: '<%= yeoman.dist %>'
          }
        ]
      }
    }
  });

  grunt.registerTask('test', [
    'jshint',
    'karma:unit'
  ]);

  grunt.registerTask('build', [
    'clean:dist',
    'copy:dist',
    'concat:dist'
  ]);

  grunt.registerTask('release', [
    'test',
    'bump-only',
    'dist',
    'bump-commit'
  ]);

  grunt.registerTask('default', ['build']);

};
