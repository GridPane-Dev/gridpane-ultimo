<?php
/**
 * All this is Dima really, I just made it a plugin
 *
 */

class GP_WU_Domain_Mapping_Hosting_support extends WU_Domain_Mapping_Hosting_Support
{
	public function __construct() {

		/**
		 * GridPane.com Support
		 */
		if ($this->uses_gridpane()) {
			add_action('mercator.mapping.created', array($this, 'add_domain_gridpane'), 20);
			add_action('mercator.mapping.updated', array($this, 'add_domain_gridpane'), 20);
			add_action('mercator.mapping.deleted', array($this, 'remove_domain_from_gridpane'), 20);
		} // end if;

	} // end construct;

	/**
	 * Checks if this site is hosted on GridPane.com or not
	 *
	 * @return bool
	 */
	public function uses_gridpane() {
		return defined('WU_GRIDPANE') && WU_GRIDPANE;
	}

	/**
	 * Sends a request to Gridpane.com, with the right API key
	 *
	 * @param  string $endpoint Endpoint to send the call to
	 * @param  array  $data     Array containing the params to the call
	 * @return object
	 */
	public function send_gridpane_api_request($endpoint, $data = array(), $method = 'POST') {
		$post_fields = array(
			'timeout'     => 45,
			'blocking'    => true,
			'method'      => $method,
			'body'        => array_merge(array(
				'api_token'       => WU_GRIDPANE_API_KEY,
			), $data)
		);

		if (defined('WP_DEBUG') && true === WP_DEBUG) {
			$this->log("++++++++Send Gridpane API Request+++++++++", false);
			$this->log($endpoint,false);
			$this->log($data, false);
			$this->log($method, false);			
		 }

		$gridpane_instance = 'my.gridpane.com';
		if (defined('GRIDPANE_INSTANCE')) {
			$gridpane_instance = GRIDPANE_INSTANCE;
		}

		$response = wp_remote_request('https://' . $gridpane_instance . '/api/application/' . $endpoint, $post_fields);

		if (defined('WP_DEBUG') && true === WP_DEBUG) {
			$this->log($response, false);
		}

		if (!is_wp_error($response)) {
			$body = json_decode(wp_remote_retrieve_body($response), true);

			if (json_last_error() === JSON_ERROR_NONE) {
				return $body;
			}
		}

		return $response;
	}

	/**
	 * Add domain to GridPane.com
	 *
	 * @param  Mercator\Mapping $mapping
	 * @return void
	 */
	public function add_domain_gridpane($mapping) {
		$domain = $mapping->get_domain();

		if (!$this->uses_gridpane() || ! $domain) {
			return;
		}

		$this->send_gridpane_api_request('add-domain', array(
			'server_ip' => WU_GRIDPANE_SERVER_ID,
			'site_url' => WU_GRIDPANE_APP_ID,
			'domain_url' => $domain
		));
	}

	/**
	 * Removes a mapped domain from Gridpane.com
	 *
	 * @param  Mercator\Mapping $mapping
	 * @return void
	 */
	public function remove_domain_from_gridpane($mapping) {
		$domain = $mapping->get_domain();

		if (!$this->uses_gridpane() || ! $domain) {
			return;
		}

		$this->send_gridpane_api_request('delete-domain', array(
			'server_ip' => WU_GRIDPANE_SERVER_ID,
			'site_url' => WU_GRIDPANE_APP_ID,
			'domain_url' => $domain
		));
	}

	/**
	 * Prints a message to the debug file that can easily be called by any subclass.
	 *
	 * @param mixed $message      an object, array, string, number, or other data to write to the debug log
	 * @param bool  $shouldNotDie whether or not the The function should exit after writing to the log
	 *
	 */
	protected function log($message, $shouldNotDie = true)
	{
		error_log(print_r($message, true));
		if ($shouldNotDie) {
			exit;
		}
	}

}

if ( ! method_exists('WU_Domain_Mapping_Hosting_Support','uses_gridpane')) {

	new GP_WU_Domain_Mapping_Hosting_Support();

};
