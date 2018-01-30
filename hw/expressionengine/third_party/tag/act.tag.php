<?php if ( ! defined('EXT') ) exit('No direct script access allowed');

/**
 * Tag - Actions
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/act.tag.php
 */

require_once 'addon_builder/addon_builder.php';

class Tag_actions extends Addon_builder_tag {

	public	$current_char_set 	= '';
	public	$clean_site_id		= 1;


	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	null
	 */

	public function __construct()
	{
		parent::__construct();

		// 2.x installs are all utf-8

		$this->current_char_set = 'utf-8';

		$this->clean_site_id = ee()->db->escape_str(ee()->config->item('site_id'));
	}
	// END constructor


	// --------------------------------------------------------------------

	/**
	 * merges the data for two tags, the first being the dominant
	 * @access 	public
	 * @param	(string) Tag that getting merged to (dominant)
	 * @param	(string) Tag that getting merged in	(recessive)
	 * @param	(int) 	 site_id in case we need to loop this for everything
	 * @return 	(bool)	 success?
	 */

	public function merge_tags($to_tag = '', $from_tag = '', $site_id = 0)
	{
		//cant work with blanks
		if ( $to_tag === '' OR $from_tag === '') return FALSE;

		//clean site_id
		$site_id 	= ee()->db->escape_str(
			(is_numeric($site_id) AND $site_id != 0) ? $site_id : ee()->config->item('site_id')
		);

		//--------------------------------------------
		//	get tag_ids
		//--------------------------------------------

		//have to use binary here because subsequent
		//spaces count as the same as none
		//and we could get false positives
		$tquery = ee()->db->query(
			"SELECT tag_id, tag_name
			 FROM 	exp_tag_tags
			 WHERE	site_id = $site_id
			 AND	BINARY tag_name
			 IN		('" . ee()->db->escape_str($to_tag) . "',
					 '" . ee()->db->escape_str($from_tag) . "')"
		);

		//did we get both results? cannot do anything with one
		if ($tquery->num_rows() < 2 )
		{
			return FALSE;
		}

		//--------------------------------------------
		//	set ids for later use
		//--------------------------------------------

		$from_id = 0;
		$to_id	 = 0;

		//this should only ever be 2 ..we hope
		foreach ($tquery->result_array() as $row)
		{
			if ($row['tag_name'] == $to_tag)
			{
				$to_id = $row['tag_id'];
			}

			if ($row['tag_name'] == $from_tag)
			{
				$from_id = $row['tag_id'];
			}
		}

		//--------------------------------------------
		//	convert tag entries
		//--------------------------------------------

		$from_data_query = ee()->db->query(
			"SELECT entry_id, type
			 FROM 	exp_tag_entries
			 WHERE	site_id = $site_id
			 AND	tag_id 	= '" .  ee()->db->escape_str($from_id) . "'"
		);

		//if there are any entries, lets convert them
		if ($from_data_query->num_rows() > 0)
		{
			$entry_ids = array();

			//seperate data by id and type
			//because the entry tables could have the same ID
			foreach($from_data_query->result_array() as $row)
			{
				$entry_ids[$row['type']][] = $row['entry_id'];
			}

			$sql = "SELECT 	entry_id, type
					FROM 	exp_tag_entries
					WHERE	site_id = $site_id
					AND		tag_id 	= '" .  ee()->db->escape_str($to_id) . "'";

			//need to check entry_id AND type because there is no primary key
			$first = TRUE;

			$sql .= ' AND (';

			foreach ($entry_ids as $type => $type_ids)
			{
				if ($first)
				{
					$first 	= FALSE;
				}
				else
				{
					$sql 	.= ' OR ';
				}

				$sql .= " ( type = '" . ee()->db->escape_str($type) . "' AND
							entry_id IN (" . implode(',', ee()->db->escape_str($type_ids)) . ") )";
			}

			//ends the paran from AND (
			$sql .= " )";

			$to_data_query = ee()->db->query($sql);

			//if there are any matches, then we already have
			//tagged this entry and dont need to convert
			//so we remove the matching items from the array of items to convert
			if ($to_data_query->num_rows() > 0)
			{
				$tagged_ids = array();

				foreach ($to_data_query->result_array() as $row)
				{
					$tagged_ids[$row['type']][] = $row['entry_id'];
				}

				foreach ($entry_ids as $type => $type_ids)
				{
					if (isset($tagged_ids[$type]))
					{
						$entry_ids[$type] = array_diff_assoc($entry_ids[$type], $tagged_ids[$type]);
					}
				}
			}

			//now with our cleaned arrays, we need to update the unique tags to be the to_tags id
			foreach ($entry_ids as $type => $type_ids)
			{
				if (empty($type_ids)) continue;

				ee()->db->query(
					ee()->db->update_string(
						'exp_tag_entries',
						array(
							'tag_id' => $to_id
						),
						"site_id 	= $site_id	AND
						 type 		= '" . ee()->db->escape_str($type) . "' AND
						 tag_id		= '" . ee()->db->escape_str($from_id) . "' AND
						 entry_id 	IN (" . implode(',', ee()->db->escape_str($type_ids)) . ")"
					)
				);
			}
		}

