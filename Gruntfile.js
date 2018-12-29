module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
	  eslint: {
          	retrieve: {
		              src: ['js/kind.js' ]
	          }
 	   },
    copy: {
      main: {
        options: {
          mode: true
        },
        src: [
          '**',
          '!node_modules/**',
          '!build/**',
          '!.git/**',
          '!Gruntfile.js',
          '!package.json',
          '!.gitignore',
		   '!kind.css.map',
		   '!kind.min.css.map',
		   '!vendor/**',
		   'vendor/mf2/mf2/Mf2/Parser.php'
        ],
        dest: 'build/trunk/'
      },
	    assets: {
	       options: {
	           mode: true
	       },
	       src: [
	         'assets/*'
	       ],
	       dest: 'build/'
	    }
    },

    wp_readme_to_markdown: {
      target: {
      	options: {
      	  screenshot_url: '/assets/{screenshot}.png'
        },
        files: {
          'readme.md': 'readme.txt'
        }
      },
      options: {
        screenshot_url: 'https://ps.w.org/indieweb-post-kinds/trunk/{screenshot}.png'
      }
    },
    sass: {                              // Task
      dev: {                            // Target
        options: {                       // Target options
          style: 'expanded'
        },
        files: {                         // Dictionary of files
          'css/kind.css': 'sass/main.scss'       // 'destination': 'source'
        }
      },
      dist: {                            // Target
        options: {                       // Target options
          style: 'compressed'
        },
        files: {                         // Dictionary of files
          'css/kind.min.css': 'sass/main.scss',       // 'destination': 'source'
          'css/kind.admin.min.css': 'sass/admin.scss',       // 'destination': 'source'
          'css/kind.themecompat.min.css': 'sass/themecompat.scss'       // 'destination': 'source'

        }
      }
    },

	svg_sprite		: {
		kinds: {
 	 	        src: ['svgs/*.svg'],
			dest: '.',
			options		: {

				shape				: {
					dimension		: {			// Set maximum dimensions
						maxWidth	: 32,
						maxHeight	: 32,
						attributes      : true
					},
					spacing			: {			// Add padding
						padding		: 10
					},
					id 			: {
						separator 	: ''
					}
				},
				mode : {
					symbol			: {		// Activate the «symbol» mode
						sprite : 'kinds.svg',
						dest: ''
					}

				}
			}
		}
	},

    makepot: {
      target: {
        options: {
          mainFile: 'indieweb-post-kinds.php', // Main project file.
          domainPath: '/languages',                   // Where to save the POT file.
          potFilename: 'post_kinds.pot',
          type: 'wp-plugin',                // Type of project (wp-plugin or wp-theme).
          exclude: [
            'build/.*'
          ],
          updateTimestamp: true             // Whether the POT-Creation-Date should be updated without other changes.
            	}
      }
    }
  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-wp-i18n');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-svg-sprite');
  grunt.loadNpmTasks('grunt-eslint');

  // Default task(s).
  grunt.registerTask('default', ['wp_readme_to_markdown', 'makepot', 'sass', 'svg_sprite', 'eslint']);

};
