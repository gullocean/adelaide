<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Tag - Field Type
 *
 * Handles the adding of a specific Field Type to the Publish Tabs for Tag.
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/ft.tag.php
 */

if ( ! defined('TAG_VERSION'))
{
	require_once 'constants.tag.php';
}

require_once 'addon_builder/addon_builder.php';

class Tag_ft extends EE_Fieldtype
{
	private $tag_ob;

	public	$info	= array(
		'name'		=> 'Tag',
		'version'	=> TAG_VERSION
	);

	public $field_name	= 'default';
	public $field_id	= 'default';

	public $has_array_data = TRUE;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 */

	public function __construct()
	{
		if (method_exists('EE_Fieldtype', '__construct'))
		{
			parent::__construct();
		}
		else
		{
			parent::EE_Fieldtype();
		}

		$this->EE =& get_instance();

		ee()->lang->loadfile('tag');

		$this->field_id 	= isset($this->settings['field_id']) ?
								$this->settings['field_id'] :
								$this->field_id;
		$this->field_name 	= isset($this->settings['field_name']) ?
								$this->settings['field_name'] :
								$this->field_name;
	}
	// END constructor


	// --------------------------------------------------------------------

	/**
	 * tag object setter. We dont want to set this in the constructor
	 * because updates hit it and it could make a mess.
	 *
	 * @access	private
	 * @return	object	tag object
	 */

	private function tob()
	{
		if ( ! is_object($this->tag_ob))
		{
			require_once PATH_THIRD . 'tag/mod.tag.php';

			$this->tag_ob = new Tag();
		}

		return $this->tag_ob;
	}
	//END tob()


	// --------------------------------------------------------------------

	/**
	 * get tag group
	 * if this has a tag group lets use it, otherwise this is the old style
	 * and we can just use default
	 *
	 * @access	private
	 * @return	string
	 */

	private function get_tag_group()
	{
		return (
			isset($this->settings['tag_group']) ?
				$this->settings['tag_group'] :
				1
		);
	}
	//END get_tag_group()


	// --------------------------------------------------------------------

	/**
	 * preprocess tag data
	 *
	 * @access	public
	 * @param	array 	data for preprocessing
	 * @return	string
	 */

	/*public function pre_process($data)
	{
		return $data;
	}*/
	//END pre_process


	// --------------------------------------------------------------------

	/**
	 * replace tag pair data
	 *
	 * @access	public
	 * @param	array 	data for preprocessing
	 * @param	array 	tag params
	 * @param	string 	tagdata
	 * @return	string	processed tag data
	 */

	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		if ( ! isset(ee()->TMPL))
		{
			ee()->load->library('template', null, 'TMPL');
		}

		if ( ! isset(ee()->TMPL->tagparams))
		{
			ee()->TMPL->tagparams = array();
		}

		$this->tob();

		//save old
		$old_tagdata			= ee()->TMPL->tagdata;
		$old_params				= ee()->TMPL->tagparams;

		if (empty($tagdata))
		{
			$tagdata = '{tag}, ';

			if ( ! isset($params['backspace']))
			{
				$params['backspace'] = '2';
			}
		}

		//replace to trick tag :D
		ee()->TMPL->tagdata 	= $tagdata;

		ee()->TMPL->tagparams 	= array_merge(
			array(
				'entry_id' 		=> $this->row['entry_id'],
				'tag_group_id'	=> isset($this->settings['tag_group']) ?
					$this->settings['tag_group'] :
					1
			),
			$params
		);

		//better workflow preview mode?
		if (ee()->input->get('bwf_dp') !== FALSE)
		{
			$return	= $this->tag_ob->tags(TRUE, $data);
		}
		else
		{
			$return	= $this->tag_ob->tags();
		}

		//reset
		ee()->TMPL->tagdata 	= $old_tagdata;
		ee()->TMPL->tagparams 	= $old_params;

