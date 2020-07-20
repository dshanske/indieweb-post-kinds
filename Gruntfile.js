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
		    files: [
		    	{expand: true, cwd: 'node_modules/datepair.js/dist', src: ['jquery.datepair.min.js'], dest: 'js/'}, 
		    	{expand: true, cwd: 'node_modules/moment/min', src: ['moment.min.js'], dest: 'js/'},
		    	{expand: true, cwd: 'node_modules/timepicker', src: ['jquery.timepicker.min.js'], dest: 'js/'},
  			],
		},
    },

    wp_readme_to_markdown: {
      target: {
      	options: {
      	  screenshot_url: '/.wordpress-org/{screenshot}.png'
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
        }
      }
    }
  });

  grunt.loadNpmTasks('grunt-wp-readme-to-markdown');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks('grunt-eslint');

  // Default task(s).
  grunt.registerTask('default', ['copy', 'wp_readme_to_markdown', 'sass', 'eslint']);

};
