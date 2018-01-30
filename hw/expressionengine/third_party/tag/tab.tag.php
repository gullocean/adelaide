<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Tag - Publish Tab
 *
 * Handles the adding of Tabs to the Publish Page (legacy).
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/tab.tag.php
 */

require_once 'addon_builder/module_builder.php';

class Tag_tab extends Module_builder_tag
{
	// --------------------------------------------------------------------

	/**
	 *	Constructor
	 *
	 *	@access		public
	 *	@return		null
	 */

	public function __construct()
	{
		parent::__construct('tag');
	}
	/* END constructor */

	// --------------------------------------------------------------------

	/**
	 *	Publish Tabs
	 *
	 *	Creates the fields that will be displayed on the Publish page for EE 2.x
	 *
	 *	@access		public
	 *	@param		integer
	 *	@param		integer
	 *	@return		array
	 */

	public function publish_tabs($channel_id, $entry_id = '')
	{
		$settings = array();

		if (REQ != 'CP')
		{
			return $settings;
		}

		// @bugfix - EE 2.x on submit of an entry calls this method with incorrect arguments
		if (is_array($channel_id))
		{
			$entry_id	= $channel_id[1];
			$channel_id	= $channel_id[0];
		}

		// --------------------------------------------
        //  Delimiter
        // --------------------------------------------

		$query	= ee()->db->query(
			"SELECT tag_preference_value, tag_preference_name
			 FROM 	exp_tag_preferences
			 WHERE 	tag_preference_name
			 IN 	('" . ee()->db->escape_str($channel_id) . "_publish_tab_label')
			 AND 	site_id = '".ee()->db->escape_str( ee()->config->item('site_id') ) . "'"
		);

		foreach($query->result_array() as $row)
		{
			if ($row['tag_preference_name'] == $channel_id.'_publish_tab_label')
			{
				$tag_name = $row['tag_preference_value'];
			}
			else
			{
				${$row['tag_preference_name']} = $row['tag_preference_value'];
			}
		}

		// --------------------------------------------
        //  Do we have a Publish Tab for this Channel?
        // --------------------------------------------

        if ( empty($tag_name))
        {
        	return array();
        }

		// --------------------------------------------
        //  Build Fields
        // --------------------------------------------

		$settings[] = array(
			'field_id'				=> 'solspace_tag_entry',
			'field_name'			=> 'default',
			'field_label'			=> lang('tag_field'),
			'field_required' 		=> 'n',
			'field_instructions' 	=> '',
			'field_is_hidden'		=> 'n',
			'field_data'			=> '',
			'field_fmt'				=> '',
			'field_show_fmt'		=> 'n',
			'field_fmt_options'		=> array(),
			'field_type' 			=> 'tag',
		);

		return $settings;
	}
	/* END publish_tabs() */

	// --------------------------------------------------------------------

	/**
	 *	Validate Submitted Publish data
	 *
	 *	Allows you to validate the data after the publish form has been submitted but before any
	 *	additions to the database. Returns FALSE if there are no errors, an array of errors otherwise.
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		bool|array
	 */

	public function validate_publish($params)
	{
		return FALSE;
	}
	/* END validate_publish() */


	// --------------------------------------------------------------------

	/**
	 *	Insert Publish Form Data
	 *
	 *	Allows the insertion of data after the core insert/update has been done, thus making
	 *	available the current $entry_id. Returns nothing.
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		null
	 */

	public function publish_data_db($params)
	{
		if ( ! isset($params['data']['tag_f']))
		{
			return;
		}

		if ( ! class_exists('Tag'))
		{
			require_once $this->addon_path.'mod.tag.php';
		}

		$TAG = new Tag();

		$TAG->channel_id			= $params['meta']['channel_id'];
		$TAG->site_id				= $params['meta']['site_id'];
		$TAG->entry_id				= $params['entry_id'];
		$TAG->from_ft				= TRUE;
		$TAG->str					= $params['data']['tag_f'];
		$TAG->separator_override 	= 'newline';
		$TAG->tag_group_id			= 1;
		$TAG->type					= 'channel';

		$TAG->parse();
	}
	/* END publish_data_db() */

	// --------------------------------------------------------------------

	/**
	 *	Entry Delete
	 *
	 *	Called near the end of the entry delete function, this allows you to sync your records if
	 *	any are tied to channel entry_ids.
	 *
	 *	@access		public
	 *	@param		array
	 *	@return		null
	 */

	public function publish_data_delete_db($params)
	{
		require_once $this->addon_path.'mod.tag'.EXT;

		$TAG = new Tag();

		return $TAG->delete( $params['entry_ids'] );
	}
	/* publish_data_delete_db() */

}
/* END Tag_tab CLASS */

/* End of file tab.tag.php */
/* Location: ./system/expressionengine/third_party/modules/tag/tab.tag.php */