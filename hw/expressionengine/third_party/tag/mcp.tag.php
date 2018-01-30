<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Tag - Control Panel
 *
 * The handler class for all control panel requests.
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/mcp.tag.php
 */

if ( ! class_exists('Module_builder_tag'))
{
	require_once 'addon_builder/module_builder.php';
}

class Tag_mcp extends Module_builder_tag
{
	private $row_limit		= 50;

	private $member_id		= 0;
	private $entry_id		= '';
	private $pref_id		= '';
	private $tag_id			= '';

	//changed in the constructor to current site
	private $clean_site_id	= 1;

	//private

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct( $switch = TRUE )
	{
		parent::__construct();

		if ((bool) $switch === FALSE) return; // Install or Uninstall Request

		//	----------------------------------------
		//	 UTF-8
		//	----------------------------------------

		if (function_exists ( 'mb_internal_encoding'))
		{
			mb_internal_encoding('UTF-8');
		}

		// --------------------------------------------
		//  Module Menu Items
		// --------------------------------------------

		$menu	= array(
			'module_manage_tags'		=> array(
				'link'  => $this->base,
				'title' => lang('manage_tags')
			),
			'module_manage_bad_tags'	=> array(
				'link'  => $this->base.'&method=manage_bad_tags',
				'title' => lang('manage_bad_tags')
			),
			'module_tag_groups'			=> array(
				'link'  => $this->base.'&method=tag_groups',
				'title' => lang('tag_groups')
			),
			'module_utilities'				=> array(
				'link'  => $this->base.'&method=utilities',
				'title' => lang('utilities')
			),
			'module_preferences'		=> array(
				'link'  => $this->base.'&method=preferences',
				'title' => lang('tag_preferences')
			),
			'module_documentation'		=> array(
				'link'  => TAG_DOCS_URL,
				'title' => lang('online_documentation'),
				'new_window' => TRUE
			),
		);

		$this->cached_vars['lang_module_version'] 	= lang('tag_module_version');
		$this->cached_vars['module_version'] 		= TAG_VERSION;
		$this->cached_vars['module_menu_highlight'] = 'module_manage_tags';
		$this->cached_vars['module_menu'] 			= $menu;
		$this->cached_vars['inner_nav_links'] 		= array();

		//needed for header.html file views
		$this->cached_vars['js_magic_checkboxes']	= $this->js_magic_checkboxes();

		// --------------------------------------------
		//  Sites
		// --------------------------------------------

		$this->cached_vars['sites']	= array();

		foreach($this->data->get_sites() as $site_id => $site_label)
		{
			$this->cached_vars['sites'][$site_id] = $site_label;
		}

		//--------------------------------------------
		//	just a helper
		//--------------------------------------------

		$this->clean_site_id = ee()->db->escape_str(ee()->config->item('site_id'));

		// -------------------------------------
		//  need a special case for our beta versions
		//  this shouldn't be hit for normal EE updates
		// -------------------------------------

		if(
			(
				$this->version_compare(
					$this->database_version(),
					'<',
					TAG_VERSION
				)
				AND
				//EE checks like this instead of version_compare
				! (TAG_VERSION > $this->database_version())
			)
			OR
			! $this->extensions_enabled()
		)
		{
			// For EE 2.x, we need to redirect the request to Update Routine
			$_GET['method'] = 'tag_module_update';
		}

	}

	// END __construct()


	// --------------------------------------------------------------------