		//--------------------------------------------
		//	cleanup (by id, tis unique)
		//--------------------------------------------

		//remove from_tag
		ee()->db->query(
			"DELETE FROM exp_tag_tags
			 WHERE		 tag_id = '" . ee()->db->escape_str($from_id) . "'"
		);

		//remove from_tag
		ee()->db->query(
			"DELETE FROM exp_tag_entries
			 WHERE		 tag_id = '" . ee()->db->escape_str($from_id) . "'"
		);

		//recount main tag
		$this->recount_tags($to_id);

		return TRUE;
	}
	//end merge tags


	// --------------------------------------------------------------------

	/**
	 * resets tag counts in the db
	 * @access 	public
	 * @param	(array/string) array of tag ids or a singular tag id
	 * @return 	(null)
	 */

	public function recount_tags($tag_ids = array())
	{
		//array?
		if ( ! is_array($tag_ids))
		{
			if (is_numeric($tag_ids))
			{
				$tag_ids = array($tag_ids);
			}
			else
			{
				return;
			}
		}

		//cannot work without data
		if ( count( $tag_ids ) == 0 ) return;

		// ----------------------------------------
		// Zero out
		// ----------------------------------------

		$default_array = array(
			'total_entries'		=> 0,
			'channel_entries'	=> 0,
			//'gallery_entries'	=> 0
		);

		$tag_groups = array();

		//add all tag groups to these counts

		$tag_groups = $this->data->get_tag_groups();

		//fill in the default_array for zero out
		foreach ($tag_groups as $id => $name)
		{
			$default_array['total_entries_' . $id] = 0;
		}

		foreach ( $tag_ids as $tag_id )
		{
			ee()->db->query(
				ee()->db->update_string(
					'exp_tag_tags',
					$default_array,
					array(
						'tag_id' => $tag_id
					)
				)
			);
		}

		//	----------------------------------------
		//	Get counts
		//	----------------------------------------

		$query	= ee()->db->query(
			"SELECT tag_id, type, tag_group_id
			 FROM 	exp_tag_entries
			 WHERE  tag_id
			 IN 	('" . implode( "','", ee()->db->escape_str($tag_ids) ) . "')"
		);

		//	----------------------------------------
		//	Array counts
		//	----------------------------------------

		$counts	= array();

		foreach ( $query->result_array() as $row )
		{
			$counts[ $row['tag_id'] ][ $row['type'] ][]	= 1;

			//tag group counts?

			$counts[ $row['tag_id'] ][ 'total_entries_' . $row['tag_group_id'] ][]	= 1;
		}

		//	----------------------------------------
		//	Update counts
		//	----------------------------------------

		foreach ( $counts as $key => $val )
		{
			$data = array();

			$data['channel_entries']	= ( isset( $val['channel'] ) ) ? count( $val['channel'] ) : 0;
			$data['total_entries']		= $data['channel_entries'];// + $data['gallery_entries'];

			//tag group counts?
			foreach ($tag_groups as $id => $name)
			{
				$data[ 'total_entries_' . $id ]	= ( isset( $val['total_entries_' . $id ] ) ) ?
													count( $val['total_entries_' . $id ] ) : 0;
			}

			ee()->db->query(
				ee()->db->update_string(
					'exp_tag_tags',
					$data,
					array(
						'tag_id' => $key
					)
				)
			);
		}
	}
	//END recount_tags


	// --------------------------------------------------------------------

	/**
	 *	Tag Auto-Complete
	 *
	 *	Used for AJAX requests on the CP and User-side to help with Tag completions
	 *
	 *	@access		public
	 *	@return		array  - Of tag_ids
	 */

	public function tag_autocomplete($fields = array('tag_name'), $headers = TRUE)
	{
		$output			= '';
		$return_type 	= $this->either_or(ee()->input->get_post('return_type'), 'text');

		//----------------------------------------
		//	Handle existing
		//----------------------------------------

		$existing = array();

		$current_tags = $this->either_or(
			ee()->input->get_post('current_tags'),
			ee()->input->get_post('tag__current_tags'),
			FALSE
		);

		if ($current_tags)
		{
			//--------------------------------------------
			//  Delimiter
			//--------------------------------------------

			//get delim based on name. first from get_post or fallbacks
			$delim = $this->data->get_tag_separator($this->either_or(
				ee()->input->get_post('tag_separator'),
				$this->preference('separator'),
				'newline'
			));

			//--------------------------------------------
			//	clean, trim, unique
			//--------------------------------------------

			$existing = array_unique(
				array_map(
					'trim',
					preg_split(
						"/" . preg_quote($delim, "/") . "/",
						trim(ee()->security->xss_clean($current_tags)),
						-1,
						PREG_SPLIT_NO_EMPTY
					)
				)
			);
		}

		//----------------------------------------
		//	Query DB
		//----------------------------------------

		$fields[]	= 'tag_name';
		$fields		= array_intersect(ee()->db->list_fields('exp_tag_tags'), $fields);



		$sql = "SELECT 	" . implode(", ", $fields) . "
				FROM 	exp_tag_tags
				WHERE 	site_id = {$this->clean_site_id} ";

		if (ee()->input->get_post('tag_group_id') AND
			is_numeric(ee()->input->get_post('tag_group_id')))
		{
			$sql .= " AND tag_id IN (
						SELECT DISTINCT tag_id
						FROM	exp_tag_entries
						WHERE	tag_group_id = " . ee()->db->escape_str(
							ee()->input->get_post('tag_group_id')) . ")";
		}

		if (count($existing) > 0)
		{
			$sql .= "AND tag_name
					 NOT IN ('".implode( "','", ee()->db->escape_str( $existing ) )."') ";
		}

		// jQuery Autocomplete plugin forces the use of 'q', but we want our own name too
		$search_term = $this->either_or(
			ee()->input->get_post('q'),
			ee()->input->get_post('tag_search'),
			ee()->input->get_post('tag__search')	//*sigh* for legacy support, thats why :/
		);

		if ($search_term != '*')
		{
			$sql .= "AND tag_name LIKE '".ee()->db->escape_like_str($search_term)."%' ";
		}

		$sql .= "ORDER BY tag_name DESC LIMIT 100";

		$query = ee()->db->query($sql);



		$return_tags = array();

		foreach($query->result_array() as $row)
		{
			$return_tags[] = $row;
		}

		if ($headers)
		{
			$data = $return_tags;

			if (count($data) > 0)
			{
				$tags = array();

				foreach($data as $row)
				{
					$tags[] = $row['tag_name'];
				}

				if ($return_type == 'json')
				{
					$output = $this->json_encode(array('suggestions' => array_unique($tags)));
				}
				else
				{
					$output = implode("\n", array_unique($tags));
				}
			}

			// --------------------------------------------
			//  Headers
			// --------------------------------------------

			ee()->output->set_status_header(200);
			@header("Cache-Control: max-age=5184000, must-revalidate");
			@header('Last-Modified: '.gmdate('D, d M Y H:i:s', gmmktime()).' GMT');
			@header('Expires: '.gmdate('D, d M Y H:i:s', gmmktime() + 1).' GMT');
			@header('Content-Length: '.strlen($output));

			if ($return_type == 'json')
			{
				@header("Content-type: application/json");
			}
			else
			{
				@header("Content-type: text/plain");
			}

			exit($output);
		}

		return $return_tags;
	}
	// END tag_autocomplete()


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
		//	----------------------------------------
		//	Clean str
		//	----------------------------------------

		$str	= ( ee()->input->post('str') === FALSE ) ?
					'' :
					$this->_clean_str( ee()->input->post('str') );

		//	----------------------------------------
		//	Create array
		//	----------------------------------------

		$arr	= str_replace( "||", ' ', $str );

		//	----------------------------------------
		//	Handle existing
		//	----------------------------------------

		$existing = array();

		if ( ee()->input->get_post('existing') !== FALSE )
		{
			// --------------------------------------------
			//  Delimiter
			// --------------------------------------------

			//get delim based on name. first from get_post or fallbacks
			$delim = $this->data->get_tag_separator($this->either_or(
				ee()->input->get_post('tag_separator'),
				$this->preference('separator'),
				'newline'
			));

			$existing = explode( $delim, ee()->security->xss_clean( ee()->input->get_post('existing') ) );
		}

		//	----------------------------------------
		//	Query DB
		//	----------------------------------------



		$sql = "SELECT DISTINCT tag_name AS name
				FROM 			exp_tag_tags
				WHERE 			tag_name NOT
				IN 				('".implode( "','", ee()->db->escape_str( $existing ) )."')";

		if (ee()->input->get_post('tag_group_id') AND
			is_numeric(ee()->input->get_post('tag_group_id')))
		{
			$sql .= " AND tag_id IN (
						SELECT DISTINCT tag_id
						FROM	exp_tag_entries
						WHERE	tag_group_id = " . ee()->db->escape_str(
							ee()->input->get_post('tag_group_id')) . ")";
		}

		if (ee()->input->get_post('msm_tag_search') !== 'y')
		{
			$sql .= " AND site_id = '".$this->clean_site_id."'";
		}

		$sql .= " ORDER BY total_entries DESC LIMIT 50";

		$query = ee()->db->query($sql);



		$return_tags = array();

		if (function_exists('mb_stristr'))
		{
			foreach($query->result_array() as $row)
			{
				if (mb_stristr ( $arr, $row['name']))
				{
					$return_tags[] = $row;
				}
			}
		}
		else
		{
			foreach($query->result_array() as $row)
			{
				if (stristr ( $arr, $row['name']))
				{
					$return_tags[] = $row;
				}
			}
		}

		//	----------------------------------------
		//	Assemble string
		//	----------------------------------------

		//json output?
		if ($json OR ee()->input->get_post('return_type') == 'json')
		{
			// --------------------------------------------
			//  Headers
			// --------------------------------------------

			$return_tag_names = array();

			foreach($return_tags as $row)
			{
				$return_tag_names[] = $row['name'];
			}

			$output = $this->json_encode(array('suggestions' => $return_tag_names));

			ee()->output->set_status_header(200);
			@header("Cache-Control: max-age=5184000, must-revalidate");
			@header('Last-Modified: '.gmdate('D, d M Y H:i:s', gmmktime()).' GMT');
			@header('Expires: '.gmdate('D, d M Y H:i:s', gmmktime() + 1).' GMT');
			@header('Content-Length: '.strlen($output));
			@header("Content-type: application/json");

			exit($output);
		}

		//string output

		if ( count($return_tags) == 0 )
		{
			$return = '<div class="message"><p>'.lang('no_matching_tags').'</p></div>';
		}
		else
		{
			$return	= "<ul>";

			foreach ( $return_tags as $row )
			{
				if ($this->preference('separator') == 'space' AND stristr(' ', $row['name']))
				{
					$row['name'] = '"' . $row['name'] . '"';
				}

				$return	.= '<li><a href="#">'.$row['name'].'</a></li>';
			}

			$return	.= "</ul>";
		}

		@header("HTTP/1.0 200 OK");
		@header("HTTP/1.1 200 OK");

		exit($return);
	}
	//END tag_suggest()


	// --------------------------------------------------------------------

	/**
	 * _clean_str
	 *
	 * @access	public
	 * @param	str		string to clean
	 * @return	str
	 */

	public function _clean_str( $str = '' )
	{
		ee()->load->helper(array('text', 'security'));

		$not_allowed = array('$', '?', ')', '(', '!', '<', '>', '/');

		$str = str_replace($not_allowed, '', $str);

		$str	= ( $this->preference('convert_case') != 'n') ?
					$this->_strtolower( $str ): $str;

		if (ee()->config->item('auto_convert_high_ascii') == 'y')
		{
			ee()->load->helper('text');

			$str =  ascii_to_entities( $str );
		}

		return $str	= ee()->security->xss_clean( $str );
	}
	//END _clean_str


	// --------------------------------------------------------------------

	/**
	 * _strtolower
	 *
	 * @access	public
	 * @param	str		string to lowerizate
	 * @return	str
	 */

	public function _strtolower($str)
	{
		if (function_exists('mb_strtolower'))
		{
			return mb_strtolower($str);
		}
		else
		{
			return strtolower( $str );
		}
	}
	//END _strtolower


	// --------------------------------------------------------------------

	/**
	 * _db_charset_switch
	 * changes db from utf-8 back and forth
	 *
	 * @deprecated No longer need this
	 * @access	public
	 * @param	type	string to lowerizate
	 * @return	null
	 */

	public function _db_charset_switch($type = 'utf-8')
	{
		return;
	}
	// END _db_charset_switch
}
/* END Tag_actions Class */