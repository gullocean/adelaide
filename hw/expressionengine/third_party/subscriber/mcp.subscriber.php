<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'subscriber/config.php';

/**
* Addon Control Panel
*/
class Subscriber_mcp
{
	function __construct()
	{
		// Setup the base url to the module
		$this->form_base = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=subscriber';
		$this->base = BASE.AMP.$this->form_base;

		ee()->cp->set_right_nav(array(
			'subscriber_dashboard' => $this->base,
			'subscriber_new_form'  => $this->base.AMP.'method=view'
		));

		ee()->load->library('table');
		ee()->load->model('subscriber_forms_model');

		$this->_include_theme_css('styles/global.css');
	}

	// -----------------------------------------------------------------------------

	public function index()
	{
		ee()->view->cp_page_title = lang('subscriber_module_name');

		$data['base'] = $this->base;
		return ee()->load->view('index', $data, TRUE);
	}

	// -----------------------------------------------------------------------------

	public function view()
	{
		ee()->javascript->set_global(array(
			'subscriber.lang.api_key_missing'      => lang('api_key_missing'),
			'subscriber.lang.list_id_missing'      => lang('list_id_missing'),
			'subscriber.lang.switch_field_missing' => lang('switch_field_missing'),
		));

		$data['base']      = $this->base;
		$data['form_base'] = $this->form_base;

		ee()->cp->set_breadcrumb($this->base, lang('subscriber_module_name'));

		$this->_include_theme_js('scripts/settings.js');

		if ($form_id = ee()->input->get('form_id'))
		{
			$data['form_id'] = $form_id;
			$data['form'] = get_object_vars(ee()->subscriber_forms_model->get(array('id' => $form_id)));
			ee()->view->cp_page_title = $data['form']['form_name'];
		}
		else
		{
			$data['form'] = array('provider' => 'campaign_monitor');
			ee()->view->cp_page_title = lang('subscriber_new_form');
		}

		$data = $this->_build_form($data);

		return ee()->load->view('settings', $data, TRUE);
	}

	private function _build_form($data = array())
	{
		if (count($data) AND isset($data['form']))
		{
			$form = $data['form'];
		}
		else
		{
			$form = array();
		}

		// Main Settings
		$form_name        = $this->_define_value($form, 'form_name');
		$provider         = $this->_define_value($form, 'provider', 'campaign_monitor');
		$api_key          = $this->_define_value($form, 'api_key');
		$list_id          = $this->_define_value($form, 'list_id');
		$method           = $this->_define_value($form, 'method', 'everyone');
		$name_field       = $this->_define_value($form, 'name_field', 'name');
		$first_name_field = $this->_define_value($form, 'first_name_field', 'first_name');
		$last_name_field  = $this->_define_value($form, 'last_name_field', 'last_name');
		$email_field      = $this->_define_value($form, 'email_field', 'email');

		// Switch Field Settings
		$switch_field = $this->_define_value($form, 'switch_field');
		$switch_value = $this->_define_value($form, 'switch_value');

		// Build method radio buttons
		$method_field = $this->_build_radio('method', $method, array(
			'method_everyone' => 'everyone',
			'method_switch'   => 'switch_field'
		));

		// Build provider radio buttons
		$provider_field = $this->_build_radio(
			'provider',
			$provider,
			array(
				'provider_campaign_monitor' => 'campaign_monitor',
				'provider_mailchimp'        => 'mailchimp'
			)
		);

		// Create the Main Settings' labels and inputs
		$data['settings_main'] = array(
			'form_name'        => form_input('form_name', $form_name, 'id="form_name"'),
			'provider'         => $provider_field,
			'api_key'          => form_input('api_key', $api_key, 'id="api_key"'),
			'list_id'          => form_input('list_id', $list_id, 'id="list_id"'),
			'method'           => $method_field,
			'name_field'       => form_input('name_field', $name_field, 'id="name_field" class="campaignmonitor"'),
			'first_name_field' => form_input('first_name_field', $first_name_field, 'id="first_name_field" class="mailchimp"'),
			'last_name_field'  => form_input('last_name_field', $last_name_field, 'id="last_name_field" class="mailchimp"'),
			'email_field'      => form_input('email_field', $email_field, 'id="email_field"')
		);

		// Create the Switch Settings' labels and inputs
		$data['settings_switch'] = array(
			'switch_field' => form_input('switch_field', $switch_field, 'id="switch_field"'),
			'switch_value' => form_input('switch_value', $switch_value, 'id="switch_value"')
		);

		// Build custom field labels and inputs using the array of custom field values
		if (isset($form['custom_fields']) AND count($form['custom_fields']))
		{
			$data['custom_fields'] = unserialize($form['custom_fields']);

			// Get rid of empty fields
			foreach ($data['custom_fields'] as $index => $custom_field)
			{
				if ($custom_field['name'] == '' OR $custom_field['tag'] == '')
				{
					unset($data['custom_fields'][$index]);
				}

				// Set multiple if it's not set
				if ( ! isset($custom_field['multiple']))
				{
					$data['custom_fields'][$index]['multiple'] = FALSE;
				}
			}
		}

		return $data;
	}

