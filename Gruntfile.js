module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
	  eslint: {
          	retrieve: {
		              src: ['includes/tabs/retrieve.js']
	          }
 	   },
    checktextdomain: {
      options: {
        text_domain: 'indieweb-post-kinds',
        keywords: [
          '__:1,2d',
          '_e:1,2d',
          '_x:1,2c,3d',
          'esc_html__:1,2d',
          'esc_html_e:1,2d',
          'esc_html_x:1,2c,3d',
          'esc_attr__:1,2d',
          'esc_attr_e:1,2d',
          'esc_attr_x:1,2c,3d',
          '_ex:1,2c,3d',
          '_n:1,2,4d',
          '_nx:1,2,4c,5d',
          '_n_noop:1,2,3d',
          '_nx_noop:1,2,3c,4d'
        ]
      },
      files: {
        src: [
          '**/*.php',         // Include all files
          'includes/*.php', // Include includes
          '!sass/**',       // Exclude sass/
          '!node_modules/**', // Exclude node_modules/
          '!tests/**',        // Exclude tests/
          '!vendor/**',       // Exclude vendor/
          '!build/**'           // Exclude build/
        ],
        expand: true
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

    svgstore: {
      options: {
        prefix: '', // Unused by us, but svgstore demands this variable
        cleanup: ['style', 'fill', 'id'],
        svg: { // will add and overide the the default xmlns="http://www.w3.org/2000/svg" attribute to the resulting SVG
          viewBox: '0 0 24 24',
          xmlns: 'http://www.w3.org/2000/svg'
        }
      },
      dist: {
        files: {
          'kinds.svg': ['svgs/*.svg']
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
  grunt.loadNpmTasks('grunt-svgstore');
  grunt.loadNpmTasks('grunt-checktextdomain');
  grunt.loadNpmTasks('grunt-eslint');

  // Default task(s).
  grunt.registerTask('default', ['wp_readme_to_markdown', 'makepot', 'sass', 'svgstore', 'checktextdomain', 'eslint']);

};