	public function utilities ($message = '')
	{
		//--------------------------------------------
		//	message
		//--------------------------------------------

		if ($message == '' AND ee()->input->get_post('msg') !== FALSE)
		{
			$message = lang(ee()->input->get_post('msg'));
		}

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  lang
		//--------------------------------------

		$lvars = array(
			'harvest_tags',
			'tag_recount',
			'tag_sync',
			'tag_field_sync_subtext',
			'tag_field_sync',
		);

		foreach($lvars as $var)
		{
			//replacing all spaces with non-breaking just to prevent BS
			$this->cached_vars['lang_' . $var] = str_replace(' ', NBS, lang($var));
		}

		$this->cached_vars['tag_recount_url'] = $this->base . AMP . 'method=update_tag_counts';
		$this->cached_vars['harvest_url'] = $this->base . AMP . 'method=harvest';
		$this->cached_vars['tag_field_sync_url'] = $this->base . AMP . 'method=tag_field_sync';

		// -------------------------------------
		//	highlight
		// -------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_utilities';

		$this->add_crumb(lang('utilities'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('utilities.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}

	// --------------------------------------------------------------------

	/**
	 *	The Main CP Index Page
	 *
	 *	@access		public
	 *	@param		string		$message - That little message display thingy
	 *	@return		string
	 */

	public function index($message = '')
	{
		//--------------------------------------------
		//	message
		//--------------------------------------------

		if ($message == '' AND ee()->input->get_post('msg') !== FALSE)
		{
			$message = lang(ee()->input->get_post('msg'));
		}

		//--------------------------------------------
		//	tag group?
		//--------------------------------------------

		$tags_in_group = FALSE;

		if ( ! in_array(ee()->input->get_post('tag_group_id'), array(FALSE, '', 0, '0'), TRUE) AND
			is_numeric(ee()->input->get_post('tag_group_id'))
		 )
		{
			//cached result
			$tags_in_group = $this->data->get_tag_ids_by_group_id(
				ee()->input->get_post('tag_group_id')
			);

			//default name in case people are playing around or some crap gets force deleted.
			$tag_group_name = lang('undefined_tag_group');

			if ($tags_in_group)
			{
				$tqn_query = ee()->db->query(
					"SELECT tag_group_name
					 FROM	exp_tag_groups
					 WHERE	tag_group_id = " . ee()->db->escape_str(ee()->input->get_post('tag_group_id'))
				);

				if ($tqn_query->num_rows() > 0)
				{
					$tag_group_name = $tqn_query->row('tag_group_name');
				}
			}

			$this->cached_vars['tag_group_name'] = $tag_group_name;
		}

		$this->cached_vars['tags_in_group'] = $tags_in_group;

		//----------------------------------------
		//  Queries for Channel Entries Tagged
		//----------------------------------------

		$this->cached_vars['percent_' . $this->sc->channel . '_entries_tagged'] = 0;

		//--------------------------------------------
		//	total tags
		//--------------------------------------------

		$sql = "SELECT 	COUNT(*) AS count
				FROM 	exp_tag_tags
				WHERE 	site_id = {$this->clean_site_id}";

		if ($tags_in_group)
		{
			$sql .=	" AND tag_id IN (" . implode(',', $tags_in_group) . ") ";
		}

		$query = ee()->db->query($sql);

		$this->cached_vars['total_tags'] = $query->row('count');

		//--------------------------------------------
		//	total entries tagged
		//--------------------------------------------

		$sql = "SELECT 	COUNT(DISTINCT entry_id) AS count
				FROM 	exp_tag_entries
				WHERE 	type = 'channel'
				AND 	site_id = {$this->clean_site_id}";

		if ($tags_in_group)
		{
			$sql .=	" AND tag_group_id = " . ee()->db->escape_str(ee()->input->get_post('tag_group_id'));
		}

		$query = ee()->db->query($sql);

		$this->cached_vars['total_' . $this->sc->channel . '_entries_tagged'] = (
			$query->num_rows() == 0
		) ? 0 : $query->row('count');

		//--------------------------------------------
		//	%
		//--------------------------------------------

		$query = ee()->db->query(
			"SELECT COUNT(*) AS count
			 FROM 	{$this->sc->db->channel_titles}
			 WHERE 	site_id = {$this->clean_site_id}"
		);

		if ( $query->row('count') != 0 )
		{
			$this->cached_vars['percent_' . $this->sc->channel . '_entries_tagged'] = round(
				$this->cached_vars['total_' . $this->sc->channel . '_entries_tagged'] / $query->row('count') * 100,
				2
			);
		}

		// --------------------------------------------
		//  Top 5 Tags
		// --------------------------------------------

		$sql = "SELECT 		t.tag_name, t.total_entries
				FROM 		exp_tag_tags t
				WHERE 		site_id = {$this->clean_site_id}";

		if ($tags_in_group)
		{
			$sql .=	" AND t.tag_id IN (" . implode(',', $tags_in_group) . ") ";
		}

		$sql .= " ORDER BY 	t.total_entries DESC
				  LIMIT 		5";

		$top5	= ee()->db->query($sql);

		$this->cached_vars['top_five_tags'] = array();

		foreach ( $top5->result_array() as $row )
		{
			$this->cached_vars['top_five_tags'][$row['tag_name']] = $row['total_entries'];
		}

		//	----------------------------------------
		//	Browse by First Character
		//	----------------------------------------

		$sql = "SELECT 		tag_alpha, COUNT(tag_alpha) AS count
				FROM 		exp_tag_tags
				WHERE 		site_id = {$this->clean_site_id}";

		if ($tags_in_group)
		{
			$sql .=	" AND tag_id IN (" . implode(',', $tags_in_group) . ") ";
		}

		$sql .= " GROUP BY 	tag_alpha";

		$query = ee()->db->query($sql);

		$this->cached_vars['tags_by_alpha'] = array();

		if ( $query->num_rows() > 0 )
		{
			foreach ( $query->result_array() as $row )
			{
				$this->cached_vars['tags_by_alpha'][$row['tag_alpha']] = $row['count'];
			}
		}

		//--------------------------------------------
		//	base alpha url (ee 2.x only)
		//--------------------------------------------

		$this->cached_vars['base_alpha_url'] 	= $this->base . (
			($tags_in_group) ? AMP . 'tag_group_id=' . ee()->input->get_post('tag_group_id') : ''
		);

		// --------------------------------------------
		//  Sets tags to $this->cached_vars['tags']
		// --------------------------------------------

		$this->_tags();

		//--------------------------------------------
		//	adjust tags
		//--------------------------------------------

		foreach ($this->cached_vars['tags'] as $tag_id => $data)
		{
			$item 							= $data;

			$item['edit_tag_url'] 			= $this->base .
												AMP . 'method=edit_tag_form' .
												AMP . 'tag_id=' . $tag_id;

			$item['view_entries_url'] 		= $this->base .
												AMP . 'method=channel_entries_by_tag' .
												AMP . 'tag_id=' . $tag_id;

			$item['bad_tag_url'] 			= $this->base .
												AMP . 'method=bad_tag' .
												AMP . 'tag_name=' .
												urlencode(base64_encode($data['tag_name']));

			$item['formatted_entry_date'] 	= $this->human_time($data['entry_date']);

			$item['formatted_edit_date']  	= $this->human_time($data['edit_date']);

			$this->cached_vars['tags'][$tag_id] = $item;
		}

		//--------------------------------------------
		//	others
		//--------------------------------------------

		$this->cached_vars['form_url'] 	= $this->base . AMP . 'method=manage_tags_process';

		//----------------------------------------
		//	 Build page
		//----------------------------------------

		$this->add_crumb(lang('manage_tags'));

		$this->cached_vars['message'] 	= $message;

		//--------------------------------------
		//  lang
		//--------------------------------------

		$lvars = array(
			'suggest_tags',
			'top_tags',
			'tag_id',
			'edit',
			'tag_entries',
			'count',
			'screen_name',
			'entered_date',
			'edit_date',
			'bad_tag',
			'all_tags',
			'no_tags_found',
			'delete',
			'edit_tag',
			'add_bad_tag',
			'tag_mark_as_bad_',
			'tag_view_entries',
			'edit_entries_for_',
			'search',
			'tag_group',
			'viewing_tags_for_group',
			'view_tags_in_all_groups'
		);

		foreach($lvars as $var)
		{
			//replacing all spaces with non-breaking just to prevent BS
			$this->cached_vars['lang_' . $var] = str_replace(' ', NBS, lang($var));
		}

		$this->cached_vars['lang_search_tags'] = lang('search_tags');

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('home.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END index()


	// --------------------------------------------------------------------

	/**
	 * tag_groups MCP page
	 *
	 * @access	public
	 * @param	message	flashdata message for CP
	 * @return	string	output html
	 */

	public function tag_groups($message = '')
	{
		if ($message == '' AND ee()->input->get_post('msg') !== FALSE)
		{
			$message = lang(ee()->input->get_post('msg'));
		}

		//----------------------------------------
		//	 message links
		//----------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_tag_groups';

		$this->add_crumb(lang('tag_groups'));

		$this->cached_vars['message'] 	= $message;

		$this->add_right_link(lang('create_tag_group'), $this->base . AMP . 'method=edit_tag_group_form');

		//--------------------------------------
		//  lang
		//--------------------------------------

		$lvars = array(
			'delete_tag_groups',
			'tag_groups',
			'tag_group_id',
			'tag_group_name',
			'tag_group_short_name',
			'locked',
			'total_tags_in_group',
			'view_group_tags',
			'view_tags_in_group',
			'delete',
			'locked_tag_group_description'
		);

		foreach($lvars as $var)
		{
			//replacing all spaces with non-breaking just to prevent BS
			$this->cached_vars['lang_' . $var] = str_replace(' ', NBS, lang($var));
		}

		//--------------------------------------------
		//	get tag groups that arent being used
		//--------------------------------------------

		$ug_query = ee()->db->query(
			"SELECT field_settings
			 FROM	exp_channel_fields
			 WHERE	field_type = 'tag'"
		);

		//1 is always locked as its default
		$used_groups = array('1' => TRUE);

		if ($ug_query->num_rows() > 0)
		{
			foreach ($ug_query->result_array() as $row)
			{
				$data = unserialize(base64_decode($row['field_settings']));

				if (isset($data['tag_group']) AND $data['tag_group'] != 1)
				{
					$used_groups[$data['tag_group']] = TRUE;
				}
			}
		}

		//--------------------------------------------
		//	get all groups
		//--------------------------------------------

		$tg_query = ee()->db->query(
			"SELECT *
			 FROM	exp_tag_groups"
		);

		$tag_groups = array();

		if ($tg_query->num_rows() > 0)
		{
			foreach ($tg_query->result_array() as $row)
			{
				$row['deletable'] 				= ! isset($used_groups[$row['tag_group_id']]);
				$row['tag_group_edit_url']		= $this->base . AMP . 'method=edit_tag_group_form' .
																AMP . 'tag_group_id=' .
																	$row['tag_group_id'];
				$row['view_group_tags_url']		= $this->base . AMP . 'method=index' .
																AMP . 'tag_group_id=' .
																	$row['tag_group_id'];
				//this will properly return 0 if none are found
				$total_query = ee()->db->query(
					"SELECT COUNT(DISTINCT tag_id) as count
					 FROM 	exp_tag_entries
					 WHERE 	tag_group_id = " . ee()->db->escape_str($row['tag_group_id'])
				);

				$row['total_tags']				= $total_query->row('count');

				$tag_groups[] = $row;
			}
		}

		$this->cached_vars['tag_groups'] = $tag_groups;

		//--------------------------------------------
		//	other data
		//--------------------------------------------

		$this->cached_vars['lock_img'] 	= $this->sc->addon_theme_url . "images/lock.png";
		$this->cached_vars['form_url'] 	= $this->base . AMP . 'method=delete_tag_group_confirm';

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('tag_groups.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END tag_groups


	// --------------------------------------------------------------------

	/**
	 * edit_tag_group_form
	 * tag_groups_edit/create MCP page
	 *
	 * @access	public
	 * @param	message	flashdata message for CP
	 * @return	string	output html
	 */

	public function edit_tag_group_form($message = '')
	{
		if ($message == '' AND ee()->input->get_post('msg') !== FALSE)
		{
			$message = lang(ee()->input->get_post('msg'));
		}

		//--------------------------------------------
		//	function mode
		//--------------------------------------------

		$mode = (ee()->input->get_post('tag_group_id') AND
			is_numeric(ee()->input->get_post('tag_group_id'))) ? 'edit' : 'create';

		//----------------------------------------
		//	 message links
		//----------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_tag_groups';

		$this->add_crumb(lang('tag_groups'));

		$this->add_crumb(lang($mode . '_tag_group'));

		$this->cached_vars['message'] 	= $message;

		//--------------------------------------
		//  lang
		//--------------------------------------

		$lvars = array(
			'group_name',
			'group_short_name'
		);

		foreach($lvars as $var)
		{
			//replacing all spaces with non-breaking just to prevent BS
			$this->cached_vars['lang_' . $var] = str_replace(' ', NBS, lang($var));
		}

		$this->cached_vars['submit_lang'] = lang(($mode == 'edit') ? 'update_tag_group' : 'create_tag_group');

		//--------------------------------------------
		//	get data
		//--------------------------------------------

		$name_field 		= '';
		$short_name_field 	= '';

		if ($mode == 'edit')
		{
			$query = ee()->db->query(
				"SELECT *
				 FROM	exp_tag_groups
				 WHERE	tag_group_id = " . ee()->db->escape_str(ee()->input->get_post('tag_group_id'))
			);

			if ($query->num_rows() > 0)
			{
				$name_field 		= $query->row('tag_group_name');
				$short_name_field 	= $query->row('tag_group_short_name');
				$this->cached_vars['tag_group_id'] = $query->row('tag_group_id');
			}
		}

		$this->cached_vars['tag_group_name'] 		= $name_field;
		$this->cached_vars['tag_group_short_name'] 	= $short_name_field;

		//--------------------------------------------
		//	other data
		//--------------------------------------------

		$this->cached_vars['form_url'] 	= $this->base . AMP . 'method=edit_tag_group';

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		ee()->cp->load_package_js('edit_tag_group');

		$this->cached_vars['current_page'] = $this->view('edit_tag_group.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END tag_groups


	//--------------------------------------------
	//	edit_tag_group
	//--------------------------------------------

	public function edit_tag_group()
	{
		//edit
		if (ee()->input->get_post('tag_group_id') AND
			is_numeric(ee()->input->get_post('tag_group_id')))
		{
			ee()->load->helper('url');

			$group_name   = strtolower(url_title(ee()->input->get_post('tag_group_name'), 'underscore'));
			$short_name   = strtolower(ee()->input->get_post('tag_group_short_name'));

			$tag_group_short_name	= ($group_name == $short_name) ? $group_name : $short_name;

			ee()->db->query(
				ee()->db->update_string(
					'exp_tag_groups',
					array(
						'tag_group_name' 		=> ee()->input->get_post('tag_group_name'),
						'tag_group_short_name'	=> $tag_group_short_name
					),
					array(
						'tag_group_id'		=> ee()->input->get_post('tag_group_id')
					)
				)
			);

			$msg = 'tag_group_updated';
		}
		//create
		else
		{
			$this->data->insert_new_tag_group(ee()->input->get_post('tag_group_name'), ee()->input->get_post('tag_group_short_name'));

			$msg = 'tag_group_created';
		}

		ee()->functions->redirect($this->base . AMP . 'method=tag_groups' . AMP . 'msg=' . $msg);
	}

	//	----------------------------------------
	//	Delete Tag - Confirm
	//	---------------------------------------

	public function delete_tag_group_confirm()
	{
		if ( ee()->input->post('toggle') === FALSE )
		{
			return $this->index();
		}

		$this->cached_vars['tag_group_ids'] = array();

		foreach ( $_POST['toggle'] as $key => $val )
		{
			$this->cached_vars['tag_group_ids'][] = $val;
		}

		if ( count($this->cached_vars['tag_group_ids']) == 1 )
		{
			$replace[]	= 1;
		}
		else
		{
			$replace[]	= count($this->cached_vars['tag_group_ids']);
		}

		$search	= array('%i%');

		$this->cached_vars['lang_tag_group_delete_question'] = str_replace( $search, $replace, lang('tag_group_delete_question'));

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------


		$this->cached_vars['module_menu_highlight'] = 'module_tag_groups';

		$this->add_crumb(lang('tag_groups'));

		$this->add_crumb(lang('tag_group_delete_confirm'));


		$this->cached_vars['form_url'] = $this->base . AMP . 'method=delete_tag_groups';

		//--------------------------------------
		//  lang
		//--------------------------------------

		$lvars = array(
			'action_can_not_be_undone',
			'delete'
		);

		foreach($lvars as $var)
		{
			//replacing all spaces with non-breaking just to prevent BS
			$this->cached_vars['lang_' . $var] = str_replace(' ', NBS, lang($var));
		}

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('delete_tag_group_confirm.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}

	// END delete_tag_group_confirm()


	//--------------------------------------------
	//	tag group delete
	//--------------------------------------------

	public function delete_tag_groups()
	{
		$sql	= array();

		if ( ee()->input->post('delete') === FALSE OR ! is_array(ee()->input->post('delete')))
		{
			return $this->index();
		}

		//--------------------------------------------
		//	get tag groups that are being used
		//	so people wont delete stuff thats legit
		//--------------------------------------------

		$ug_query = ee()->db->query(
			"SELECT field_settings
			 FROM	exp_channel_fields
			 WHERE	field_type = 'tag'"
		);

		$used_groups = array();

		if ($ug_query->num_rows() > 0)
		{
			foreach ($ug_query->result_array() as $row)
			{
				$data = unserialize(base64_decode($row['field_settings']));

				if (isset($data['tag_group']) AND $data['tag_group'] != 1)
				{
					$used_groups[] = $data['tag_group'];
				}
			}
		}

		$ids	= array();

		foreach ($_POST['delete'] as $key => $val)
		{
			if (is_numeric($val) AND ! in_array($val, $used_groups))
			{
				$ids[] = $val;
			}
		}

		if ( ! empty($ids))
		{
			ee()->db->query(
				"DELETE FROM 	exp_tag_groups
				 WHERE 			tag_group_id
				 IN 			(" . implode(",", ee()->db->escape_str($ids)) . ")"
			);

			//drop count columns
			foreach ($ids as $id)
			{
				$col	= ee()->db->escape_str('total_entries_' . $id);

				if ($this->column_exists($col, 'exp_tag_tags'))
				{
					ee()->db->query(
						"ALTER TABLE 	`exp_tag_tags`
						 DROP COLUMN 	{$col}"
					);
				}
			}
		}

		ee()->functions->redirect(
			$this->base .
				AMP . 'method=tag_groups' .
				AMP . 'msg=tag_group_deleted'
		);
	}
	// END delete_tag_groups()


	//	----------------------------------------
	//	Tags
	//	---------------------------------------

	public function _tags()
	{
		$paginate		= '';
		$row_count		= 0;

		//--------------------------------------------
		//	are we looking for tag groups?
		//--------------------------------------------

		$tags_in_group = FALSE;

		if ( ! in_array(ee()->input->get_post('tag_group_id'), array(FALSE, '', 0, '0'), TRUE) AND
			is_numeric(ee()->input->get_post('tag_group_id'))
		 )
		{
			$tags_in_group = $this->data->get_tag_ids_by_group_id(ee()->input->get_post('tag_group_id'));
		}

		//	----------------------------------------
		//	Bad tags array
		//	----------------------------------------

		$this->cached_vars['bad_tags'] = array();

		$badq = ee()->db->query(
			"SELECT tag_name
			 FROM 	exp_tag_bad_tags
			 WHERE 	site_id = {$this->clean_site_id}"
		);

		foreach( $badq->result_array() as $b )
		{
			$this->cached_vars['bad_tags'][] = $b['tag_name'];
		}

		// --------------------------------------------
		//  Build Our Tags Query
		// --------------------------------------------

		$sql	= " FROM exp_tag_tags t
					LEFT JOIN exp_members m ON m.member_id = t.author_id
					WHERE t.tag_name != ''
					AND t.site_id = {$this->clean_site_id}";


		if ( ee()->input->get_post('alpha') !== FALSE)
		{
			//$_POST['tag_search_keywords'] = $this->_clean_str( ee()->input->get_post('alpha'));
			$sql .=	" AND t.tag_name REGEXP '^" . ee()->db->escape_str($this->_clean_str( ee()->input->get_post('alpha'), TRUE )) . "'";
		}

		if (ee()->input->get_post('tag_search_keywords') !== FALSE)
		{
			$sql .=	" AND t.tag_name LIKE '%" .
					ee()->db->escape_str($this->_clean_str( ee()->input->get_post('tag_search_keywords'), TRUE ))."%'";
		}

		if ($tags_in_group)
		{
			$sql .=	" AND t.tag_id IN (" . implode(',', $tags_in_group) . ") ";
		}

		$query	= ee()->db->query("SELECT COUNT(*) AS count ".$sql);

		$sql	.= " ORDER BY t.tag_name ASC";

		//	----------------------------------------
		//	Paginate
		//	----------------------------------------

		$sql = "SELECT t.tag_name, t.tag_id, t.entry_date, t.edit_date, m.member_id " . $sql;

		$this->cached_vars['paginate'] = '';

		if ( $query->row()->count > $this->row_limit )
		{
			$row_count		= ( ee()->input->get_post('row') === FALSE OR ee()->input->get_post('row') == '' ) ? 0 : ee()->input->get_post('row');

			$alpha			= ( ee()->input->get_post('alpha') === FALSE ) ? '': AMP . 'alpha=' . ee()->input->get_post('alpha');

			$tag_group_id	= ( ee()->input->get_post('tag_group_id') === FALSE ) ? '': AMP . 'tag_group_id=' . ee()->input->get_post('tag_group_id');

			ee()->load->library('pagination');

			$config['base_url']				= $this->base.'&method=index'.$alpha.$tag_group_id;
			$config['total_rows']			= $query->row()->count;
			$config['per_page']				= $this->row_limit;
			$config['page_query_string']	= TRUE;
			$config['query_string_segment']	= 'row';

			ee()->pagination->initialize($config);

			$this->cached_vars['paginate'] = ee()->pagination->create_links();

			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
		}

		$query = ee()->db->query($sql);

		$this->cached_vars['tags'] = array();

		$member_ids = $tag_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach($query->result_array() AS $row)
			{
				$tag_ids[]						= $row['tag_id'];
				$member_ids[$row['member_id']]	= '--';
			}

			//	----------------------------------------
			//	 Fetch Tag Count Data
			//	----------------------------------------

			$tag_counts = array();

			$cquery = ee()->db->query(
				"SELECT 	tag_id, COUNT(*) AS count
				 FROM 		exp_tag_entries
				 WHERE 		site_id = '".$this->clean_site_id."'
				 AND 		tag_id
				 IN 		(".implode(',', $tag_ids).")
				 GROUP BY 	tag_id"
			);

			foreach( $cquery->result_array() as $row )
			{
				$tag_counts[$row['tag_id']] = $row['count'];
			}

			// --------------------------------------------
			//  Fetch Screen Names
			// --------------------------------------------



			$squery = ee()->db->query(
				"SELECT screen_name, member_id
				 FROM 	exp_members
				 WHERE 	member_id
				 IN 	('" . implode("','", array_keys($member_ids)) . "')"
			);

			foreach($squery->result_array() as $row)
			{
				$member_ids[$row['member_id']] = $row['screen_name'];
			}

			foreach($query->result_array() as $key => $row)
			{
				if ( empty($row['edit_date']))
				{
					$row['edit_date'] = $row['entry_date'];
				}

				$this->cached_vars['tags'][$row['tag_id']] = $row;
				$this->cached_vars['tags'][$row['tag_id']][$this->sc->channel . '_entries_count'] = ( ! isset($tag_counts[$row['tag_id']])) ? 0 : $tag_counts[$row['tag_id']];
				$this->cached_vars['tags'][$row['tag_id']]['screen_name'] = $member_ids[$row['member_id']];
			}


		}
	}

	/* END _tags */


	// --------------------------------------------------------------------

	/**
	 *	Manage Tags Processing
	 *
	 *	Redirects to either a mass-edit, mass-delete, or a simple search
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function manage_tags_process()
	{
		if ( isset($_POST['delete_tag_button']))
		{
			return $this->delete_tag_confirm();
		}
		elseif(isset($_POST['search_tags_button']))
		{
			return $this->index();
		}
		else
		{
			return $this->index();
		}
	}
	/* END manage_tags_proces() */


	//	----------------------------------------
	//	Entries by tag
	//	----------------------------------------

	public function channel_entries_by_tag($message = '')
	{
		//	----------------------------------------
		//	 Fetch Tag Name
		//	----------------------------------------

		$query = ee()->db->query(
			"SELECT tag_name
			 FROM 	exp_tag_tags
			 WHERE 	site_id = {$this->clean_site_id}
			 AND 	tag_id  = '" . ee()->db->escape_str(ee()->input->get_post('tag_id')) . "'"
		);

		if ($query->num_rows() == 0)
		{
			$this->add_crumb(lang('invalid_request'));
			$this->cached_vars['error_message'] = lang('invalid_request');

			return $this->ee_cp_view('error_page.html');
		}

		$this->cached_vars['tag_name']	= trim($query->row()->tag_name, '"');
		$this->cached_vars['tag_id']	= ee()->input->get_post('tag_id');

		//	----------------------------------------
		//	Gallery entries bar
		//	----------------------------------------

		$this->cached_vars['has_gallery_entries'] = 'no';

		//	----------------------------------------
		//	Fetch Entries
		//	----------------------------------------

		$this->_entries(ee()->input->get_post('tag_id'));

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->add_crumb(lang('channel_entries_by_tag'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('channel_entries_by_tag.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END channel_entries_by_tag()


	//	---------------------------------------
	//	Entries
	//	---------------------------------------

	public function _entries( $tag_id = '' )
	{
		$this->cached_vars['entries'] = array();



		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$select = "SELECT wt.title, wt.entry_date, wt.entry_id,
						  wt.{$this->sc->db->channel_id}, wt.author_id, te.type ";

		$sql	= "FROM 		{$this->sc->db->channel_titles} AS wt
				   LEFT JOIN 	exp_tag_entries te
				   ON 			wt.entry_id = te.entry_id
				   WHERE 		te.type 	= 'channel'
				   AND 			te.site_id 	= '" . $this->clean_site_id . "'
				   AND 			te.tag_id 	= '" . ee()->db->escape_str( $tag_id ) . "'
				   ORDER BY 	wt.entry_date ASC";

		$query	= ee()->db->query("SELECT COUNT(*) AS count ".$sql);

		//	----------------------------------------
		//	Paginate
		//	----------------------------------------

		$this->cached_vars['paginate']	= '';

		if ( $query->row()->count > $this->row_limit )
		{
			$row_count = ( ee()->input->get_post('row') === FALSE OR
						   ee()->input->get_post('row') == '' ) ? 0 : ee()->input->get_post('row');

			ee()->load->library('pagination');

			$config['base_url']				= $this->base . '&method=channel_entries_by_tag&tag_id='.$tag_id;
			$config['total_rows']			= $query->row()->count;
			$config['per_page']				= $this->row_limit;
			$config['page_query_string']	= TRUE;
			$config['query_string_segment']	= 'row';

			ee()->pagination->initialize($config);

			$this->cached_vars['paginate'] = ee()->pagination->create_links();

			$sql .= " LIMIT " . $row_count . ", " . $this->row_limit;
		}

		$query = ee()->db->query($select.$sql);

		// --------------------------------------------
		//  Screen Names - Done separately because of DB character set
		// --------------------------------------------



		$author_ids		= array();
		$screen_names	= array();

		foreach($query->result_array() as $row)
		{
			$author_ids[] = $row['author_id'];
		}

		$mquery = ee()->db->query(
			"SELECT screen_name, member_id
			 FROM 	exp_members
			 WHERE 	member_id
			 IN 	('" . implode("','", array_unique($author_ids)) . "')"
		);

		foreach($mquery->result_array() as $row)
		{
			$screen_name[$row['member_id']] = $row['screen_name'];
		}

		foreach($query->result_array() as $key => $row)
		{
			$row['entry_date'] = $this->human_time($row['entry_date']);

			$this->cached_vars['entries'][$row['entry_id']] = $row;
			$this->cached_vars['entries'][$row['entry_id']]['screen_name'] = (
				! isset($screen_name[$row['author_id']])
			) ? '--' : $screen_name[$row['author_id']];
		}


	}
	// END _entries


	// --------------------------------------------------------------------

	/**
	 *	Edit Tag Form
	 *
	 *	Come on, doofus, if it ain't obvious from the name, then you seriously need to just go
	 *	somewhere else and bug anyone you find there.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit_tag_form()
	{
		if ( ee()->input->get_post('tag_id') === FALSE OR
			! is_numeric(ee()->input->get_post('tag_id')))
		{
			return FALSE;
		}

		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$query = ee()->db->query(
			"SELECT *
			 FROM 	exp_tag_tags
			 WHERE  site_id = {$this->clean_site_id}
			 AND 	tag_id = '" . ee()->db->escape_str(ee()->input->get_post('tag_id')) . "'
			 LIMIT 	1"
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		$this->cached_vars = array_merge($this->cached_vars, $query->row_array());

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->add_crumb(lang('edit_tag'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('edit_tag_form.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END edit tag form


	// --------------------------------------------------------------------

	/**
	 *	Edit Tag
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function edit_tag()
	{
		if ( ee()->input->get_post('tag_id') === FALSE OR
			 ! is_numeric(ee()->input->get_post('tag_id')))
		{
			return FALSE;
		}

		$this->tag_id = ee()->db->escape_str( ee()->input->get_post('tag_id'));

		$combine = TRUE;

		//	----------------------------------------
		//	Validate
		//	----------------------------------------

		if ( ( $tag_name = ee()->input->get_post('tag_name') ) === FALSE )
		{
			return $this->_error_message(lang('tag_name_required'));
		}

		$query = ee()->db->query(
			"SELECT tag_name
			 FROM 	exp_tag_tags
			 WHERE 	tag_id = '" . $this->tag_id . "'
			 LIMIT 	1"
		);

		if ($query->num_rows() == 0)
		{
			return $this->index();
		}

		$old_tag_name = $query->row('tag_name');

		unset($temp);

		//	----------------------------------------
		//	Clean tag
		//	----------------------------------------

		$tag_name	= $this->_clean_str( $tag_name );

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		$sql	= "SELECT 	tag_id, tag_name
				   FROM 	exp_tag_tags
				   WHERE 	site_id = {$this->clean_site_id}
				   AND 		tag_name = '" . ee()->db->escape_str( $tag_name ) . "'";

		if ( $this->tag_id != '' )
		{
			$sql .= " AND tag_id != '" . $this->tag_id . "'";
		}

		$sql	.= " LIMIT 1";

		$query	= ee()->db->query( $sql );

		//	----------------------------------------
		//	If we find no matching tags we can't possibly combine tags.
		//	----------------------------------------

		if ( $query->num_rows() == 0 )
		{
			$combine = FALSE;
		}

		//	----------------------------------------
		//	Are we combining?
		//	----------------------------------------

		if ( $combine === TRUE )
		{
			// --------------------------------------------
			//  Previously Tagged by New Tag
			// --------------------------------------------

			$extra_sql			= '';

			$previous = ee()->db->query(
				"SELECT entry_id
				 FROM 	exp_tag_entries
				 WHERE 	tag_id = '" . $query->row('tag_id') . "'"
			);

			if ($previous->num_rows() > 0)
			{
				$previous_entries	= array();

				foreach($previous->result_array() as $row)
				{
					$previous_entries[] = $row['entry_id'];
				}

				$extra_sql .= " AND entry_id NOT IN (".implode(',', $previous_entries).")";
			}

			// --------------------------------------------
			//  Update Tag Entries from Old to New, Except Where Already Tagged by New
			// --------------------------------------------

			ee()->db->query(
				"UPDATE exp_tag_entries
				 SET 	tag_id = '" . ee()->db->escape_str($query->row('tag_id')) . "'
				 WHERE 	tag_id = '" . ee()->db->escape_str($this->tag_id) . "'" .
				 $extra_sql
			);

			//	----------------------------------------
			//	Delete the old
			//	----------------------------------------

			ee()->db->query( "DELETE FROM exp_tag_entries 		WHERE tag_id = '" . $this->tag_id . "'" );
			ee()->db->query( "DELETE FROM exp_tag_tags 			WHERE tag_id = '" . $this->tag_id . "'" );
			ee()->db->query( "DELETE FROM exp_tag_subscriptions WHERE tag_id = '" . $this->tag_id . "'" );

			//	----------------------------------------
			//	Recount stats
			//	----------------------------------------

			$this->_recount( array( $query->row('tag_id') ) );

			$message	= str_replace(
				array( '%old_tag_name%', '%new_tag_name%' ),
				array( $old_tag_name, $tag_name ),
				lang('tags_combined')
			);
		}

		//	----------------------------------------
		//	 No Combining, Simply Updating
		//	----------------------------------------

		if ( $combine === FALSE )
		{
			ee()->db->query(
				ee()->db->update_string(
					'exp_tag_tags',
					array(
						'tag_name' 	=> $tag_name,
						'tag_alpha' => $this->_first_character($tag_name),
						'author_id' => ee()->session->userdata['member_id'],
						'edit_date' => ee()->localize->now
					),
					array(
						'tag_id' => $this->tag_id
					)
				)
			);

			$message	= lang('tag_updated');
		}

		return $this->index($message);
	}
	// END edit tag


	// --------------------------------------------------------------------

	/**
	 *	Clean String
	 *
	 *	Cleans up tag strings into useable items by the Tag module
	 *
	 *	@access		public
	 * 	@param 		string 	string to be converted
	 *	@return		string 	clean tag string
	 */

	public function _clean_str( $str = '' )
	{
		return $this->actions()->_clean_str($str);
	}
	// END clean tag


	// --------------------------------------------------------------------

	/**
	 *	Find First Character
	 *
	 *	finds the first character of a string using multibyte methods
	 * 	if availble
	 *
	 *	@access		public
	 * 	@param 		string 	string to find the first character of
	 *	@return		string 	first character of string
	 */

	public function _first_character($str)
	{
		if (function_exists('mb_substr'))
		{
			return mb_substr($str, 0, 1);
		}
		elseif(function_exists('iconv_substr') AND ($iconvstr = @iconv('', 'UTF-8', $str)) !== FALSE)
		{
			return iconv_substr($iconvstr, 0, 1, 'UTF-8');
		}
		else
		{
			return substr( $str, 0, 1 );
		}
	}
	// END _first_character()


	// --------------------------------------------------------------------

	/**
	 *	String To Lower
	 *
	 *	returns string to lower with multibyte functions if availablew
	 *
	 *	@access		public
	 * 	@param 		string 	string to convert
	 *	@return		string 	lower cased string
	 */

	public function _strtolower($str)
	{
		return $this->actions()->_strtolower($str);
	}
	// END _strtolower()


	// --------------------------------------------------------------------

	/**
	 *	Delete Tag - Confirm
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_tag_confirm()
	{
		if ( ee()->input->post('toggle') === FALSE )
		{
			return $this->index();
		}

		$this->cached_vars['tag_ids'] = array();

		foreach ( $_POST['toggle'] as $key => $val )
		{
			$this->cached_vars['tag_ids'][] = $val;
		}

		if ( count($this->cached_vars['tag_ids']) == 1 )
		{
			$replace[]	= 1;
			$replace[]	= 'tag';
		}
		else
		{
			$replace[]	= count($this->cached_vars['tag_ids']);
			$replace[]	= 'tags';
		}

		$search	= array( '%i%', '%tags%' );

		$this->cached_vars['tag_delete_question'] = str_replace( $search, $replace, lang('tag_delete_question'));

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->add_crumb(lang('tag_delete_confirm'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('delete_tag_confirm.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END delete_tag_confirm()


	// --------------------------------------------------------------------

	/**
	 *	Delete Tag
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_tag()
	{
		$sql	= array();

		if ( ee()->input->post('delete') === FALSE OR
			 ! is_array(ee()->input->post('delete')))
		{
			return $this->index();
		}

		$ids	= array();

		foreach($_POST['delete'] as $key => $val)
		{
			$ids[] = $val;
		}

		$query = ee()->db->query(
			"SELECT tag_id
			 FROM 	exp_tag_tags
			 WHERE 	tag_id
			 IN 	('".implode("','", ee()->db->escape_str($ids))."')"
		);

		//	----------------------------------------
		//	Delete Tags
		//	----------------------------------------

		$ids = array();

		foreach ( $query->result_array() as $row )
		{
			$ids[] = $row['tag_id'];
		}

		ee()->db->query(
			"DELETE FROM 	exp_tag_tags
			 WHERE 			tag_id
			 IN 			('".implode("','", ee()->db->escape_str($ids))."')"
		);

		ee()->db->query(
			"DELETE FROM 	exp_tag_entries
			 WHERE 			tag_id
			 IN 			('".implode("','", ee()->db->escape_str($ids))."')"
		);

		ee()->db->query(
			"DELETE FROM 	exp_tag_subscriptions
			 WHERE 			tag_id
			 IN 			('".implode("','", ee()->db->escape_str($ids))."')"
		);

		foreach ( $sql as $q )
		{
			ee()->db->query($q);
		}

		$message = ($query->num_rows() == 1) ?
					str_replace(
						'%i%',
						$query->num_rows(),
						lang('tag_deleted')
					) :
					str_replace(
						'%i%',
						$query->num_rows(),
						lang('tags_deleted')
					);

		return $this->index($message);
	}
	// END delete_tag()


	// --------------------------------------------------------------------

	/**
	 *	Bad Tag quick submit
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function bad_tag()
	{
		//	----------------------------------------
		//	Validate
		//	----------------------------------------

		if ( ( $tag_name_post = ee()->input->post('tag_name') ) === FALSE AND
			( $tag_name_get = base64_decode(urldecode(ee()->input->get('tag_name'))) ) === FALSE )
		{
			return $this->_error_message(lang('tag_name_required'));
		}

		$tag_name = ($tag_name_post !== FALSE) ? $tag_name_post : $tag_name_get;

		// --------------------------------------------
		//  The Past Messing with the Future
		// --------------------------------------------

		// What we have here is an old tag that was not
		// made lower cased when created.
		// Kelsey still wants the non-lowercased version entered,
		// so we do this whole fun process twice!

		if ($this->preference('convert_case') != 'n' AND
			$this->_strtolower($tag_name) != $tag_name)
		{
			//	----------------------------------------
			//	Check for duplicate
			//	----------------------------------------

			$query	= ee()->db->query(
				"SELECT tag_name
				 FROM 	exp_tag_bad_tags
				 WHERE  site_id = '".$this->clean_site_id."'
				 AND 	BINARY tag_name = '".ee()->db->escape_str( $tag_name )."'"
			);

			if ( $query->num_rows() > 0 )
			{
				$tag_name = trim($tag_name, '"');

				return $this->_error_message(
					str_replace(
						'%tag_name%',
						stripslashes( $tag_name ),
						lang('bad_tag_exists')
					)
				);
			}

			//	----------------------------------------
			//	Add
			//	----------------------------------------

			ee()->db->query(
				ee()->db->insert_string(
					'exp_tag_bad_tags',
					array(
						'tag_name' 	=> $tag_name,
						'site_id' 	=> ee()->config->item('site_id'),
						'author_id' => ee()->session->userdata['member_id'],
						'edit_date' => ee()->localize->now
					)
				)
			);
		}

		//	----------------------------------------
		//	Clean tag
		//	----------------------------------------

		$tag_name = $this->_clean_str( $tag_name );

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		if ($this->preference('convert_case') != 'n')
		{
			$tag_name = strtolower($tag_name);
		}

		$query	= ee()->db->query(
			"SELECT tag_name
			 FROM 	exp_tag_bad_tags
			 WHERE  site_id = '".$this->clean_site_id."'
			 AND   	BINARY tag_name = '".ee()->db->escape_str( $tag_name )."'"
		);

		if ( $query->num_rows() > 0 )
		{
			$tag_name = trim($tag_name, '"');

			return $this->_error_message(
				str_replace(
					'%tag_name%',
					stripslashes( $tag_name ),
					lang('bad_tag_exists')
				)
			);
		}

		//	----------------------------------------
		//	Add
		//	----------------------------------------

		ee()->db->query(
			ee()->db->insert_string(
				'exp_tag_bad_tags',
				array(
					'tag_name' 	=> $tag_name,
					'site_id' 	=> ee()->config->item('site_id'),
					'author_id' => ee()->session->userdata['member_id'],
					'edit_date' => ee()->localize->now
				)
			)
		);

		return $this->index(
			str_replace(
				'%tag_name%',
				"'".stripslashes( trim($tag_name, '"') )."'",
				lang('bad_tag_added')
			)
		);
	}
	// END bad tag quick submit


	// --------------------------------------------------------------------

	/**
	 *	Manage Bad Tags
	 *
	 *	Manage Bad Tags in the CP and delete.
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *	@return		string
	 */

	public function manage_bad_tags($message = '')
	{
		//	----------------------------------------
		//	Query
		//	----------------------------------------

		$sql = "SELECT bt.*
				FROM exp_tag_bad_tags bt
				WHERE bt.site_id = '".$this->clean_site_id."'
				ORDER BY bt.tag_name ASC";

		$query = ee()->db->query(
			preg_replace(
				"/SELECT(.*?)\s+FROM\s+/is",
				'SELECT COUNT(*) AS count FROM ',
				$sql
			)
		);

		//	----------------------------------------
		//	Paginate
		//	----------------------------------------

		$this->cached_vars['paginate'] = '';

		if ( $query->row()->count > $this->row_limit )
		{
			$row_count		= ( ee()->input->get_post('row') === FALSE OR ee()->input->get_post('row') == '' ) ? 0 : ee()->input->get_post('row');

			ee()->load->library('pagination');

			$config['base_url']				= $this->base.'&method=manage_bad_tags';
			$config['total_rows']			= $query->row()->count;
			$config['per_page']				= $this->row_limit;
			$config['page_query_string']	= TRUE;
			$config['query_string_segment']	= 'row';

			ee()->pagination->initialize($config);

			$this->cached_vars['paginate'] = ee()->pagination->create_links();

			$sql .= " LIMIT ".$row_count.", ".$this->row_limit;
		}

		$query = ee()->db->query($sql);

		// --------------------------------------------
		//  Screen Names - Done separately because of DB character set
		// --------------------------------------------



		$this->cached_vars['bad_tags'] = array();

		$author_ids		= array();
		$screen_names	= array();

		foreach($query->result_array() as $row)
		{
			$author_ids[] = $row['author_id'];
		}

		$mquery = ee()->db->query("SELECT screen_name, member_id FROM exp_members
									WHERE member_id IN ('".implode("','", ee()->db->escape_str(array_unique($author_ids)))."')");

		foreach($mquery->result_array() as $row)
		{
			$screen_name[$row['member_id']] = $row['screen_name'];
		}

		foreach($query->result_array() as $key => $row)
		{
			$row['edit_date'] = $this->human_time($row['edit_date']);

			$this->cached_vars['bad_tags'][$row['tag_id']] = $row;
			$this->cached_vars['bad_tags'][$row['tag_id']]['screen_name'] = ( isset($screen_name[$row['author_id']])) ? $screen_name[$row['author_id']] : '--';
		}



		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->cached_vars['right_crumb_link']	= $this->base.'&method=add_bad_tags_form';
		$this->cached_vars['right_crumb_title']	= lang('add_bad_tags');

		$this->cached_vars['module_menu_highlight'] = 'module_manage_bad_tags';

		$this->add_crumb(lang('manage_bad_tags'));

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('bad_tags.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END manage_bad_tags()


	// --------------------------------------------------------------------

	/**
	 *	Bad Tags Processing Method
	 *
	 *	Currently, this is a direct line to the Delete Confirm code.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function bad_tags_process()
	{
		if ( ee()->input->post('toggle') === FALSE )
		{
			return $this->index();
		}

		$this->cached_vars['tag_ids'] = array();

		foreach ( $_POST['toggle'] as $key => $val )
		{
			$this->cached_vars['tag_ids'][] = $val;
		}

		if ( count($this->cached_vars['tag_ids']) == 1 )
		{
			$replace[]	= 1;
			$replace[]	= 'tag';
		}
		else
		{
			$replace[]	= count($this->cached_vars['tag_ids']);
			$replace[]	= 'tags';
		}

		$search	= array( '%i%', '%tags%' );

		$this->cached_vars['bad_tag_delete_question'] = str_replace( $search, $replace, lang('bad_tag_delete_question'));

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->add_crumb(lang('bad_tag_delete_confirm'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('delete_bad_tag_confirm.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END bad_tags_process()


	// --------------------------------------------------------------------

	/**
	 *	Delete Bad Tag
	 *
	 *	Removes Bad Tags from the database.
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function delete_bad_tag()
	{
		$sql	= array();

		if ( ee()->input->post('delete') === FALSE OR
			 ! is_array(ee()->input->post('delete')))
		{
			return $this->manage_bad_tags();
		}

		$ids	= array();

		foreach ($_POST['delete'] as $key => $val)
		{
			$ids[] = $val;
		}

		$query = ee()->db->query("SELECT tag_id FROM exp_tag_bad_tags WHERE tag_id IN ('".implode("','", ee()->db->escape_str($ids))."')");

		//	----------------------------------------
		//	Delete Bad Tags!
		//	----------------------------------------

		$ids = array();

		foreach ( $query->result_array() as $row )
		{
			$ids[] = $row['tag_id'];
		}

		ee()->db->query("DELETE FROM exp_tag_bad_tags WHERE tag_id IN ('".implode("','", ee()->db->escape_str($ids))."')");

		$message = ($query->num_rows() == 1) ? str_replace( '%i%', $query->num_rows(), lang('bad_tag_deleted') ) : str_replace( '%i%', $query->num_rows(), lang('bad_tags_deleted') );

		return $this->manage_bad_tags($message);
	}
	// END delete_bad_tag()


	// --------------------------------------------------------------------

	/**
	 *	Edit bad tag form
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function add_bad_tags_form()
	{
		$this->cached_vars['module_menu_highlight'] = 'module_manage_bad_tags';

		$this->add_crumb(lang('add_bad_tags'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('add_bad_tags_form.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END add_bad_tags_form()


	// --------------------------------------------------------------------

	/**
	 *	Edit bad tag
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function add_bad_tags()
	{
		//	----------------------------------------
		//	Validate
		//	----------------------------------------

		if ( ( $tag_name = ee()->input->get_post('tag_name') ) === FALSE )
		{
			return $this->_error_message(lang('tag_name_required'));
		}

		//	----------------------------------------
		//	Clean tag
		//	----------------------------------------

		$tag_name = $this->_clean_str( $tag_name );

		if ($tag_name == '')
		{
			return $this->_error_message(lang('tag_name_required'));
		}

		$tag_array = preg_split( "/\n|\r/", $tag_name, -1, PREG_SPLIT_NO_EMPTY);

		//	----------------------------------------
		//	Check for duplicate
		//	----------------------------------------

		$inserts = array();

		foreach($tag_array as $tag_name)
		{
			$query	= ee()->db->query("SELECT tag_name FROM exp_tag_bad_tags
									  WHERE site_id = '".$this->clean_site_id."'
									  AND tag_name = '".ee()->db->escape_str( $tag_name )."'
									  LIMIT 1");

			if ( $query->num_rows() == 0 )
			{
				$inserts[] = $tag_name;
			}
		}

		//	----------------------------------------
		//	 Add New Bad Tags to Database
		//	----------------------------------------

		foreach($inserts as $tag_name)
		{
			ee()->db->query( ee()->db->insert_string('exp_tag_bad_tags',
													array(	'tag_name'	=> $tag_name,
															'site_id'	=> ee()->config->item('site_id'),
															'author_id'	=> ee()->session->userdata['member_id'],
															'edit_date'	=> ee()->localize->now ) ) );
		}

		$message = str_replace( '%tag_name%', implode(', ', $inserts), (count($inserts) == 1) ? lang('bad_tag_added') : lang('bad_tags_added') );

		return $this->manage_bad_tags($message);
	}
	// END add_bad_tags()


	// --------------------------------------------------------------------

	/**
	 *	Manage preferences
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *	@return		string
	 */

	public function preferences($message = '')
	{
		// --------------------------------------------
		//  Current Values
		// --------------------------------------------

		$prefs_query = ee()->db->get('tag_preferences');

		$preferences = array();

		foreach($prefs_query->result_array() as $row)
		{
			$this->module_preferences[$row['site_id']][
				$row['tag_preference_name']
			] = $row['tag_preference_value'];
		}

		//	----------------------------------------
		//	 Build Form
		//	----------------------------------------

		$this->cached_vars['current_site_id'] 	= $current_site_id = ee()->config->item('site_id');

		$this->cached_vars['separators']		= array_keys($this->data->delimiters);

		$this->cached_vars['form_fields']		= array();

		foreach($this->cached_vars['sites'] AS $site_id => $site_label)
		{
			$site_data = array();

			$site_data['separator']					= $this->either_or(
				ee()->input->get_post($site_id.'_separator'),
				$this->preference($site_id, 'separator')
			);

			$site_data['convert_case']					= $this->either_or(
				ee()->input->get_post($site_id.'_convert_case'),
				$this->preference($site_id, 'convert_case')
			);

			$site_data['enable_tag_form']				= $this->either_or(
				ee()->input->get_post($site_id.'_enable_tag_form'),
				$this->preference($site_id, 'enable_tag_form')
			);

			$site_data['allow_tag_creation_publish']	= $this->either_or(
				ee()->input->get_post($site_id.'_allow_tag_creation_publish'),
				$this->preference($site_id, 'allow_tag_creation_publish')
			);

			$site_data['publish_entry_tag_limit']		= $this->either_or(
				ee()->input->get_post($site_id.'_publish_entry_tag_limit'),
				$this->preference($site_id, 'publish_entry_tag_limit')
			);

			$site_data['explode_input_on_separator']		= $this->either_or(
				ee()->input->get_post($site_id.'_explode_input_on_separator'),
				$this->preference($site_id, 'explode_input_on_separator')
			);

			$site_data['enable_explode_controls']		= $this->either_or(
				ee()->input->get_post($site_id.'_enable_explode_controls'),
				$this->preference($site_id, 'enable_explode_controls')
			);

			// --------------------------------------------
			//  Create Site/Weblog/Publish Tab Fields - Default Data Arrays
			// --------------------------------------------

			$this->cached_vars['channel_form_fields'][$site_id] = array();
			$this->cached_vars['channels'][$site_id] 			= array();


			$this->cached_vars['form_fields'][$site_id] 		= $site_data;

			if ($current_site_id == $site_id)
			{
				$this->cached_vars['current_site_label'] = $site_label;
			}
		}

		// --------------------------------------------
		//  List of Channels
		// --------------------------------------------

		$channels_query = ee()->db
								->select(
									'channel_id, site_id,' .
									' channel_title, field_group'
								)
								->order_by('channel_title')
								->get('channels');

		$fields_query	= ee()->db
								->select('field_id, field_label, group_id')
								->order_by('field_order')
								->get('channel_fields');

		foreach($channels_query->result_array() as $row)
		{
			$this->cached_vars['channels'][$row['site_id']][$row['channel_id']] = $row['channel_title'];
			$fields[$row['field_group']] = array();
		}

		$this->cached_vars['default_channel'] = key($this->cached_vars['channels'][$current_site_id]);

		foreach($fields_query->result_array() as $row)
		{
			$fields[$row['group_id']][$row['field_id']] = $row['field_label'];
		}

		// --------------------------------------------
		//  Build Fields
		// --------------------------------------------

		foreach($channels_query->result_array() AS $row)
		{
			extract($row);

			$this->cached_vars['channel_form_fields'][$channel_id] = array();

			$this->cached_vars['channel_form_fields'][$channel_id]['publish_tab_label'] = (
				isset($this->module_preferences[$row['site_id']][$channel_id.'_publish_tab_label']) ?
					$this->module_preferences[$row['site_id']][$channel_id.'_publish_tab_label'] :
					''
			);

			$this->cached_vars['channel_form_fields'][$channel_id]['tag_field'] = (
				isset($this->module_preferences[$row['site_id']][$channel_id.'_tag_field']) ?
					$this->module_preferences[$row['site_id']][$channel_id.'_tag_field'] :
					''
			);

			$this->cached_vars['channel_form_fields'][$channel_id]['fields'] = $fields[$field_group];
		}

		$this->cached_vars['form_url'] = $this->base . AMP . 'method=update_preferences';

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->cached_vars['module_menu_highlight'] = 'module_preferences';

		$this->add_crumb(lang('tag_preferences'));

		$this->cached_vars['message'] = $message;

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		ee()->cp->load_package_js('preferences_form');

		$this->cached_vars['current_page'] = $this->view(
			'preferences_form.html',
			NULL,
			TRUE
		);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');

	}
	// END preferences()


	// --------------------------------------------------------------------

	/**
	 *	Update Preferences
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function update_preferences()
	{
		if ( ! isset($_POST[ee()->config->item('site_id').'_separator']))
		{
			ee()->functions->redirect(
				$this->base . AMP . 'method=preferences'
			);
		}

		ee()->db->truncate("tag_preferences");

		//	----------------------------------------
		//	Update Preferences
		//	----------------------------------------

		foreach($this->cached_vars['sites'] AS $site_id => $site_label)
		{
			$prefs = array(
				'separator',
				'convert_case',
				'enable_tag_form',
				'allow_tag_creation_publish',
				'publish_entry_tag_limit',
				'explode_input_on_separator',
				'enable_explode_controls'
			);

			foreach($prefs as $val)
			{
				ee()->db->insert(
					'exp_tag_preferences',
					array(
						'tag_preference_name' => $val,
						'tag_preference_value' => (
							$val == 'publish_entry_tag_limit'
						) ? floor(ee()->input->post($site_id.'_'.$val)) :
							ee()->input->post($site_id.'_'.$val),
						'site_id'				=> $site_id
					)
				);
			}
		}

		// --------------------------------------------
		//  Channel Specific Preferences
		// --------------------------------------------

		$channels_query = ee()->db
								->select('channel_id, site_id')
								->order_by('channel_title')
								->get('channels');

		foreach($channels_query->result_array() as $row)
		{
			ee()->db->insert(
				'exp_tag_preferences',
				array(
					'tag_preference_name'	=> $row['channel_id'].'_publish_tab_label',
					'tag_preference_value'	=> ee()->input->post(
						$row['channel_id'].'_publish_tab_label'
					),
					'site_id'				=> $row['site_id']
				)
			);

			ee()->db->insert(
				'exp_tag_preferences',
				array(
					'tag_preference_name'	=> $row['channel_id'].'_tag_field',
					'tag_preference_value'	=> ee()->input->post(
						$row['channel_id'].'_tag_field'
					),
					'site_id'				=> $row['site_id']
				)
			);
		}

		ee()->load->library('layout');

		if ( ! class_exists('Tag_upd'))
		{
			require_once $this->addon_path . 'upd.tag.php';
		}

		$T = new Tag_upd();

		//remove all old ones
		ee()->layout->delete_layout_tabs(
			array_merge($T->old_tabs(), $T->tabs())
		);
		ee()->layout->delete_layout_fields(
			array_merge($T->old_tabs(), $T->tabs())
		);

		//if we already have tabs named, we need to reinstall them
		//this starts by not using cache on these so if its true,
		//we still only call it once
		if ($this->data->get_tab_channel_ids(FALSE) !== FALSE)
		{
			ee()->layout->add_layout_tabs(
				$T->tabs(),
				'',
				array_keys($this->data->get_tab_channel_ids())
			);
		}

		return $this->preferences(lang('tag_preferences_updated'));
	}
	// END update_preferences()

	// --------------------------------------------------------------------

	/**
	 *	Tag Field Sync
	 *
	 *	Used when the Publish Tab was used and prior to Tag 4.0.  Now, with the custom field
	 *	type the tags are put into that custom field so that they can be used with the Search
	 *	module.  This code merely sures that every entry has this done. -PB
	 *
	 *	@access		public
	 *	@return		string
	 */
	public function tag_field_sync()
	{
		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->cached_vars['module_menu_highlight'] 	= 'module_utilities';
		$this->add_crumb(lang('utilities'), $this->base . AMP . 'method=utilities');
		$this->add_crumb(lang('sync_tags_fields'));

		// --------------------------------------------
		//  Find All Tag Custom Fields for Each Channel
		// --------------------------------------------

		$query = ee()->db->query("	SELECT	c.channel_id, cf.field_id, cf.field_settings
									FROM	exp_channels AS c,
											exp_channel_fields as cf
									 WHERE 	cf.group_id = c.field_group
									 AND	cf.field_type = 'tag'");

		if ($query->num_rows() == 0)
		{
			ee()->functions->redirect($this->base . AMP . 'method=utilities' . AMP . 'msg=no_tag_custom_field_type');
		}

		// --------------------------------------------
		//  Find All Entries without Tag Custom Field Data
		// --------------------------------------------

		$entries = array();

		foreach($query->result_array() AS $row)
		{
			$settings		= unserialize(base64_decode($row['field_settings']));
			$tag_group_id	= ( ! isset($settings['tag_group'])) ? 1 : $settings['tag_group'];

			$cquery = ee()->db->query("	SELECT	ct.entry_id, ct.title
										FROM	exp_channel_titles AS ct,
												exp_channel_data AS cd
										WHERE	ct.entry_id = cd.entry_id
										AND 	cd.field_id_{$row['field_id']} = ''
										AND		ct.entry_id IN
											(
												SELECT DISTINCT entry_id
												FROM exp_tag_entries
												WHERE channel_id = {$row['channel_id']}
												AND tag_group_id = {$tag_group_id}
											)");

			foreach($cquery->result_array() AS $crow)
			{
				$entries[$crow['entry_id']] = $crow['title'];
			}
		}

		if (count($entries) == 0)
		{
			//ee()->functions->redirect($this->base . AMP . 'method=utilities' . AMP . 'msg=no_tag_fields_needed_updating');
		}

		// -------------------------------------
		//	Prep the View Vars
		// -------------------------------------

		$this->cached_vars['entries'] 				= $entries;
		$this->cached_vars['total_entries_count'] 	= count($entries);
		$this->cached_vars['return_uri'] 			= $this->base;

		$this->cached_vars['ajax_url'] = str_replace(AMP, '&',
													  BASE.
													  AMP.'C=javascript'.
													  AMP.'M=load'.
													  AMP.'file=ext_scripts'.
													  AMP.'call=sync_tag_fields');

		// -------------------------------------
		//	jQuery UI stuff
		// -------------------------------------

		ee()->cp->add_js_script(array('ui' => 'progressbar'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('tag_field_sync.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	// END tag_field_sync()


	// --------------------------------------------------------------------

	/**
	 *	Update Tag Counts
	 *
	 *	uses ajax to update tag counts in a way that wont hog database resources
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *  @param 		bool 	show 4.1 update to user
	 *	@return		string
	 */

	public function update_tag_counts($message = '', $show_update_msg = FALSE)
	{
		// ----------------------------------------
		//	 Build page
		// ----------------------------------------

		$this->cached_vars['message'] = $message;

		$this->add_crumb(lang('update_tag_counts'));

		// -------------------------------------
		//	highlight
		// -------------------------------------

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->cached_vars['module_menu_highlight'] 	= 'module_utilities';

		$this->add_crumb(lang('utilities'), $this->base . AMP . 'method=utilities');


		$this->add_crumb(lang('update_tag_counts'));

		// -------------------------------------
		//	tag data
		// -------------------------------------

		$tags = array();

		$query = ee()->db->query(
			"SELECT 	tag_id, tag_name
			 FROM 		exp_tag_tags"
		);

		if ($query->num_rows() > 0)
		{
			foreach ($query->result_array() as $row)
			{
				$tags[$row['tag_id']] = $row['tag_name'];
			}
		}

		$this->cached_vars['tags'] 				= $tags;
		$this->cached_vars['total_tags_count'] 	= count($tags);
		$this->cached_vars['show_update_msg'] 	= $show_update_msg;

		// -------------------------------------
		//	lang
		// -------------------------------------

		$lines = array(
			'update_tag_counts',
			'update_tag_count_from_upgrade_notice',
			'update_tag_count_purpose',
			'update_all_tag_counts',
			"updating_counts_for_tag_" ,
			'percent_completed',
			'number_of_tags_updated',
			'pause',
			'resume',
			'tag_recounts_complete',
			'return_to_tag_mcp'
		);

		foreach ($lines as $line)
		{
			$this->cached_vars['lang_' . $line] = lang($line);
		}

		$this->cached_vars['return_uri'] = $this->base;

		// -------------------------------------
		//	AJAX url
		// -------------------------------------

		$this->cached_vars['ajax_url'] = str_replace(AMP, '&',
													  BASE.
													  AMP.'C=javascript'.
													  AMP.'M=load'.
													  AMP.'file=ext_scripts'.
													  AMP.'call=update_tag_count');

		// -------------------------------------
		//	jquery UI stuff
		// -------------------------------------

		ee()->cp->add_js_script(array('ui' => 'progressbar'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('update_tag_counts.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	//END update_tag_counts


	// --------------------------------------------------------------------

	/**
	 *	Manage harvest tags
	 *
	 *	@access		public
	 * 	@param 		string 	message to send to user
	 *	@return		string
	 */

	public function harvest($message = '')
	{
		//	----------------------------------------
		//	 Harvest from What Data Source?
		//	----------------------------------------

		$groups = array(
			'channel_categories'	=> lang('harvest_from_channel_categories'),
			'tag_fields'			=> lang('harvest_from_channel_tag_field')
		);

		foreach($groups as $group => $group_label)
		{
			$options[$group] = array();

			if ($group == 'channel_categories')
			{
				$channels_query = ee()->db->query(
					"SELECT 	{$this->sc->db->channel_id} AS channel_id,
								site_label,
								{$this->sc->db->channel_title} AS channel_title,
								field_group
					 FROM 		{$this->sc->db->channels}, exp_sites
					 WHERE 		exp_sites.site_id = {$this->sc->db->channels}.site_id
					 ORDER BY 	{$this->sc->db->channel_title}"
				);

				foreach($channels_query->result_array() as $row)
				{
					$options[$group][$row['channel_id']] = $row['site_label'] .
															' - ' . $row['channel_title'];
				}
			}
			else if ($group == 'tag_fields')
			{
				$query = ee()->db->query(
					"SELECT		p.tag_preference_name, f.field_label
					 FROM		exp_tag_preferences as p
					 LEFT JOIN	exp_channel_fields f
					 ON			f.field_id = p.tag_preference_value
					 WHERE		tag_preference_name
					 LIKE		'%_tag_field'
					 AND		tag_preference_value != '0'"
				);

				foreach($query->result_array() AS $q_row)
				{
					$x = explode('_', $q_row['tag_preference_name'], 2);

					foreach($channels_query->result_array() AS $row)
					{
						if ($row['channel_id'] == $x[0])
						{
							$options[$group][$row['channel_id']] = $row['site_label'] .
															' - ' . $row['channel_title'] .
															' > ' .$q_row['field_label'];
						}
					}
				}
			}
		}
		//END foreach($groups as $group => $group_label)

		// --------------------------------------------
		//  Build Harvest Location Field
		// --------------------------------------------

		$this->cached_vars['groups'] 					= $groups;
		$this->cached_vars['options'] 					= $options;

		//	----------------------------------------
		//	 Batch Size for Processing
		//	----------------------------------------

		$this->cached_vars['per_batch_options'] 		= array(1, 50, 100, 250, 500, 1000);

		$this->cached_vars['lang_harvest_description'] 	= lang('harvest_description');

		//	----------------------------------------
		//	 Build page
		//	----------------------------------------

		$this->cached_vars['module_menu_highlight'] 	= 'module_utilities';

		$this->add_crumb(lang('utilities'), $this->base . AMP . 'method=utilities');

		$this->add_crumb(lang('tag_harvest'));

		$this->cached_vars['message'] 					= $message;

		$this->cached_vars['form_url'] 					= $this->base . AMP . 'method=process_harvest';
		$this->cached_vars['tag_groups']				= $this->data->get_tag_groups();

		$lang_items = array(
			'tag_harvest',
			'per_batch',
			'harvest_sources',
			'tag_group'
		);

		foreach ($lang_items as $item)
		{
			$this->cached_vars['lang_' . $item] = lang($item);
		}

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('harvest_form.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}

	/* END harvest() */

	// --------------------------------------------------------------------

	/**
	 *	Process Harvest Request or Refresh
	 *
	 *	@access		public
	 *	@return		string
	 */

	public function process_harvest()
	{
		$this->cached_vars['module_menu_highlight'] = 'module_harvest';

		// --------------------------------------------
		//  Do Our Harvesting
		// --------------------------------------------

		$return = $this->_harvest();

		// --------------------------------------------
		//  Are We Finished?
		// --------------------------------------------

		if ( ! in_array(FALSE, $return['done']))
		{
			return $this->harvest(lang('success_harvest_processing_is_complete'));
		}

		$this->cached_vars['hidden_fields'] = array();

		// --------------------------------------------
		//  Harvest Sources for this Batch
		// --------------------------------------------

		foreach($return['done'] as $type => $finished)
		{
			if ($finished === TRUE) continue;

			foreach($return['harvest_sources'] as $harvest_source)
			{
				if ( ! stristr($harvest_source, $type)) continue;

				$this->cached_vars['hidden_fields'][] = array('harvest_sources[]', $harvest_source);
			}
		}

		// --------------------------------------------
		//  Batch Number and Per Batch Amount
		// --------------------------------------------

		$return['batch']++; // Next Batch!

		$this->cached_vars['hidden_fields'][] = array('batch', $return['batch']);

		$this->cached_vars['hidden_fields'][] = array('per_batch', $return['per_batch']);

		$this->cached_vars['hidden_fields'][] = array('tag_group', $return['tag_group']);

		// --------------------------------------------
		//  Set All Return Variables to View Variables and Call Batch Page
		// --------------------------------------------

		$this->cached_vars = array_merge($this->cached_vars, $return);

		$this->add_crumb(lang('tag_harvest_batch_process'));

		//--------------------------------------
		//  menus and page content
		//--------------------------------------

		$this->cached_vars['current_page'] = $this->view('harvest_batch_form.html', NULL, TRUE);

		//---------------------------------------------
		//  Load Homepage
		//---------------------------------------------

		return $this->ee_cp_view('index.html');
	}
	/* END process_harvest() */

	// --------------------------------------------------------------------

	/**
	 *	The Harvest Processing Routine
	 *
	 *	The actual processing work is done here, and then we keep process_harvest() a bit cleaner
	 *	for displaying the page for more batches.
	 *
	 *	@access		private
	 *	@return		array
	 */

	private function _harvest( )
	{
		// --------------------------------------------
		//  Data Validation
		// --------------------------------------------

		if (ee()->input->get_post('harvest_sources') === FALSE)
		{
			return $this->harvest();
		}

		if ( isset($_POST['harvest_sources']))
		{
			$harvest_sources = (! is_array($_POST['harvest_sources'])) ?
				array($_POST['harvest_sources']) : $_POST['harvest_sources'];
		}

		if ( isset($_GET['harvest_sources']))
		{
			$harvest_sources = explode('|', $_GET['harvest_sources']);
		}

		if ( count($harvest_sources) == 0)
		{
			return $this->harvest();
		}

		$per_batch	= ( ee()->input->get_post('per_batch') === FALSE OR
						! is_numeric( ee()->input->get_post('per_batch'))) ?
							250 : ee()->input->get_post('per_batch');

		$batch		=  ( is_numeric(ee()->input->get_post('batch'))) ? ee()->input->get_post('batch') : 1;

		$done		= array();

		// --------------------------------------------
		//  Find Out What We're Parsing
		// --------------------------------------------

		$harvest_types = array();

		foreach($harvest_sources as $harvest_source)
		{
			foreach(array('channel_categories', 'tag_fields') as $type)
			{
				if (stristr($harvest_source, $type))
				{
					$harvest_types[$type][] = str_replace($type.'_', '', $harvest_source);
				}
			}
		}

		// --------------------------------------------
		//  Switch to the DB's Character Set from the Tag Character Set
		// --------------------------------------------



		// --------------------------------------------
		//  Let's Prepare for Some Parsing!
		// --------------------------------------------

		$data  = array();
		$total = 0;

		foreach($harvest_types as $harvest_type => $harvest_items)
		{
			$done[$harvest_type] = FALSE;
			$data[$harvest_type] = array();

			//	----------------------------------------
			//	Query Channel Categories
			//	----------------------------------------

			if ( $harvest_type == 'channel_categories')
			{
				$sql	= "SELECT 		%sql
						   FROM 		{$this->sc->db->channel_titles} AS wt
						   LEFT JOIN 	exp_category_posts cp
						   ON 			wt.entry_id = cp.entry_id
						   LEFT JOIN 	exp_categories c
						   ON 			c.cat_id = cp.cat_id
						   WHERE 		wt.{$this->sc->db->channel_id}
						   IN 			('".implode( "','", ee()->db->escape_str($harvest_items) )."')";

				//	----------------------------------------
				//	 Check Total
				//	----------------------------------------

				$query	= ee()->db->query( str_replace( "%sql", "COUNT(*) AS count", $sql ) );
				$query_row = $query->row_array();

				if ($query_row['count'] == 0)
				{
					$done[$harvest_type] = TRUE;
					continue;
				}

				if ($query_row['count'] > $total)
				{
					$total = $query_row['count'];
				}

				//	----------------------------------------
				//	Get data
				//	----------------------------------------

				$sql	.= " ORDER BY entry_id ASC LIMIT " . ( ( $batch - 1 ) * $per_batch ).",".$per_batch;

				$query	= ee()->db->query(
					str_replace(
						"%sql",
						"DISTINCT wt.entry_id, wt.site_id, wt.{$this->sc->db->channel_id}, c.cat_name",
						$sql
					)
				);

				if ($query->num_rows() == 0)
				{
					$done[$harvest_type] = TRUE;
					continue;
				}
				elseif($query->num_rows() < $per_batch OR $query_row['count'] == ( $batch * $per_batch ))
				{
					$done[$harvest_type] = TRUE;
				}

				//	----------------------------------------
				//	Prep data
				//	----------------------------------------

				$entries	= array();

				foreach ( $query->result_array() as $row )
				{
					if ( trim($row['cat_name']) == '' ) continue;
					$entries[ $row['entry_id'] ][ $this->sc->db->channel_id ]	= $row[$this->sc->db->channel_id];
					$entries[ $row['entry_id'] ][ 'site_id' ]	= $row['site_id'];
					$entries[ $row['entry_id'] ][ 'str' ][]		= stripslashes($row['cat_name']);
				}

				$data[$harvest_type] = $entries;
			}
			elseif ( $harvest_type == 'tag_fields' )
			{
				//	----------------------------------------
				//	Discover Our Fields
				//	----------------------------------------

				$fields	= array();

				$query = ee()->db->query(
					"SELECT tag_preference_name, tag_preference_value
					 FROM 	exp_tag_preferences
					 WHERE 	tag_preference_name
					 LIKE 	'%_tag_field'
					 AND 	tag_preference_value != '0'"
				);


				foreach($query->result_array() AS $row)
				{
					foreach($harvest_items as $channel_id)
					{
						if ($row['tag_preference_name'] == $channel_id.'_tag_field')
						{
							$fields[$channel_id] = $row['tag_preference_value'];
						}
					}
				}

				// --------------------------------------------
				//  Validate Fields - They Might Have Deleted Since Saving Preferences?
				// --------------------------------------------

				$query = ee()->db->query(
					"SELECT COUNT(*) AS count
					 FROM 	{$this->sc->db->channel_fields}
					 WHERE 	field_id
					 IN 	('" . implode("','", ee()->db->escape_str(array_unique($fields))) . "')"
				);

				if ($query->row('count') != count(array_unique(array_values($fields))))
				{
					$this->add_crumb(lang('error'));
					$this->cached_vars['error_message'] = lang('error_invalid_custom_fields_for_channels');

					return $this->ee_cp_view('error_page.html');
				}

				//	----------------------------------------
				//	 Initial Query of Data Retrieval
				//	----------------------------------------

				$sql	= "SELECT 		%sql
						   FROM 		{$this->sc->db->channel_titles} AS wt
						   LEFT JOIN 	{$this->sc->db->channel_data} AS wd
						   ON 			wt.entry_id = wd.entry_id
						   WHERE		wt.{$this->sc->db->channel_id}
						   IN 			('".implode( "','", ee()->db->escape_str(array_keys($fields)) )."')";

				//	----------------------------------------
				//	 Check Total
				//	----------------------------------------

				$query	= ee()->db->query( str_replace( "%sql", "COUNT(*) AS count", $sql ) );
				$query_row = $query->row_array();

				if ($query_row['count'] == 0)
				{
					$done[$harvest_type] = TRUE;
					continue;
				}

				if ($query_row['count'] > $total)
				{
					$total = $query_row['count'];
				}

				//	----------------------------------------
				//	Get data
				//	----------------------------------------

				$sql	.= " ORDER BY entry_id ASC LIMIT ".(( $batch - 1 ) * $per_batch).",".$per_batch;

				$query	= ee()->db->query(
					str_replace(
						"%sql",
						"wt.entry_id, wt.site_id, wt.{$this->sc->db->channel_id}, wd.field_id_" .
							implode( ", wd.field_id_", $fields ),
						$sql
					)
				);

				// There is nothing to harvest, so we are done!
				if ($query->num_rows() == 0)
				{
					$done[$harvest_type] = TRUE;
					continue;
				}
				// The number left is less than or equal to the number per batch, so this is the last batch!
				elseif($query->num_rows() < $per_batch OR $query_row['count'] == ( $batch * $per_batch ))
				{
					$done[$harvest_type] = TRUE;
				}

				//	----------------------------------------
				//	Prep data
				//	----------------------------------------

				$entries	= array();

				foreach ( $query->result_array() as $row )
				{
					if ( ! isset( $fields[ $row[$this->sc->db->channel_id] ] ) ) continue;

					$id	= 'field_id_'.$fields[ $row[$this->sc->db->channel_id] ];

					if ( $row[ $id ] == '' ) continue;

					$entries[ $row['entry_id'] ][ $this->sc->db->channel_id ]	= $row[$this->sc->db->channel_id];
					$entries[ $row['entry_id'] ][ 'site_id' ]					= $row['site_id'];
					$entries[ $row['entry_id'] ][ 'str' ]						= $row[$id];
				}

				$data[$harvest_type] = $entries;
			}
		}

		// --------------------------------------------
		//  Commence Parsing!
		// --------------------------------------------

		if ( ! class_exists('Tag') )
		{
			require $this->addon_path.'mod.tag.php';
		}

		foreach($data as $harvest_type => $entries)
		{
			if ( $harvest_type == 'channel_categories')
			{
				$Tag = new Tag();

				foreach ( $entries as $key => $val )
				{
					$Tag->remote		= FALSE;
					$Tag->batch			= TRUE;
					$Tag->entry_id		= $key;
					$Tag->site_id		= $val['site_id'];
					$Tag->channel_id	= $val[$this->sc->db->channel_id];
					$Tag->str			= implode( "\n", $val['str'] );
					$Tag->tag_group_id  = ee()->input->post('tag_group');
					$Tag->parse();
				}
			}
			elseif ( $harvest_type == 'tag_fields' )
			{
				$Tag	= new Tag();

				foreach ( $entries as $key => $val )
				{
					$Tag->remote		= FALSE;
					$Tag->batch			= TRUE;
					$Tag->entry_id		= $key;
					$Tag->content_id	= $Tag->channel_id = $val[$this->sc->db->channel_id];
					$Tag->site_id		= $val['site_id'];
					$Tag->str			= $val['str'];
					$Tag->tag_group_id  = ee()->input->post('tag_group');
					$Tag->parse();
				}
			}
		}

		//	----------------------------------------
		//	Return
		//	----------------------------------------

		return array(
			'done'				=> $done,
			'harvest_sources'	=> $harvest_sources,
			'batch'				=> $batch,
			'per_batch'			=> $per_batch,
			'total'				=> round($total/$per_batch),
			'tag_group'			=> ee()->input->post('tag_group')
		);
	}
	/* END _harvest() */


	//	----------------------------------------
	//	Recount Tag Statistics
	//	---------------------------------------

	public function recount( $return = TRUE )
	{


		// --------------------------------------------
		//	Set num per batch and start
		// --------------------------------------------

		$num	= ( ee()->input->get_post('num') !== FALSE AND is_numeric( ee()->input->get_post('num') ) === TRUE ) ? ee()->input->get_post('num'): 1000;
		$start	= ( ee()->input->get_post('start') !== FALSE AND is_numeric( ee()->input->get_post('start') ) === TRUE ) ? ee()->input->get_post('start'): 0;

		//	----------------------------------------
		//	 Check Totals
		//	----------------------------------------

		$countq		= ee()->db->query( "SELECT COUNT(*) AS count FROM exp_tag_tags" );
		$remainingq	= ee()->db->query( "SELECT site_id FROM exp_tag_tags LIMIT ".ee()->db->escape_str( $start ).",".ee()->db->escape_str( $num ) );

		//	----------------------------------------
		//	Any tags at all?
		//	----------------------------------------

		if ( $countq->num_rows() == 0 OR $countq->row('count') == 0 )
		{
			ee()->functions->bounce( $this->base.'msg=no_tags_to_recount');
			exit;
		}

		//	----------------------------------------
		//	Are we done?
		//	----------------------------------------

		if ( $remainingq->num_rows() == 0 )
		{
			ee()->functions->bounce( $this->base.'msg=tags_successfully_recounted');
			exit;
		}

		// --------------------------------------------
		//	Is this our first pass through?
		// --------------------------------------------

		if ( $start == 0 )
		{
			// --------------------------------------------
			//  Old Entries Not Removed in Previous Versions
			// --------------------------------------------

			// Disabled because it was deleting Tags from entries submitted via SAEF from a Guest member.
			//ee()->db->query("DELETE FROM exp_tag_entries WHERE exp_tag_entries.author_id = 0");

			ee()->db->query("DELETE te
							FROM exp_tag_entries AS te
							LEFT JOIN exp_members AS m ON te.author_id = m.member_id
							WHERE te.author_id != 0
							AND m.member_id IS NULL");

			ee()->db->query("DELETE te FROM exp_tag_entries AS te
							LEFT JOIN {$this->sc->db->channel_titles} AS wt ON te.entry_id = wt.entry_id
							WHERE te.type = 'channel'
							AND wt.entry_id IS NULL");

			//	----------------------------------------
			//	Remove Orphans
			//	----------------------------------------

			ee()->db->query("DELETE tt
							FROM exp_tag_tags AS tt
							LEFT JOIN exp_tag_entries AS te ON te.tag_id = tt.tag_id
							WHERE te.tag_id IS NULL");
		}

		//	----------------------------------------
		//	Recount stats for all existing tags
		//	----------------------------------------

		$query	= ee()->db->query( "SELECT tag_id FROM exp_tag_tags LIMIT ".ee()->db->escape_str( $start ).",".ee()->db->escape_str( $num ) );

		$tags	= array();

		foreach ( $query->result_array() as $row )
		{
			$tags[]	= $row['tag_id'];
		}

		$this->_recount( $tags );

		//	----------------------------------------
		//	Loop and refresh page
		//	----------------------------------------

		$start	+=	$num;

		$url	= $this->base.'P='.'recount'.AMP.'num='.$num.AMP.'start='.$start;

		$data	= array(
						'title'		=> lang('recount'),
						'heading'	=> lang('recount'),
						'content'	=> str_replace( array( '%num', '%start', '%total' ), array( $num, $start, $countq->row('count') ), lang('tag_recount_running') ),
						'rate'		=> 2,
						'link'		=> array( $url, 'click here to get there' ),
						'redirect'	=> $url
						);

		ee()->output->show_message( $data );
	}
	/* END recount() */


	//	----------------------------------------
	//	Recount
	//	---------------------------------------

	public function _recount( $tags = array() )
	{
		if ( class_exists('Tag') === FALSE )
		{
			require $this->addon_path.'mod.tag'.EXT;
		}

		// $Tag	= new Tag;

		Tag::_recount( array( 'tag_id' => $tags ) );
	}

	/* END recount */


	//	----------------------------------------
	//	AJAX tag browse
	//	---------------------------------------

	public function tag_browse()
	{
		ee()->lang->loadfile( 'tag' );

		//	----------------------------------------
		//	Handle existing
		//	----------------------------------------

		$existing	= array();

		if ( ee()->input->get_post('existing') !== FALSE )
		{
			$existing	= explode( "||", ee()->security->xss_clean(ee()->input->get_post('existing')) );
		}

		//	----------------------------------------
		//	Query and construct
		//	----------------------------------------



		$extra = '';

		if (ee()->input->get_post('msm_tag_search') !== 'y')
		{
			$extra = " AND site_id = '".$this->clean_site_id."'";
		}

		if (ee()->input->get_post('str') == '*')
		{
			$query	= ee()->db->query("SELECT DISTINCT tag_name AS name
									   FROM exp_tag_tags
									   WHERE tag_name NOT IN ('".implode( "','", ee()->db->escape_str( $existing ) )."')
									   {$extra}
									   ORDER BY tag_name" );
		}
		else
		{
			$str 	= $this->_clean_str( ee()->input->get_post('str') );

			$query	= ee()->db->query("SELECT DISTINCT tag_name AS name
									   FROM exp_tag_tags
									   WHERE tag_alpha = '".ee()->db->escape_str( $this->_first_character($str) )."'
									   AND tag_name LIKE '".ee()->db->escape_str( $str )."%'
									   AND tag_name NOT IN ('".implode( "','", ee()->db->escape_str( $existing ) )."')
									   {$extra}
									   ORDER BY tag_name" );
		}



		if ( $query->num_rows() == 0 )
		{
			$select = '<div class="message"><p>'.lang('no_matching_tags').'</p></div>';
		}
		else
		{
			$select	= '<ul>';

			foreach ( $query->result_array() as $row )
			{
				$select	.= '<li><a href="#">'.$row['name']."</a></li>";
			}

			$select	.= '</ul>';
		}

		@header("HTTP/1.0 200 OK");
		@header("HTTP/1.1 200 OK");

		exit($select);
	}
	// END AJAX browse


	// --------------------------------------------------------------------

	/**
	 * tag_autocomplete
	 *
	 * @access	public
	 * @return  null
	 */

	public function tag_autocomplete()
	{
		return $this->actions()->tag_autocomplete(array('tag_name'));
	}
	//END tag_autocomplete()


	// --------------------------------------------------------------------

	/**
	 * tag_suggest
	 *
	 * @access	public
	 * @param	bool	return json?
	 * @return	null
	 */

	public function tag_suggest($json = FALSE)
	{
		//does a system exit
		return $this->actions()->tag_suggest($json);
	}
	// END _tag_suggest()


	// --------------------------------------------------------------------

	/**
	 * Module Upgrading
	 *
	 * This	public function is not required by the 1.x branch of ExpressionEngine by default.  However,
	 * as the install and deinstall ones are, we are just going to keep the habit and include it
	 * anyhow.
	 *
	 * @access	public
	 * @return	bool
	 */
	public function tag_module_update()
	{
		if ( ! isset($_POST['run_update']) OR $_POST['run_update'] != 'y')
		{
			$this->add_crumb(lang('update_tag_module'));
			$this->cached_vars['form_url'] = $this->base.'&method=tag_module_update';
			return $this->ee_cp_view('update_module.html');
		}

		require_once $this->addon_path.'upd.tag.php';

		$U = new Tag_upd();

		if ($U->update() !== TRUE)
		{
			return ee()->functions->redirect($this->base . AMP . 'msg=update_failure');
		}
		else
		{
			//we need to go to the ajax tag count udpate for 4.1.0+
			if ($this->version_compare($U->previous_version, '<', '4.1.0'))
			{
				return $this->update_tag_counts(lang('update_successful'), TRUE);
			}

			return ee()->functions->redirect($this->base . AMP . 'msg=update_successful');
		}
	}
	// END Tag_module_update()


	// --------------------------------------------------------------------

	/**
	 * ajax
	 *
	 * this is a passthrough function from ext in order to allow CP view
	 * calls without auto CP template wrapping in 2.x
	 *
	 * @access	public
	 * @return	string	result of passed function
	 */
	public function ajax()
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
			return '';
		}

		$method = ee()->input->get('method');

		if (method_exists($this, $method))
		{
			return $this->$method();
		}
		else
		{
			return '';
		}
	}
	//end ajax


	// --------------------------------------------------------------------

	/**
	 * _error_message
	 *
	 * shows a display error when something is wrong
	 *
	 * @access	public
	 * @return	null	sets an error message output
	 */
	public function _error_message($message)
	{
		return $this->show_error($message);
	}
	//END _error_message


	// --------------------------------------------------------------------

	/**
	 * Add Right Link override to inner link
	 *
	 * @access	public
	 * @param	string	$lang	link name
	 * @param	string	$link	href
	 */

	public function add_right_link ($lang, $link)
	{
		$this->cached_vars['inner_nav_links'][$lang] = $link;
	}
	//END add_right_link
}
// END CLASS Tag_mcp