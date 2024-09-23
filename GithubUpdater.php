<?php
namespace BadabingBreda;
/**
 * Github updater
 *
 * @example Github release body:
 * 	Tested: 6.3
 *	Icons: 1x|https://domainname.com/icon-256x256.png?rev=2818463,2x|https://domainname.com/icon-256x256.png?rev=2818463
 *  Banners: 1x|https://domainname.com/banner-720x250.png
 *	RequiresPHP: 7.0
 *
 *	|||
 *	Add your changelog here
 *
 */
class GithubUpdater {

	private $file;
	private $plugin;
	private $basename;
	private $active;
	private $username;
	private $repository;
	private $authorize_token;
	private $github_response;

	private $plugin_settings;


	/**
	 * GithubUpdater constructor.
	 *
	 * @param string $file
	 * @param string $username
	 * @param string $repository
	 * @param string $authorize_token
	 *
	 * @return GithubUpdater
	 */
	public function __construct( $file , $username = false , $repository = false , $authorize_token = false ) {
		$this->file = $file;

		if ( $username ) {
			$this->set_username( $username );	
		}

		if ( $repository ) {
			$this->set_repository( $repository );
		}

		if ( $authorize_token ) {
			$this->authorize( $authorize_token );
		}

		add_action( 'admin_init', array( $this, 'set_plugin_properties' ) );
		return $this;
	}

	/**
	 * Set the plugin properties after the plugin row has been loaded.
	 *
	 * The plugin properties are set after the plugin row has been loaded
	 * because the plugin basename is required to determine if the plugin is
	 * active. This is the earliest point in the WordPress loading process
	 * where the basename is known.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function set_plugin_properties() {
		$this->plugin	= get_plugin_data( $this->file );
		$this->basename = plugin_basename( $this->file );
		$this->active	= is_plugin_active( $this->basename );
	}


	/**
	 * Set the GitHub username.
	 *
	 * @since 1.0
	 * @access public
	 * @param string $username The GitHub username.
	 */
	public function set_username( $username ) {
		$this->username = $username;
	}

	/**
	 * Set the plugin settings.
	 *
	 * The plugin settings are used to filter the list of plugins on the WordPress
	 * Plugin Directory. The settings that can be set are as follows:
	 *
	 * - requires: The minimum version of WordPress that the plugin requires.
	 * - tested: The highest version of WordPress that the plugin has been tested with.
	 * - rating: The rating of the plugin on a scale of 1 to 100.
	 * - num_ratings: The number of ratings the plugin has received.
	 * - downloaded: The number of times the plugin has been downloaded.
	 * - added: The date the plugin was added to the directory.
	 * - banners: An array of banner images to display on the plugin's page on the
	 *   WordPress Plugin Directory.
	 *
	 * If any of the settings are not set, the following defaults will be used:
	 *
	 * - requires: 5.4
	 * - tested: 6.3
	 * - rating: 100.0
	 * - num_ratings: 10
	 * - downloaded: 10
	 * - added: 2023-10-03
	 * - banners: false
	 *
	 * @since 1.0
	 * @access public
	 * @param array $settings An associative array of settings to set.
	 */
	public function set_settings( $settings ) {

		// set some defaults in case someone forgets to set these
		$defaults = array(
			'requires'			=> '5.4',
			'tested'			=> '6.3',
			'rating'			=> '100.0',
			'num_ratings'		=> '10',
			'downloaded'		=> '10',
			'added'				=> '2023-10-03',
			'banners'			=> false,
		);

		$settings = wp_parse_args( $settings , $defaults );

		$this->plugin_settings = $settings;
	}

