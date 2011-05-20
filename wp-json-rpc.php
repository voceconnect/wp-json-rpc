<?php

/*
  Plugin Name: WP JSON-RPC
  Plugin URI: http://plugins.voceconnect.com
  Description: This plugin provides a JSON version of the XML-RPC WordPress API. Based on the <a href="http://wordpress.org/extend/plugins/wp-json-rpc-api/">WP JSON RPC API Plugin</a> written by <a href="http://profiles.wordpress.org/users/filosofo/">filosofo</a>.
  Author: Jeff Stieler
  Version: 0.9.1
 */

if (!defined('ABSPATH')) {
	die('Please do not load this file directly.');
}

include_once(ABSPATH . 'wp-admin/includes/admin.php');
include_once(ABSPATH . WPINC . '/class-IXR.php');
include_once(ABSPATH . WPINC . '/class-wp-xmlrpc-server.php');

if (!function_exists('load_wp_json_rpc_api')) {

	function load_wp_json_rpc_api() {
		global $wp_json_rpc_api;

		if (empty($wp_json_rpc_api)) {
			$wp_json_rpc_api = new WP_JSON_RPC_API_Control;
		}
	}

	add_action('plugins_loaded', 'load_wp_json_rpc_api');
}

class WP_JSON_RPC_API_Control {

	public $id;
	public $view;

	public function __construct() {
		$this->view = new WP_JSON_RPC_API_View;
		add_action('init', array(&$this, 'event_init'));
	}

	public function event_init() {
		// listen for request with ?json-rpc-request
		if (!empty($_REQUEST['json-rpc-request'])) {
			global $HTTP_RAW_POST_DATA;
			// From xmlrpc.php (thanks!)
			// A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
			// but we can do it ourself.
			if (!isset($HTTP_RAW_POST_DATA)) {
				$HTTP_RAW_POST_DATA = file_get_contents('php://input');
			}

			$request = $HTTP_RAW_POST_DATA;
			if (get_magic_quotes_gpc ()) {
				$request = stripslashes($request);
			}

			$decoded = json_decode($request);

			// in some configurations the request is slashed even when get_magic_quotes_gpc returns 0--not sure why
			if (empty($decoded) || empty($decoded->jsonrpc)) {
				$decoded = json_decode(stripslashes($request));
			}

			if (!empty($decoded) && !empty($decoded->jsonrpc)) {

				if (!empty($decoded->params)) {
					$decoded->params = $this->json_object_to_array($decoded->params);
				}

				$this->handle_json_request($decoded);
			}
		}
	}

	public function is_array_or_object($object) {
		return (is_array($object) || (is_object($object) && ('stdClass' == get_class($object))));
	}

	public function json_object_to_array($object) {
		if ($this->is_array_or_object($object)) {
			$object = (array) $object;
			foreach ($object as $key => $value) {
				if ($this->is_array_or_object($object)) {
					$object[$key] = $this->json_object_to_array($value);
				}
			}
		}
		return $object;
	}

	public function handle_json_request($request = null) {
		if (empty($request->method)) {
			return;
		}

		header('Content-Type: application/json');
		$id = isset($request->id) ? (string) $request->id : null;
		$this->id = $id;

		$server_class = apply_filters('json_server_classname', 'WP_JSON_RPC_Server', $request->method);
		$json_server = new $server_class;
		$result = $json_server->serve_request($request);

		if (is_a($result, 'IXR_Error')) {
			echo $this->view->get_json_error(
					new WP_Error($result->code, $result->message),
					$id
			);
			exit;
		} elseif (is_wp_error($result)) {
			echo $this->view->get_json_error(
					$result,
					$id
			);
			exit;
		} else {
			echo $this->view->get_json_result(
					$result,
					$id
			);
			exit;
		}
	}

}

class WP_JSON_RPC_API_View {

	public function get_json_error(WP_Error $error, $id = null, $data = null) {
		$code = (int) $error->get_error_code();
		$message = $error->get_error_message();

		$error = array(
			'code' => $code,
			'message' => $message,
		);

		if (!empty($data)) {
			$error['data'] = $data;
		}

		return json_encode(array(
			'jsonrpc' => '2.0',
			'error' => $error,
			'id' => $id,
				));
	}

	public function get_json_result($result = null, $id = null) {
		return json_encode(array(
			'jsonrpc' => '2.0',
			'result' => $result,
			'id' => $id,
				));
	}

}

class WP_JSON_RPC_Server extends wp_xmlrpc_server {

	public function serve_request($data = null) {
		$this->setCapabilities();
		$this->callbacks = apply_filters('jsonrpc_methods', $this->methods);
		$this->setCallbacks();
		$this->message = new WP_JSON_RPC_Message($data);
		$this->message->parse();
		$result = $this->call($this->message->methodName, $this->message->params);

		return $result;
	}

}

class WP_JSON_RPC_Message extends IXR_Message {

	public function parse() {
		$this->methodName = $this->message->method;
		$this->messageType = 'methodCall';
		$this->params = is_array($this->message->params) ? $this->message->params : array($this->message->params);
	}

}
