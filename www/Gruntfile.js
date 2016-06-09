module.exports = function (grunt) {

    require('time-grunt')(grunt);
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        sass: {
            options: {

            },
            dev: {
                options: {
                    sourceMap: true
                },
                files: {
                    'css/master.css': 'src/scss/main.scss'
                }
            },
            dist: {
                options: {
                    outputStyle: 'compressed'
                },
                files: {
                    'css/master.css': 'src/scss/main.scss'
                }
            }
        },

        autoprefixer: {
            dist: {
                files: {
                    'css/master.css': 'css/master.css'
                }
            }
        },

        uglify: {
            dist: {
                files: {
                    'js/master.js': 'js/master.js'
                }
            }
        },

        browserify: {
            dist: {
                files: {
                    './js/master.js': ['./src/js/main.js']
                }
            }
        },

        handlebars: {
            compile: {
                options: {
                    node: true,
                    wrapped: true,
                    namespace: 'App.Templates',
                    partialsUseNamespace: true,
                    processName: function (path) {
                        return path.split('/').pop().split('.')[0];
                    }
                },
                files: {
                    "src/js/templates.js": "src/templates/**/*.{hbs,handlebars}"
                }
            }
        },

        watch: {
            grunt: {
                options: {
                    reload: true
                },
                files: ['Gruntfile.js']
            },
            js: {
                files: ['src/js/**/*.js'],
                tasks: ['browserify']
            },
            sass: {
                files: ['src/scss/**/*.scss'],
                tasks: ['sass:dev']
            },
            handlebars: {
                files: ['src/templates/**/*.{hbs,handlebars}'],
                tasks: ['handlebars']
            }
        }
    });

    grunt.loadNpmTasks('grunt-browserify');
    grunt.loadNpmTasks('grunt-sass');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-handlebars');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-autoprefixer');

    grunt.registerTask('default', ['build', 'watch']);
    grunt.registerTask('build', ['sass:dev', 'handlebars', 'browserify']);

    grunt.registerTask('release', ['sass:dist', 'autoprefixer', 'handlebars', 'browserify', 'uglify']);
};