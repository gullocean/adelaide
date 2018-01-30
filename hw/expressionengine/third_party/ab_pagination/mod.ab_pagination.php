<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine - by EllisLab
 *
 * @package		ExpressionEngine
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2003 - 2011, EllisLab, Inc.
 * @license		http://expressionengine.com/user_guide/license.html
 * @link		http://expressionengine.com
 * @since		Version 2.0
 * @filesource
 */
 
// ------------------------------------------------------------------------

/**
 * AB Pagination Module Front End File
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Module
 * @author		Bjørn Børresen
 * @link		http://www.wedoaddons.com
 */

class Ab_pagination {
	
	public $return_data;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();
        $this->EE->load->library('urllib');
        $this->EE->load->library('entrieslib');
	}

    public function next_entry()
    {
        return $this->find_entry('next');
    }

    public function prev_entry()
    {
        return $this->find_entry('prev');
    }

    private function find_entry($which = 'next')
    {
        $orderby = $this->EE->TMPL->fetch_param('orderby', 'entry_date');
        $entry_id = $this->EE->TMPL->fetch_param('entry_id');
        if($entry_id) {
            $entry_id = str_replace('|', ',', $entry_id);
        }
        $sort = ($which == 'next' ? 'desc' : 'asc');

        $url_title = $this->EE->urllib->get_url_title_from_segment();
        if($url_title) {
            $current_entry = $this->EE->entrieslib->get_entry_info(FALSE, $url_title);
            $current_entry_orderby_value = $current_entry[$orderby];

            $orderby = $this->EE->entrieslib->get_field_db_name($orderby);

            $q = $this->EE->db->query("SELECT * FROM ".$this->EE->db->dbprefix ."channel_titles t, ".$this->EE->db->dbprefix."channel_data d WHERE t.entry_id = d.entry_id".($entry_id?" AND t.entry_id IN(".$entry_id.")":"")." AND t.channel_id=".$current_entry['channel_id']." AND ".$orderby." " . ($which == 'next' ? '<': '>') . "= " . $this->EE->db->escape($current_entry_orderby_value) ." ORDER BY ".$orderby." ".$sort." LIMIT 0,2");

            if($q->num_rows() > 0) {
                $is_next = FALSE;
                $the_entry = FALSE;
                foreach($q->result_array() as $found_entry) {
                    if($is_next) {
                        $the_entry = $found_entry;
                        continue;
                    }
                    if($found_entry['entry_id'] == $current_entry['entry_id']) {
                        $is_next = TRUE;
                    }
                }

                if($the_entry) {
                    $tagdata = $this->EE->TMPL->tagdata;
                    $vars[0] = $this->EE->entrieslib->remap_entry_arr($the_entry);
                    $this->return_data = $this->EE->TMPL->parse_variables($tagdata, $vars);
                }
            }

        }

        return $this->return_data;
    }

}
/* End of file mod.ab_pagination.php */
/* Location: /system/expressionengine/third_party/ab_pagination/mod.ab_pagination.php */