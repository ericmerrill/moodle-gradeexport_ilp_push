// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Gruntfile.
 *
 * @package    gradeexport_ilp_push
 * @author     Eric Merrill (merrill@oakland.edu)
 * @copyright  2019 Oakland University (https://www.oakland.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */
"use strict";

module.exports = function (grunt) {

    // We need to include the core Moodle grunt file too, otherwise we can't run tasks like "amd".
    require("grunt-load-gruntfile")(grunt);
    grunt.loadGruntfile("../../../Gruntfile.js");

    var uglifyRename = function(destPath, srcPath) {
        destPath = srcPath.replace('src', 'build');
        destPath = destPath.replace('.js', '.min.js');
        return destPath;
    };

    grunt.initConfig({
        exec: {
            decache: {
                cmd: 'php "../../../admin/cli/purge_caches.php"',
                callback: function(error) {
                    // Warning: Be careful when executing this task.  It may give
                    // file permission errors accessing Moodle because of the directory permissions
                    // for configured Moodledata directory if this is run as root.
                    // The exec process will output error messages.

                    // Just add one to confirm success.
                    if (!error) {
                        grunt.log.writeln("All Moodle caches reset.");
                    }
                }
            },
            decachetheme: {
                cmd: 'php "../../../admin/cli/purge_caches.php" --theme',
                callback: function(error) {
                    if (!error) {
                        grunt.log.writeln("Moodle theme cache reset.");
                    }
                }
            },
            decachejs: {
                cmd: 'php "../../../admin/cli/purge_caches.php" --js',
                callback: function(error) {
                    if (!error) {
                        grunt.log.writeln("Moodle JS cache reset.");
                    }
                }
            },
            decachelang: {
                cmd: 'php "../../../admin/cli/purge_caches.php" --lang',
                callback: function(error) {
                    if (!error) {
                        grunt.log.writeln("Moodle lang cache reset.");
                    }
                }
            }

        },
        watch: {
            options: {
                nospawn: true // We need not to spawn so config can be changed dynamically.
            },
            amd: {
                // If any .js file changes in directory "amd/src" then run the "amd" task.
                files: ["**/amd/src/*.js"],
                tasks: ["amd", "decachejs"]
            },
            less: {
                // If any .less file changes in directory "less" then run the "less" task.
                files: ["**/scss/*.scss"],
                tasks: ["css", "decachetheme"]
            },
            lang: {
                // If any .less file changes in directory "less" then run the "less" task.
                files: ["**/lang/en/*.php"],
                tasks: ["decachelang"]
            }
        },
        sass: {
            dist: {
                files: {
                    "styles.css": "scss/styles.scss"
                }
            }
        },
        stylelint: {
            scss: {
                options: {syntax: 'scss'},
                src: ['scss/styles.scss']
            },
            css: {
                src: ['styles.css'],
                options: {
                    configOverrides: {
                        rules: {
                            // These rules have to be disabled in .stylelintrc for scss compat.
                            "at-rule-no-unknown": true,
                        }
                    }
                }
            }
        },
        uglify: {
            amd: {
                files: [{
                    expand: true,
                    src: ['amd/src/*.js'],
                    rename: uglifyRename
                }],
                options: {report: 'none'}
            }
        },
        eslint: {
            // Setup the local AMD source files.
            amd: {src: 'amd/src/*.js'},
            options: {report: 'none'}
        },
    });

    grunt.loadNpmTasks("grunt-exec");

    grunt.registerTask('decache', ['exec:decache']);
    grunt.registerTask('decachetheme', ['exec:decachetheme']);
    grunt.registerTask('decachejs', ['exec:decachejs']);
    grunt.registerTask('decachelang', ['exec:decachelang']);

    grunt.registerTask('amd', ['eslint:amd', 'uglify']);
    grunt.registerTask('css', ['sass']);
    grunt.registerTask('csscheck', ['sass', 'stylelint:scss']);

    grunt.registerTask('default', ['watch']);
};