		return $return;
	}
	//END replace_tag

	// --------------------------------------------------------------------

	/**
	 * Display Field Settings
	 *
	 * allows adding of rows to the displayed table
	 * this table api is just weird
	 *
	 * @access	public
	 * @param	array 	$settings
	 */

	public function display_settings($settings)
	{
		ee()->load->helper('form');

		//tag group
		ee()->table->add_row(
			"<label>" .
				lang('tag_group') .
			"</label>" .
			"<div class='subtext'>" .
				lang('tag_group_subtext') .
			"</div>",
			$this->tob()->view(
				'tag_group_settings.html',
				 array(
					'tag_groups'				=> $this->tob()->data->get_tag_groups(),
					'current_group_id'			=> isset($settings['tag_group']) ?
													$settings['tag_group'] :
													$this->get_tag_group(),
					'lang_insert_new_tag_group'	=> lang('insert_new_tag_group'),
					'lang_new_tag_group_name'	=> lang('new_tag_group_name'),
					'lang_cancel'				=> lang('cancel'),
					'lang_new_group_name'		=> lang('new_group_name'),
					'lang_short_name'			=> lang('short_name'),
					'id_wrapper'				=> 'ss_tag_field'
				),
				TRUE
			)
		);

		//all open
		ee()->table->add_row(
			"<label>" .
				lang('all_open') .
			"</label>" .
			"<div class='subtext'>" .
				lang('all_open_subtext') .
			"</div>",
			form_radio(array(
				'name'		=> 'all_open',
				'id'		=> 'all_open_yes',
				'value'		=> 'yes',
				'checked'	=> (
					isset($settings['all_open']) AND
					$settings['all_open'] == 'yes'
				),
			)) .
			NBS .
			form_label(lang('yes'), 'all_open_yes') .
			form_radio(array(
				'name'		=> 'all_open',
				'id'		=> 'all_open_no',
				'value'		=> 'no',
				'checked'	=> (
					! isset($settings['all_open']) OR
					$settings['all_open'] != 'yes'
				),
				'style'		=> 'margin-left:20px'
			)) .
			NBS .
			form_label(lang('no'), 'all_open_no')
		);

		//Suggest tags from group or all groups
		ee()->table->add_row(
			"<label>" .
				lang('suggest_from') .
			"</label>" .
			"<div class='subtext'>" .
				lang('suggest_from_subtext') .
			"</div>",
			form_radio(array(
				'name'		=> 'suggest_from',
				'id'		=> 'suggest_from_yes',
				'value'		=> 'all',
				'checked'	=> (
					isset($settings['suggest_from']) AND
					$settings['suggest_from'] == 'all'
				),
			)) .
			NBS .
			form_label(lang('all_groups'), 'suggest_from_yes') .
			form_radio(array(
				'name'		=> 'suggest_from',
				'id'		=> 'suggest_from_no',
				'value'		=> 'group',
				'checked'	=> (
					! isset($settings['suggest_from']) OR
					$settings['suggest_from'] != 'all'
				),
				'style'		=> 'margin-left:20px'
			)) .
			NBS .
			form_label(lang('this_group'), 'suggest_from_no')
		);

		//Suggest tags from group or all groups
		ee()->table->add_row(
			"<label>" .
				lang('top_tag_limit') .
			"</label>" .
			"<div class='subtext'>" .
				lang('top_tag_limit_subtext') .
			"</div>",
			form_input(array(
				'name'		=> 'top_tag_limit',
				'id'		=> 'top_tag_limit',
				'value'		=> (
					isset($settings['top_tag_limit']) ?
					$settings['top_tag_limit'] :
					5
				)
			))
		);
	}
	//END display_settings()

	// --------------------------------------------------------------------

	/**
	 * save_settings
	 * @access	public
	 * @return	string
	 */

	public function save_settings($data)
	{
		$this->tob();

		//check tag group
		$tag_group_id = 1;

		//new group id?
		if (ee()->input->get_post('new_tag_group_name') AND
			trim(ee()->input->get_post('new_tag_group_name')) != '')
		{
			$tag_group_name = ee()->input->get_post('new_tag_group_name');

			//lets make sure it worked
			if ($new_group_id = $this->tag_ob->data->insert_new_tag_group($tag_group_name))
			{
				$tag_group_id = $new_group_id;
			}
			else
			{
				return $this->tob()->show_error(lang('tag_group_name_taken'));
			}
		}
		else if (is_numeric(ee()->input->get_post('tag_group')) AND
				ee()->input->get_post('tag_group') > 0)
		{
			$tag_group_id = ee()->input->get_post('tag_group');
		}

		return array(
			'all_open' 		=> ($this->tag_ob->check_yes(ee()->input->get_post('all_open')) ? "yes" : "no"),
			'suggest_from'	=> (ee()->input->get_post('suggest_from') === 'all' ? "all" : "group"),
			'tag_group'		=> $tag_group_id,
			'field_name'	=> ee()->input->get_post('field_name'),
			'top_tag_limit'	=> (ctype_digit((string) ee()->input->get_post('top_tag_limit')) ?
									ee()->input->get_post('top_tag_limit') : '5')
		);
	}
	//END save_settings()


	// --------------------------------------------------------------------

	/**
	 * displays field for publish/saef
	 *
	 * @access	public
	 * @param	string	$data	any incoming data from the channel entry
	 * @return	string	html output view
	 */

	public function display_field($data)
	{
		$output = "";

		$this->tob();

		// --------------------------------------------
		//  Add in our JavaScript/CSS
		// --------------------------------------------

		$ac_js		= $this->tag_ob->data->tag_field_autocomplete_js();

		$tag_css 	= $this->tag_ob->data->tag_field_css();

		$tag_js		= $this->tag_ob->data->tag_field_js();

		$front_css 	= $this->tag_ob->data->tag_front_css();

		$ss_cache	=& ee()->sessions->cache['solspace'];

		//prevent double loading in case this is used more than once
		//jquery autocomplete js
		if ( ! isset($ss_cache['scripts']['jquery']['tag_autocomplete']))
		{
			if (REQ == 'CP' AND is_object(ee()->cp))
			{
				//ee()->cp->add_to_head($ac_css);
				ee()->cp->add_to_foot($ac_js);
			}
			else
			{
				$output .= $ac_js . "\n";
			}

			$ss_cache['scripts']['jquery']['tag_autocomplete'] = TRUE;
		}

		//jquery autocomplete js
		if ( ! isset($ss_cache['scripts']['tag']['field']))
		{
			if (REQ == 'CP' AND is_object(ee()->cp))
			{
				ee()->cp->add_to_head($tag_css);
				ee()->cp->add_to_foot($tag_js);
			}
			else
			{
				$output .= $tag_css . "\n" . $tag_js . "\n" . $front_css . "\n";
			}

			$ss_cache['scripts']['tag']['field'] = TRUE;
		}


		//--------------------------------------------
		//	views widgets, whatever
		//--------------------------------------------

		$output 	.= $this->tag_ob->field_type_widget(
			array(
				'field_data' 	=> $data,
				'field_name'	=> ($this->field_name == 'default') ?
										'tag_f' :
										$this->field_name,
				'field_id'		=> $this->field_id,
				'tag_group_id'	=> $this->get_tag_group(),
				'all_open'		=> (isset($this->settings['all_open']) ?
										$this->settings['all_open'] : 'no'),
				'top_tag_limit' => (isset($this->settings['top_tag_limit']) ?
										$this->settings['top_tag_limit'] : 5),
				'suggest_from'	=> (isset($this->settings['suggest_from']) ?
										$this->settings['suggest_from'] : 'group')
			)
		);

		return $output;
	}
	// END display_field()


	// --------------------------------------------------------------------

	/**
	 * delete. gets called when entries are deleted
	 *
	 * @access	public
	 * @param	array	$ids ids of the entries being deleted
	 * @return	null
	 */

	public function delete($ids)
	{
		$this->tob()->delete($ids);
	}
	//ENd delete


	// --------------------------------------------------------------------

	/**
	 * post_save. we arent using the intial save() because it doesn't
	 * have the entry id available yet, so it's somewhat useless to us here
	 *
	 * @access	public
	 * @param	string	$data	any incoming data from the channel entry
	 * @return	null	html output view
	 */

	public function post_save($data)
	{
		$this->tob();

		$this->tag_ob->site_id			= ee()->config->item('site_id');
		$this->tag_ob->entry_id			= $this->settings['entry_id'];
		$this->tag_ob->str				= $data;
		$this->tag_ob->from_ft			= TRUE;
		$this->tag_ob->field_id			= $this->field_id;
		$this->tag_ob->tag_group_id		= $this->get_tag_group();
		$this->tag_ob->type				= 'channel';
		//everything is stored hidden as newline separation
		$this->tag_ob->separator_override = 'newline';

		$this->tag_ob->parse();

		return;
	}
	//END post_save
}
// END Tag_ft class

// End of file ft.tag.php