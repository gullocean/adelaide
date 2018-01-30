<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'subscriber/config.php';

/**
* Addon Module Update
*/
class Subscriber_upd
{
	var $version = SUBSCRIBER_VER;
	
	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	/**
	 * Installs the module, creating the necessary tables
	 */
	public function install()
	{
		// Check for Freeform install
		if ( ! $this->EE->db->table_exists('freeform_fields'))
		{
			// If it doesn't exist, show them the install error
			$this->EE->lang->load('subscriber', '', FALSE, TRUE, APPPATH.'third_party/subscriber/');
			show_error(lang('install_freeform'));
			return FALSE;
		}

		// Insert module information into Modules table
		$data = array(
			"module_name" => SUBSCRIBER_MACHINE, 
			"module_version" => $this->version, 
			"has_cp_backend" => "y",
			"has_publish_fields" => "n"
		);
		$this->EE->db->insert('modules', $data);
		
		// Create new database tables
		$this->EE->load->dbforge();
		
		// Forms table
		$forms_table = array(
			'id'               => array('type' => 'int', 'constraint' => '10', 'unsigned' => TRUE, 'auto_increment' => TRUE),
			'form_name'        => array('type' => 'varchar', 'constraint' => '128'),
			'provider'         => array('type' => 'varchar', 'constraint' => '40', 'default' => 'campaign_monitor'),
			'api_key'          => array('type' => 'varchar', 'constraint' => '50'),
			'list_id'          => array('type' => 'varchar', 'constraint' => '50'),
			'method'           => array('type' => 'varchar', 'constraint' => '40'),
			'name_field'       => array('type' => 'varchar', 'constraint' => '128'),
			'first_name_field' => array('type' => 'varchar', 'constraint' => '128'),
			'last_name_field'  => array('type' => 'varchar', 'constraint' => '128'),
			'email_field'      => array('type' => 'varchar', 'constraint' => '128'),
			'switch_field'     => array('type' => 'varchar', 'constraint' => '128'),
			'switch_value'     => array('type' => 'varchar', 'constraint' => '128'),
			'custom_fields'    => array('type' => 'text')
		);
		$this->EE->dbforge->add_field($forms_table);
		$this->EE->dbforge->add_key('id', TRUE);
		$this->EE->dbforge->create_table(SUBSCRIBER_DB_FORMS);
		
		// Check to see if Extension exists
		$this->_extension_check();
		
		// Add field to Freeform
		$this->_add_field();

		return TRUE;
	}

	/**
	 * Uninstalls the module, removing the database tables
	 */
	public function uninstall()
	{
		$module_id = $this->EE->db->select('module_id')->get_where('modules', array('module_name' => SUBSCRIBER_MACHINE))->row('module_id');

		$this->EE->db->where('module_id', $module_id);
		$this->EE->db->delete('module_member_groups');
		
		$this->EE->db->where('module_name', SUBSCRIBER_MACHINE);
		$this->EE->db->delete('modules');
		
		$this->EE->db->where('class', SUBSCRIBER_MACHINE);
		$this->EE->db->delete('actions');
		
		$this->EE->load->dbforge();
		$this->EE->dbforge->drop_table(SUBSCRIBER_DB_FORMS);
		
		return TRUE;
	}
	