	/**
	 * Set the repository slug
	 *
	 * The repository slug is used to create the URL that is used to fetch the
	 * plugin's data from Github. The slug should be in the format of
	 * `username/repository`.
	 *
	 * @since 1.0
	 * @access public
	 * @param string $repository The repository slug.
	 */
	public function set_repository( $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Authorize the plugin to access the Github API on your behalf.
	 *
	 * The authorization token is used to access the Github API on your behalf.
	 * The token should be in the format of a Github personal access token.
	 *
	 * @since 1.0
	 * @access public
	 * @param string $token The Github personal access token.
	 */
	public function authorize( $token ) {
		$this->authorize_token = $token;
	}


	/**
	 * Get repository information from Github
	 *
	 * This method fetches the information of the plugin from Github and stores
	 * it in the class property github_response.
	 *
	 * @since 1.0
	 * @access private
	 * @return array The repository information.
	 */
	private function get_repository_info() {

		// Do we have a response?
	    if ( is_null( $this->github_response ) ) {
	    	// Build URI
	        $request_uri = sprintf( 'https://api.github.com/repos/%s/%s/releases', $this->username, $this->repository );

	        // Is there an access token?
	        if( $this->authorize_token ) {
	        	// Append it
	            $request_uri = add_query_arg( 'access_token', $this->authorize_token, $request_uri );
	        }

	        // Get JSON and parse it
	        $response = json_decode( wp_remote_retrieve_body( wp_remote_get( $request_uri ) ), true );

	        // If it is an array
	        if( is_array( $response ) ) {
	        	// Get the first item
	            $response = current( $response );

				// Is there an access token?
				if( $this->authorize_token ) {
					// Update our zip url with token
					$response['zipball_url'] = add_query_arg( 'access_token', $this->authorize_token, $response['zipball_url'] );
				}
	
				// try to get metadata from the release body
				$metadata = $this->get_tmpfile_data( $response['body']);
	
				// merge the data with the response
				$response = array_merge( $response, $metadata);
	
				// Set it to our property
				$this->github_response = $response;
	        }
	        return $response;
	    }

	}

	/**
	 * Initialize the class and add our filters and actions
	 *
	 * @since 1.0
	 * @access public
	 */
	public function initialize() {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'modify_transient' ), 10, 1 );
		add_filter( 'plugins_api', array( $this, 'plugin_popup' ), 10, 3);
		add_filter( 'upgrader_post_install', array( $this, 'after_install' ), 10, 3 );
	}

	/**
	 * Modify the transient response to include our plugin's info.
	 *
	 * If the plugin is out of date, modify the transient to include the new
	 * version's information.
	 *
	 * @since 1.0
	 * @access public
	 * @param object $transient The transient response object.
	 * @return object The modified transient response object.
	 */
	public function modify_transient( $transient ) {

		if ( empty( $trainsient->checked )) return $transient;

		// Get the repo info
		$this->get_repository_info();

		// Check if we're out of date
		$out_of_date = version_compare( $this->github_response['tag_name'], $checked[ $this->basename ], 'gt' );

		if( $out_of_date ) {

			// Get the ZIP
			$new_files = $this->github_response['zipball_url'];

			// Create valid slug
			$slug = current( explode('/', $this->basename ) );

			// setup our plugin info
			$plugin = array(
				'url' => $this->plugin["PluginURI"],
				'slug' => $slug,
				'package' => $new_files,
				'tested' => $this->github_response['tested'],
				'icons' => $this->github_response['icons'],
				'banners' => $this->github_response['banners'],
				'banners_rtl' => [],
				'requires_php' => $this->github_response['requires_php'],
				'new_version' => $this->github_response['tag_name'],
			);

			// Return it in response
			$transient->response[$this->basename] = (object) $plugin;
		}
		
		// Return filtered transient
		return $transient;
	}


	/**
	 * Get data from a github release text file
	 *
	 * This method takes a string from a github release text file and returns
	 * an associative array with the data from it.
	 *
	 * @since 4.0
	 * @access private
	 *
	 * @param string $string The text file contents.
	 * @return array The associative array with the data.
	 */
	private function get_tmpfile_data( $string ) {


		// create a wp temp file in the 
		$temp_file = wp_tempnam();
		$temp = fopen($temp_file, 'r+');
		
		// make sure to also delete the file when done or even when scripts fail
		register_shutdown_function( function() use( $temp_file ) {
			@unlink( $temp_file );
		} );		

		$tmpfilename = stream_get_meta_data($temp)['uri'];
		fwrite( $temp, $string);

        $file_headers = \get_file_data( 
            $tmpfilename,
            [
                'tested' => 'Tested',
				'icons' => 'Icons',
				'banners' => 'Banners',
				'requires_php' => 'RequiresPHP',
				'name' => 'Name',
				'rating' => 'Rating',
				'num_ratings' => 'NumRatings',
				'downloaded' => 'Downloaded',
				'description' => 'Description',
				'author' => 'Author',
				'author_profile' => 'AuthorProfile',
				'homepage' => 'Homepage',
            ]
        );

		$icons = $file_headers[ 'icons' ] ? array_map( 'trim', explode(',', $file_headers[ 'icons' ] ) ) : false;
		$banners = $file_headers[ 'banners' ] ? array_map( 'trim', explode(',', $file_headers[ 'banners' ] ) ) : false;

		$username = $this->username;
		$repository = $this->repository;

		// decompose the icons, if provided
		if (is_array($icons)) {
			$icons = array_reduce( $icons, function ($acc , $item) use ($username,$repository) { 
				$ex_item = explode('|', $item);
				$acc[$ex_item[0]] = sprintf("https://github.com/%s/%s" , $username, $repository ) . $ex_item[1];
				return $acc;
			} , []);
		}

		// decompose the banners, if provided
		if (is_array($banners)) {
			$banners = array_reduce( $banners, function ($acc , $item) use ($username,$repository) { 
				$ex_item = explode('|', $item);
				$acc[$ex_item[0]] = sprintf("https://github.com/%s/%s" , $username, $repository ) . $ex_item[1];
				return $acc;
			} , []);
		}

		// try to find the update_description delimiter
		$update_description = explode( '|||' , $string );

		$updates = ( sizeof($update_description) == 2 ) ? $update_description[1] : '';

		$data = [
            'tested' => $file_headers[ 'tested' ],
            'requires_php' => $file_headers[ 'requires_php' ],
			'name' => $file_headers[ 'name' ],
			'rating' => $file_headers[ 'rating' ],
			'num_ratings' => $file_headers[ 'num_ratings' ],
			'downloaded' => $file_headers[ 'downloaded' ],
			'description' => $file_headers[ 'description' ],
			'author' => $file_headers[ 'author' ],
			'author_profile' => $file_headers[ 'author_profile' ],
			'homepage' => $file_headers[ 'homepage' ],
            'icons' => $icons,
            'banners' => $banners,
			'updates' => $updates,
        ];

		// the register_shutdown function will also make sure the temp-file
		// gets deleted whenever something fails

        return $data;




	}

	/**
	 * Hook into the plugin popup on the WordPress plugins page.
	 *
	 * @param object $result The result object passed to the filter.
	 * @param string $action The current action being performed.
	 * @param object $args The current plugin information.
	 *
	 * @return object The modified result object.
	 */
	public function plugin_popup( $result, $action, $args ) {

		// If there is a slug
		if( ! empty( $args->slug ) ) {

			// And it's our slug
			if( $args->slug == current( explode( '/' , $this->basename ) ) ) {

				// Get our repo info
				$this->get_repository_info();
				// Set it to an array
				$plugin = array(
					'name'				=> $this->plugin["Name"],
					'slug'				=> $this->basename,
					'version'			=> $this->github_response['tag_name'],
					'new_version'		=> $this->github_response['tag_name'],
					'author'			=> $this->plugin["AuthorName"],
					'author_profile'	=> $this->plugin["AuthorURI"],
					'last_updated'		=> $this->github_response['published_at'],
					'homepage'			=> $this->plugin["PluginURI"],
					'short_description' => $this->plugin["Description"],
					'sections'			=> array(
						'Description'	=> $this->plugin["Description"],
						'Updates'		=> $this->github_response['updates'],
					),
					'icons'				=> $this->github_response[ 'icons' ],
					'banners'			=> $this->github_response[ 'banners' ],
					'download_link'		=> $this->github_response['zipball_url']
				);

				// merge with other settings that can be set
				$plugin = wp_parse_args( $plugin, $this->plugin_settings );

				// Return the data
				return (object) $plugin;
			}
		}
		// Otherwise return default
		return $result;
	}

	/**
	 * Move the plugin files to the correct directory after installation.
	 *
	 * @param object $response The update response object.
	 * @param string $hook_extra The hook extra for the action.
	 * @param array  $result The result of the installation.
	 *
	 * @return array The installation result, modified to include the correct destination.
	 */
	public function after_install( $response, $hook_extra, $result ) {

		// Get global FS object
		global $wp_filesystem;

		// Our plugin directory
		$install_directory = plugin_dir_path( $this->file );

		// Move files to the plugin dir
		$wp_filesystem->move( $result['destination'], $install_directory );

		// Set the destination for the rest of the stack
		$result['destination'] = $install_directory;

		// If it was active
		if ( $this->active ) {

			// Reactivate
			activate_plugin( $this->basename );
		}

		return $result;
	}
}