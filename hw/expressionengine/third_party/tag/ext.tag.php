<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Tag - Extension
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/ext.tag.php
 */

require_once 'addon_builder/extension_builder.php';

class Tag_ext extends Extension_builder_tag
{
	public $name			= "Tag";
	public $version			= "";
	public $description		= "";
	public $settings_exist	= "n";
	public $docs_url		= "http://solspace.com/docs/";
	public $required_by		= array('module');


	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		null
	 */

	public function __construct( $settings = '' )
	{
		// --------------------------------------------
		//  Required During 1.x to 2.x Upgrade
		// --------------------------------------------

		if (get_class($this) == 'Tag_submit')
		{
			return;
		}

		// --------------------------------------------
		//  Load Parent Constructor
		// --------------------------------------------

		parent::__construct();

		// --------------------------------------------
		//  Settings!
		// --------------------------------------------

		$this->settings = $settings;
	}
	//END constructor


	//required functions we aren't using
	public function activate_extension(){}
	public function disable_extension(){}
	public function update_extension(){}


	// --------------------------------------------------------------------

	/**
	 *	Parse Channel Entry Submissions
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		array
	 *	@param		string
	 *	@return		string
	 */

	public function entry_submission_end($entry_id, $meta, $data)
	{
		//we dont want this running in the CP because
		//the tab already does this work in 2.x
		if (REQ == 'CP')
		{
			return;
		}

		//in 2.x, the second argument is meta info and the third argument is data
		//so we will just merge those arrays with the data taking precedence.

		$data = array_merge($meta, $data);

		// ----------------------------------------
		//	Instantiate class
		// ----------------------------------------

		require_once $this->addon_path . 'mod.tag.php';

		$tag = new Tag();

		$tag->_parse_from_cp( $entry_id, $data );
	}
	// END entry_submission_end


	// --------------------------------------------------------------------

	/**
	 * Sessions End Hook
	 *
	 * @access	public
	 * @param	object	$incoming	incoming session object
	 * @return	[type]           [description]
	 */

	public function sessions_end( $incoming )
	{
		if ( REQ != 'CP' OR
			 ee()->input->get('ajax') != 'solspace_tag_module' OR
			 ! in_array(
				ee()->input->get('method'),
				array(
					 'tag_suggest',
					 'tag_autocomplete'
				)
			)
		)
		{
			return;
		}

		ee()->extensions->end_script = TRUE;

		// ----------------------------------------
		//	Instantiate class
		// ----------------------------------------

		if ( class_exists('Tag_cp') === FALSE )
		{
			require $this->addon_path.'mcp.tag.php';
		}

		$tag_cp = new Tag_cp_base();

		return $tag_cp->ajax();
	}
	// END sessions_end

	// Deprecated, required for updates so a PHP error is not outputted
	public function ajax($incoming) { return $this->sessions_end($incoming); }


	// --------------------------------------------------------------------

	/**
	 * AJAX Processing via cp_js_end hook
	 *
	 * @access	public
	 * @return	null
	 */

	public function cp_js_end()
	{
		$allowed = array('sync_tag_fields', 'update_tag_count');

		// Not the correct kind of request.
		if ( ! in_array(ee()->input->get('call'), $allowed))
		{
			return ee()->extensions->last_call;
		}

		require_once $this->addon_path.'mod.tag'.EXT;

		$tag = new Tag();

		$call = ee()->input->get('call');

		if ($call == 'sync_tag_fields')
		{
			$tag->sync_tag_fields();
		}
		else if ($call == 'update_tag_count')
		{
			$tag->update_tag_count();
		}
	}
	// END cp_js_end()


	// --------------------------------------------------------------------

	/**
	 * Delete Entries Start Hook
	 *
	 * @access	public
	 * @return	bool
	 */

	public function delete_entries_start()
	{
		if ( ! isset($_POST['delete']) OR ! is_array($_POST['delete']))
		{
			return;
		}

		require_once $this->addon_path.'mod.tag'.EXT;

		$tag = new Tag();

		return $tag->delete( $_POST['delete'] );
	}
	//delete_entries_start
}
//END class