	/**
	 * Update the module's database tables if necessary
	 * @param String $current The installed module's current version
	 */
	public function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}

		$this->EE->load->dbforge();

		// Change the length of the api_key and list_id columns
		if (version_compare($current, '3.4.2', '<'))
		{
			$column_data = array(
				'api_key' => array(
					'name' => 'api_key',
					'type' => 'varchar', 
					'constraint' => '50'
				),
				'list_id' => array(
					'name' => 'list_id',
					'type' => 'varchar',
					'constraint' => '50'
				)
			);

			$this->EE->dbforge->modify_column(SUBSCRIBER_DB_FORMS, $column_data);
		}
		
		// Add provider column to support MailChimp vs Campaign Monitor
		if (version_compare($current, '3.1', '<'))
		{
			$column_data = array(
				'provider' => array(
					'type'      => 'varchar',
					'constraint' => 40,
					'default'   => 'campaign_monitor'
				)
			);
			
			$this->EE->dbforge->add_column(SUBSCRIBER_DB_FORMS, $column_data);
		}
		
		// Add first name and last name fields for Mailchimp
		if (version_compare($current, '3.3', '<'))
		{
			$column_data = array(
				'first_name_field' => array(
					'type' => 'varchar', 
					'constraint' => '128'
				)
			);
			
			$this->EE->dbforge->add_column(SUBSCRIBER_DB_FORMS, $column_data, 'name_field');
			
			$column_data = array(
				'last_name_field' => array(
					'type' => 'varchar', 
					'constraint' => '128'
				)
			);
			
			$this->EE->dbforge->add_column(SUBSCRIBER_DB_FORMS, $column_data, 'first_name_field');
		}
		
		// 3.0.2 fixed a bug where the method column wasn't long enough for the
		// values it was supposed to contain. This increases the size of the 
		// column and also renames the broken value
		if (version_compare($current, '3.0.2', '<'))
		{
			// First increase the size of the column
			$column_data = array(
				'method' => array(
					'name' => 'method',
					'type' => 'varchar',
					'constraint' => 40
				)
			);
			
			$this->EE->dbforge->modify_column(SUBSCRIBER_DB_FORMS, $column_data);
			
			// Then correct switch_fie to switch_field
			$this->EE->db->update(
				SUBSCRIBER_DB_FORMS,
				array('method' => 'switch_field'),
				array('method' => 'switch_fie')
			);
		}
		
		// 3.0.1 added a bug fix where the freeform_entries table didn't
		// have the subscriber_form_id column
		if (version_compare($current, '3.0.1', '<'))
		{
			$this->_add_field();
		}
		
		// Update the database
		$this->EE->db->update(
			'modules', 
			array(
				"module_version" => $this->version
			), 
			array(
				'module_name' => SUBSCRIBER_MACHINE
			)
		);
		
		return TRUE;
	}
	
	/**
	 * Add Subscriber Form ID to Freeform 3.x automatically
	 */
	private function _add_field()
	{
		// Which version of Freeform are we dealing with?
		$version = $this->EE->db->select('module_version')
			->get_where('modules', array('module_name' => 'Freeform'))
			->row('module_version');
		$freeform_4 = (version_compare($version, '4.0', '>=')) ? TRUE : FALSE;

		if ( ! $freeform_4)
		{
			// Add field if necessary
			$this->EE->db->where('name', 'subscriber_form_id');
			if ($this->EE->db->count_all_results('freeform_fields') <= 0)
			{
				$field_data = array(
					'field_order' 	=> '1000',
					'field_type' 	=> 'text',
					'field_length' 	=> '150',
					'name' 			=> 'subscriber_form_id',
					'label' 		=> 'Subscriber Form ID (Do not delete)',
					'editable' 		=> 'n',
					'status' 		=> 'open'
				);
				
				$this->EE->db->insert('freeform_fields', $field_data);
			}
			
			// Add column if necessary
			$this->EE->load->dbforge();

			$column_data = array(
				'subscriber_form_id' => array(
					'type' => 'varchar',
					'constraint' => 150,
					'default' => ''
				)
			);

			$this->EE->dbforge->add_column('freeform_entries', $column_data);
		}
	}
	
	/**
	 * Check to see if you have the extension installed, if you do get the settings and roll with it
	 */
	private function _extension_check()
	{
		$former_extension_name = 'Wb_freeform_campaign_monitor_ext';
		
		$settings_query = $this->EE->db->select('settings')
			->get_where('extensions', array(
				'class' => $former_extension_name
			));
		
		// Are there any settings from the previous extension?
		if ($settings_query->num_rows() > 0)
		{
			$this->EE->load->model(SUBSCRIBER_DB_FORMS.'_model');
			
			foreach ($settings_query->result() as $row)
			{
				$extension_settings = unserialize($row->settings);

				if (is_array($extension_settings))
				{
					$custom_fields = array();

					for ($i = 1; $i <= 4; $i++)
					{ 
						$field = $extension_settings["custom_field_{$i}"];
						$tag   = $extension_settings["custom_field_{$i}_tag"];

						if ($field !== "" AND $tag !== "")
						{
							$custom_fields[] = array(
								'field' => $field,
								'tag' => $tag
							);
						}
					}

					$this->EE->subscriber_forms_model->save(array(
						'form_name'     => 'Imported Form', 
						'api_key'       => $extension_settings['api_key'],
						'list_id'       => $extension_settings['list_id'],
						'method'        => $extension_settings['add_method'],
						'name_field'    => $extension_settings['name_field'],
						'email_field'   => $extension_settings['email_field'],
						'switch_field'  => $extension_settings['switch_field'],
						'switch_value'  => $extension_settings['switch_field_value'],
						'custom_fields' => $custom_fields
					));

					// Disable extension
					$this->EE->db->delete('extensions', array("class" => $former_extension_name));
				}
			}
		}
		
		$settings_query->free_result();
	}
}

// End File upd.subscriber.php
// File Source /system/expressionengine/third_party/subscriber/upd.subscriber.php
