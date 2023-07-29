<?php
/**
 * WP Async Request
 *
 * @package WP-Background-Processing
 */

 namespace PluginToolsServer\Providers\Rest;
 use PluginToolsServer\Services\BitbucketManager;

/**
 * Abstract WP_Async_Request class.
 *
 * @abstract
 */
class PluginDownloadJob {

	/**
	 * Prefix
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'wp';

	/**
	 * Action
	 *
	 * (default value: 'async_request')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'plugin_download_job';

	/**
	 * Identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $identifier;

	/**
	 * Data
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $data = array();

	/**
	 * Initiate new async request.
	 */
	public function __construct() {
		$this->identifier = $this->prefix . '_' . $this->action;
		add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
		add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );
	}

	/**
	 * Set data used during the request.
	 *
	 * @param array $data Data.
	 *
	 * @return $this
	 */
	public function data( $data ) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Dispatch the async request.
	 *
	 * @return array|WP_Error|false HTTP Response array, WP_Error on failure, or false if not attempted.
	 */
	public function dispatch() {
		$url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
		$args = $this->get_post_args();
		$result = wp_remote_post( esc_url_raw( $url ), $args );
		error_log( print_r($url, true));
        return;
	}

	/**
	 * Get query args.
	 *
	 * @return array
	 */
	protected function get_query_args() {
		if ( property_exists( $this, 'query_args' ) ) {
			return $this->query_args;
		}

		$args = array(
			'action' => $this->identifier,
			'nonce'  => wp_create_nonce( $this->identifier ),
		);

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param array $url
		 */
		return apply_filters( $this->identifier . '_query_args', $args );
	}

	/**
	 * Get query URL.
	 *
	 * @return string
	 */
	protected function get_query_url() {
		if ( property_exists( $this, 'query_url' ) ) {
			return $this->query_url;
		}

		$url = admin_url( 'admin-ajax.php' );

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param string $url
		 */
		return apply_filters( $this->identifier . '_query_url', $url );
	}

	/**
	 * Get post args.
	 *
	 * @return array
	 */
	protected function get_post_args() {
		if ( property_exists( $this, 'post_args' ) ) {
			return $this->post_args;
		}

		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => $this->data,
			'cookies'   => $_COOKIE, // Passing cookies ensures request is performed as initiating user.
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ), // Local requests, fine to pass false.
		);

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param array $args
		 */
		return apply_filters( $this->identifier . '_post_args', $args );
	}

	/**
	 * Maybe handle a dispatched request.
	 *
	 * Check for correct nonce and pass to handler.
	 *
	 * @return void|mixed
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing.

		error_log('maybe handle');

		session_write_close();

		$nonce_check = check_ajax_referer( $this->identifier, 'nonce', false );

		if( ! $nonce_check ) {
			error_log('nonce check failed');
			if ( wp_doing_ajax() ) {
				wp_die( -1, 403 );
			} else {
				die( '-1' );
			}
		}

		$this->handle();

		return $this->maybe_wp_die();
	}

	/**
	 * Should the process exit with wp_die?
	 *
	 * @param mixed $return What to return if filter says don't die, default is null.
	 *
	 * @return void|mixed
	 */
	protected function maybe_wp_die( $return = null ) {
		/**
		 * Should wp_die be used?
		 *
		 * @return bool
		 */
		if ( apply_filters( $this->identifier . '_wp_die', true ) ) {
			wp_die();
		}

		return $return;
	}

    protected function handle()
    {
		$data = isset($_POST['data']) ? sanitize_text_field($_POST['data']) : '';

		$plugin_URL = $_POST['plugin_url'];
		$plugin_Slug = $_POST['plugin_slug'];
		$plugin_Name = $_POST['plugin_name'];
		$plugin_Version = $_POST['plugin_version'];

        $bitbucketManager = new BitbucketManager();

        $targetDir = $bitbucketManager->getTargetDir();
        
        try {
            $bitbucketManager->handlePluginUpdate($plugin_URL, $plugin_Slug, $plugin_Name, $plugin_Version);
        } catch (\Exception $e) {
			error_log('data in data');
			error_log(print_r($e,true));
			// error_log('error in handle'.print_r($e->getMessage(), true);
        }
    }
}