<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'subscriber/config.php';

class Subscriber_ext
{
	var $settings		= array();
	var $name			= SUBSCRIBER_NAME;
	var $class_name		= 'Subscriber';
	var $version		= SUBSCRIBER_VER;
	var $description	= SUBSCRIBER_DESC;
	var $settings_exist = 'n';
	var $docs_url		= 'http://devot-ee.com/add-ons/subscriber/';

	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}
	
	// Extension Functions =========================================================
	
	/**
	 * Activate Extension
	 * @access public
	 */
	public function activate_extension()
	{
		// Check for Freeform install
		if ( ! $this->EE->db->table_exists('freeform_fields'))
		{
			return FALSE;
		}

		$hooks = array(
		  'freeform_module_insert_begin' => 'insert_new_entry'
		);
		
		foreach ($hooks as $hook => $method)
		{
			$this->EE->db->insert(
				'exp_extensions',
				array(
					'class'        => ucfirst(get_class($this)),
					'method'       => $method,
					'hook'         => $hook,
					'settings'     => '',
					'priority'     => 10,
					'version'      => $this->version,
					'enabled'      => 'y'
				)
			);
		}
		
		return TRUE;
	}
	
	
	/**
	 * Disable Extension
	 * @access public
	 */
	public function disable_extension()
	{
		$this->EE->db->delete('extensions', array("class" => ucfirst(get_class($this))));
	}
	
	/**
	 * Update Extension
	 * @access public
	 */
	public function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		
		$this->EE->db->where('class', ucfirst(get_class($this)))->update('extensions', array("version" => $this->version));
	}

	// Hooks =======================================================================

	/**
	 * Sends name and email address (and optionally custom fields) to Campaign Monitor
	 *
	 * @access public
	 * @param Array $data Array of fields/values to be inserted for the Freeform form submission
	 * @return Array The same data passed into the function, unchanged
	 */
	public function insert_new_entry($data)
	{
		$subscriber_form_id_raw = $this->EE->input->post('subscriber_form_id');

		if (isset($subscriber_form_id_raw))
		{
			// Get the subscriber form ids and break em up
			if (is_array($subscriber_form_id_raw))
			{
				// Shouldn't happen, but here in case it does
				$subscriber_forms_ids = array();

				foreach ($subscriber_form_id_raw as $index => $value)
				{
					$subscriber_forms_ids = array_merge($subscriber_forms_ids, explode('|', $value));
				}
			}
			else
			{
				$subscriber_forms_ids = explode('|', $subscriber_form_id_raw);
			}
			
			// Make sure IDs are unique
			$subscriber_forms_ids = array_unique($subscriber_forms_ids, SORT_NUMERIC);

			foreach ($subscriber_forms_ids as $index => &$form_id)
			{
				// Trim the form_id since there might be a lot of extra 
				// whitespaces and newlines, also make it an integer
				$form_id = (int) trim($form_id);
				
				// Remove 0's since there are no forms with 0 as an ID
				if ($form_id === 0)
				{
					unset($subscriber_forms_ids[$index]);
				}
				
				// Make sure it's not an empty string
				if ( ! empty($form_id))
				{
					$this->_add_subscriber($form_id, $data);
				}
			}
		}
		
		// Clean up the subscriber_form_ids
		$data['subscriber_form_id'] = implode('|', $subscriber_forms_ids);
		
		// Always return data
		return $data;
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Given a form_id and the data sent from Freeform, will get the settings
	 * from a form and send it to the right provider
	 * 
	 * @param integer $form_id The id of the subscriber form we're using
	 * @param array $settings Associative array of form data
	 */
	private function _add_subscriber($form_id, $data)
	{
		$this->EE->load->model('subscriber_forms_model');
		$this->EE->load->helper('email');
		
		$settings = $this->EE->subscriber_forms_model->get(array('id' => $form_id));

		if (
				(
					$settings->method == 'everyone'
					OR $this->EE->input->get_post($settings->switch_field) == $settings->switch_value 
				)
				AND valid_email($data[$settings->email_field]) 
			) 
		{
			switch ($settings->provider)
			{
				case 'mailchimp':
					$this->_send_to_mailchimp($data, $settings);
					break;
				
				case 'campaign_monitor':
				default:
					$this->_send_to_campaign_monitor($data, $settings);
					break;
			}
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Send a subscriber to Campaign Monitor
	 * 
	 * @param array $settings Associative array of form settings
	 */
	private function _send_to_campaign_monitor($data, $settings)
	{
		// Campaign Monitor
		require_once('libraries/csrest_subscribers.php');

		$api_key     = $settings->api_key;
		$list_id     = $settings->list_id;
		
		$campaign_monitor = new CS_REST_Subscribers($list_id, $api_key);
		
		// Setup array of custom field tags and values for Campaign Monitor
		// Needs to have the CM custom field tag as the index the field's value as the array's value
		
		$custom_field_values = array();
		foreach (unserialize($settings->custom_fields) as $custom_field_settings) 
		{
			if ( ! empty($custom_field_settings['name']) 
				AND ! empty($custom_field_settings['tag']))
			{
				// If this is a multiple option field we need to split things up
				if (isset($custom_field_settings['multiple']) 
					AND $custom_field_settings['multiple'] == 'yes')
				{
					if ( ! is_array($data[$custom_field_settings['name']]))
					{
						$data[$custom_field_settings['name']] = preg_split("/(?:,|\n|\r)/", $data[$custom_field_settings['name']], -1, PREG_SPLIT_NO_EMPTY);
					}

					// Add a custom field value for each item
					foreach ($data[$custom_field_settings['name']] as $index => $value)
					{
						$custom_field_values[] = array(
							'Key' => $custom_field_settings['tag'],
							'Value' => trim($data[$custom_field_settings['name']][$index])
						);
					}
				}
				else
				{
					$custom_field_values[] = array(
						'Key' => $custom_field_settings['tag'],
						'Value' => $data[$custom_field_settings['name']]
					);
				}
			}
		}

		$result = $campaign_monitor->add(array(
			'EmailAddress' => $data[$settings->email_field],
			'Name' => $data[$settings->name_field],
			'CustomFields' => $custom_field_values,
			'Resubscribe' => true
		));

		if ( ! $result->was_successful())
		{
			$this->EE->load->library('logger');
			$message = (isset($result->response->Message)) ? $result->response->Message : $result->response['message'];
			$this->EE->logger->log_action("Subscriber Campaign Monitor API Error: ".$result->http_status_code.": ".$message);
		}
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Send a subscriber to MailChimp
	 * 
	 * @param array $settings Associative array of form settings
	 */
	private function _send_to_mailchimp($data, $settings)
	{
		require_once('libraries/MCAPI.class.php');

		$mailchimp = new MCAPI($settings->api_key);
		
		// Create the name data
		$subscriber_data = array();
		foreach (array('LNAME' => 'last_name_field', 'FNAME' => 'first_name_field') as $key => $field_name)
		{
			if (isset($settings->$field_name) AND isset($data[$settings->$field_name]))
			{
				$subscriber_data[$key] = $data[$settings->$field_name];
			}	
		}
		
		foreach (unserialize($settings->custom_fields) as $custom_field_settings) 
		{
			if ( ! empty($custom_field_settings['name']) AND ! empty($custom_field_settings['tag']))
			{
				$subscriber_data[$custom_field_settings['tag']] = $data[$custom_field_settings['name']];
			}
		}
		
		$result = $mailchimp->listSubscribe(
			$settings->list_id,
			$data[$settings->email_field],
			$subscriber_data,
			'html', // Send html email
			TRUE, // Send double opt-in email
			TRUE // Update member if exists
		);
		
		if ($mailchimp->errorCode)
		{
			$this->EE->load->library('logger');
			$this->EE->logger->log_action("Subscriber MailChimp API Error: ".$mailchimp->errorCode.": ".$mailchimp->errorMessage);
		}
	}
}
/* End of file ext.subscriber.php */
/* Location: ./system/expressionengine/third_party/subscriber/ext.subscriber.php */
