<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include_once dirname(__FILE__).'/config.php';

/**
 * Install / Uninstall and updates the modules
 *
 * @package			DevDemon_ChannelImages
 * @author			DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright 		Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license 		http://www.devdemon.com/license/
 * @link			http://www.devdemon.com
 * @see				http://expressionengine.com/user_guide/development/module_tutorial.html#update_file
 */
class Channel_images_upd
{
	/**
	 * Module version
	 *
	 * @var string
	 * @access public
	 */
	public $version		=	CHANNEL_IMAGES_VERSION;

	/**
	 * Module Short Name
	 *
	 * @var string
	 * @access private
	 */
	private $module_name	=	CHANNEL_IMAGES_CLASS_NAME;

	/**
	 * Has Control Panel Backend?
	 *
	 * @var string
	 * @access private
	 */
	private $has_cp_backend = 'y';

	/**
	 * Has Publish Fields?
	 *
	 * @var string
	 * @access private
	 */
	private $has_publish_fields = 'n';


	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		ee()->load->add_package_path(PATH_THIRD . 'channel_images/');
		ee()->config->load('ci_config');
	}

	// ********************************************************************************* //

	/**
	 * Installs the module
	 *
	 * Installs the module, adding a record to the exp_modules table,
	 * creates and populates and necessary database tables,
	 * adds any necessary records to the exp_actions table,
	 * and if custom tabs are to be used, adds those fields to any saved publish layouts
	 *
	 * @access public
	 * @return boolean
	 **/
	public function install()
	{
		// Load dbforge
		ee()->load->dbforge();

        //----------------------------------------
        // EXP_MODULES
        //----------------------------------------
        ee()->db->set('module_name', ucfirst($this->module_name));
        ee()->db->set('module_version', $this->version);
        ee()->db->set('has_cp_backend', $this->has_cp_backend);
        ee()->db->set('has_publish_fields', $this->has_publish_fields);
        ee()->db->insert('modules');

        //----------------------------------------
        // Actions
        //----------------------------------------
        $fields = ee()->db->list_fields('exp_actions');
        $csrfColumnExists = in_array('csrf_exempt', $fields);

        ee()->db->set('class', ucfirst($this->module_name));
        if ($csrfColumnExists) ee()->db->set('csrf_exempt', 1);
        ee()->db->set('method', $this->module_name . '_router');
        ee()->db->insert('actions');

        ee()->db->set('class', ucfirst($this->module_name));
        ee()->db->set('method', 'locked_image_url');
        ee()->db->insert('actions');

        ee()->db->set('class', ucfirst($this->module_name));
        ee()->db->set('method', 'simple_image_url');
        ee()->db->insert('actions');

        //----------------------------------------
        // EXP_MODULES
        // The settings column, Ellislab should have put this one in long ago.
        // No need for a seperate preferences table for each module.
        //----------------------------------------
        if (ee()->db->field_exists('settings', 'modules') == false) {
            ee()->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
        }

		//----------------------------------------
		// EXP_CHANNEL_IMAGES
		//----------------------------------------
		$ci = array(
			'image_id' 		=> array('type' => 'INT',		'unsigned' => TRUE,	'auto_increment' => TRUE),
			'site_id'		=> array('type' => 'TINYINT',	'unsigned' => TRUE,	'default' => 1),
			'entry_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'field_id'		=> array('type' => 'MEDIUMINT',	'unsigned' => TRUE, 'default' => 0),
			'channel_id'	=> array('type' => 'TINYINT',	'unsigned' => TRUE, 'default' => 0),
			'member_id'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'is_draft'		=> array('type' => 'TINYINT',	'unsigned' => TRUE, 'default' => 0),
			'link_image_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'link_entry_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'link_channel_id'=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'link_field_id'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'upload_date'	=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'cover'			=> array('type' => 'TINYINT',	'constraint' => '1', 'unsigned' => TRUE, 'default' => 0),
			'image_order'	=> array('type' => 'SMALLINT',	'unsigned' => TRUE, 'default' => 1),
			'filename'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'extension'		=> array('type' => 'VARCHAR',	'constraint' => '20', 'default' => ''),
			'filesize'		=> array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'mime'			=> array('type' => 'VARCHAR',	'constraint' => '20', 'default' => ''),
			'width'			=> array('type' => 'SMALLINT',	'default' => 0),
			'height'		=> array('type' => 'SMALLINT',	'default' => 0),
			'title'			=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'url_title'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'description'	=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'category'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cifield_1'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cifield_2'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cifield_3'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cifield_4'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'cifield_5'		=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'sizes_metadata'=> array('type' => 'VARCHAR',	'constraint' => '250', 'default' => ''),
			'iptc'			=> array('type' => 'TEXT'),
			'exif'			=> array('type' => 'TEXT'),
			'xmp'			=> array('type' => 'TEXT'),
		);

		ee()->dbforge->add_field($ci);
		ee()->dbforge->add_key('image_id', TRUE);
		ee()->dbforge->add_key('entry_id');
		ee()->dbforge->create_table('channel_images', TRUE);


		// Do we need to enable the extension
        //if ($this->uses_extension === TRUE) $this->extension_handler('enable');

		return TRUE;
	}

	// ********************************************************************************* //

	/**
	 * Uninstalls the module
	 *
	 * @access public
	 * @return Boolean FALSE if uninstall failed, TRUE if it was successful
	 **/
	public function uninstall()
	{
		// Load dbforge
		ee()->load->dbforge();

		// Remove
		ee()->dbforge->drop_table('channel_images');
		ee()->db->where('module_name', ucfirst($this->module_name));
		ee()->db->delete('modules');
		ee()->db->where('class', ucfirst($this->module_name));
		ee()->db->delete('actions');

		// ee()->cp->delete_layout_tabs($this->tabs(), 'tagger');

		return TRUE;
	}

	// ********************************************************************************* //

	/**
	 * Updates the module
	 *
	 * This function is checked on any visit to the module's control panel,
	 * and compares the current version number in the file to
	 * the recorded version in the database.
	 * This allows you to easily make database or
	 * other changes as new versions of the module come out.
	 *
	 * @access public
	 * @return Boolean FALSE if no update is necessary, TRUE if it is.
	 **/
	public function update($current = '')
	{
		if (ee()->db->field_exists('csrf_exempt', 'exp_actions') === true) {
			ee()->db->set('csrf_exempt', 1);
			ee()->db->where('class', ucfirst($this->module_name));
			ee()->db->update('exp_actions');
		}

		// Are they the same?
		if (version_compare($current, $this->version) >= 0) {
			return FALSE;
		}

		$current = str_replace('.', '', $current);

		// Two Digits? (needs to be 3)
		if (strlen($current) == 2) $current .= '0';

		$update_dir = PATH_THIRD.strtolower($this->module_name).'/updates/';

		// Does our folder exist?
		if (@is_dir($update_dir) === TRUE)
		{
			// Loop over all files
			$files = @scandir($update_dir);

			if (is_array($files) == TRUE)
			{
				foreach ($files as $file)
				{
					if ($file == '.' OR $file == '..' OR strtolower($file) == '.ds_store') continue;

					// Get the version number
					$ver = substr($file, 0, -4);

					// We only want greater ones
					if ($current >= $ver) continue;

					require $update_dir . $file;
					$class = 'ChannelImagesUpdate_' . $ver;
					$UPD = new $class();
					$UPD->do_update();
				}
			}
		}

		// Upgrade The Module
		ee()->db->set('module_version', $this->version);
		ee()->db->where('module_name', ucfirst($this->module_name));
		ee()->db->update('exp_modules');

		return TRUE;
	}

} // END CLASS

/* End of file upd.channel_images.php */
/* Location: ./system/expressionengine/third_party/channel_images/upd.channel_images.php */