	/**
	 * Builds a set of radio buttons given the field name, current value and desired fields
	 *
	 * @param string $field_name Value to place in the radio's name field
	 * @param string $current_field_value The input field's current value
	 * @param array $fields Associative array containing the field's ID as the
	 * 	key and the value for the value
	 * @return string String for the radio group
	 */
	public function _build_radio($field_name, $current_field_value, $fields)
	{
		$radio = '';
		$values = array();
		$count = 0;

		// Determine Checked Status of Add Method
		foreach ($fields as $field_id => $field_value)
		{
			$values[$field_id] = (isset($current_field_value) AND $current_field_value == $field_value) ? TRUE : FALSE;
			if ($count != 0)
			{
				$radio .= NBS.NBS.NBS.NBS;
			}

			$radio .= form_radio(array(
				'name'    => $field_name,
				'value'   => $field_value,
				'id'      => $field_id,
				'checked' => $values[$field_id]
			));
			$radio .= NBS;
			$radio .= form_label(lang($field_value), $field_id);

			$count += 1;
		}

		return $radio;
	}


	/**
	 * Returns the value of an input using a data array first, then post value and then a default value
	 *
	 * @access private
	 * @param Array $data The data array from the settings form
	 * @param String $input_name The name of the input field
	 * @param String $default_value The value to default to (defaults to an empty string)
	 * @return String The correct value for the input field
	 */
	private function _define_value($data, $input_name, $default_value = '')
	{
		if (isset($data[$input_name]))
		{
			return $data[$input_name];
		}
		else if ($post_value = ee()->input->post($input_name))
		{
			return $post_value;
		}
		else
		{
			return $default_value;
		}
	}

	// -----------------------------------------------------------------------------

	public function save()
	{
		$form_data = array(
			'form_name'        => ee()->input->post('form_name', TRUE),
			'provider'         => ee()->input->post('provider', TRUE),
			'api_key'          => ee()->input->post('api_key', TRUE),
			'list_id'          => ee()->input->post('list_id', TRUE),
			'method'           => ee()->input->post('method', TRUE),
			'name_field'       => ee()->input->post('name_field', TRUE),
			'first_name_field' => ee()->input->post('first_name_field', TRUE),
			'last_name_field'  => ee()->input->post('last_name_field', TRUE),
			'email_field'      => ee()->input->post('email_field', TRUE),
			'switch_field'     => ee()->input->post('switch_field', TRUE),
			'switch_value'     => ee()->input->post('switch_value', TRUE),
		);

		// Deal with empty custom_fields
		$custom_fields = ee()->input->post('custom_fields');
		foreach ($custom_fields as $field_index => $field_data)
		{
			if (empty($field_data['name']) AND empty($field_data['tag']))
			{
				unset($custom_fields[$field_index]);
			}
		}
		$form_data['custom_fields'] = serialize($custom_fields);

		if ($form_id = ee()->input->post('form_id'))
		{
			$form_data['id'] = $form_id;
		}

		ee()->subscriber_forms_model->save($form_data);

		ee()->session->set_flashdata(array(
			'message_success' => lang('preferences_updated')
		));

		ee()->functions->redirect($this->base);
	}

	// -----------------------------------------------------------------------------

	public function duplicate()
	{
		if ($form_id = ee()->input->get('form_id'))
		{
			// Get the current form's settings
			$form = ee()->subscriber_forms_model->get(array('id' => $form_id), NULL, NULL, TRUE);

			// Remove the form_id
			unset($form['id']);

			// Add it back
			$new_form_id = ee()->subscriber_forms_model->save($form);

			// Redirect
			ee()->functions->redirect($this->base.AMP.'method=view'.AMP.'form_id='.$new_form_id);
		}
	}

	// -----------------------------------------------------------------------------

	public function delete()
	{
		if ($form_id = ee()->input->get('form_id'))
		{
			$form = ee()->subscriber_forms_model->get(array('id' => $form_id));

			ee()->cp->set_breadcrumb($this->base, lang('subscriber_module_name'));
			ee()->view->cp_page_title = lang('form_delete_left').$form->form_name.lang('form_delete_right');

			$data['form_id'] = $form_id;
			$data['form_base'] = $this->form_base;

			return ee()->load->view('delete', $data, TRUE);
		}
		else if ($form_id = ee()->input->post('form_id'))
		{
			ee()->subscriber_forms_model->delete(array(
				'id' => $form_id
			));

			ee()->functions->redirect($this->base);
		}
	}

	// -----------------------------------------------------------------------------

	/**
	 * Theme URL
	 */
	private function _theme_url()
	{
		if (! isset($this->cache['theme_url']))
		{
			$theme_folder_url = ee()->config->item('theme_folder_url');
			if (substr($theme_folder_url, -1) != '/') $theme_folder_url .= '/';
			$this->cache['theme_url'] = $theme_folder_url.'third_party/subscriber/';
		}

		return $this->cache['theme_url'];
	}

	/**
	 * Include Theme CSS
	 */
	private function _include_theme_css($file)
	{
		ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->_theme_url().$file.'" />');
	}

	/**
	 * Include Theme JS
	 */
	private function _include_theme_js($file)
	{
		ee()->cp->add_to_foot('<script type="text/javascript" src="'.$this->_theme_url().$file.'"></script>');
	}

	/**
	 * Insert JS
	 */
	private function _insert_js($js)
	{
		ee()->cp->add_to_foot('<script type="text/javascript">'.$js.'</script>');
	}


}

// End File mcp.subscriber.php
// File Source /system/expressionengine/third_party/subscriber/mcp.subscriber.php