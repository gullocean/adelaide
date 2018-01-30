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
 * AB Pagination Extension
 *
 * @package		ExpressionEngine
 * @subpackage	Addons
 * @category	Extension
 * @author		Bjørn Børresen
 * @link		http://www.addonbakery.com
 */

class Ab_pagination_ext {

	public $settings 		= array();
	public $description		= 'ExpressionEngine Pagination Fixed';
	public $docs_url		= 'http://wedoaddons.com/addon/ab-pagination/documentation';
	public $name			= 'AB Pagination';
	public $settings_exist	= 'y';
	public $version			= '1.6.4';

	private $EE;
	private $padding_left = 3;
    private $padding_right = 3;
    private $initial_size = FALSE;
    private $fixed_size = FALSE;

	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	public function __construct($settings = '')
	{
		$this->EE =& get_instance();
		$this->settings = $settings;
	}

	// ----------------------------------------------------------------------

	/**
	 * Settings Form
	 *
	 * If you wish for ExpressionEngine to automatically create your settings
	 * page, work in this method.  If you wish to have fine-grained control
	 * over your form, use the settings_form() and save_settings() methods
	 * instead, and delete this one.
	 *
	 * @see http://expressionengine.com/user_guide/development/extensions.html#settings
	 */
	public function settings()
	{
		return array(
			'ab_pagination_tag_prefix' => array('i', '', 'abp_'),
            'ab_pagination_strict_urls' => array('r', array('y' => "Yes", 'n' => "No"), 'y'),
            'ab_pagination_enable_query_strings' => array('r', array('y' => "Yes", 'n' => "No"), 'n'),
		);
	}

	// ----------------------------------------------------------------------

	/**
	 * Activate Extension
	 *
	 * This function enters the extension into the exp_extensions table
	 *
	 * @see http://codeigniter.com/user_guide/database/index.html for
	 * more information on the db class.
	 *
	 * @return void
	 */
	public function activate_extension()
	{
		// Setup custom settings in this array.
		$this->settings = array();

		$hooks = array(
			'pagination_create'	=> 'on_pagination_create',
            'template_post_parse' => 'on_template_post_parse',
		);

		foreach ($hooks as $hook => $method)
		{
			$data = array(
				'class'		=> __CLASS__,
				'method'	=> $method,
				'hook'		=> $hook,
				'settings'	=> serialize($this->settings),
				'version'	=> $this->version,
				'enabled'	=> 'y'
			);

			$this->EE->db->insert('extensions', $data);
		}
	}

	// ----------------------------------------------------------------------

    /**
     *
     * This will only run in EE 2.4+
     *
     * @param $template
     * @param $sub
     * @param $site_id
     */

    public function on_template_post_parse($template, $sub, $site_id)
    {
        $tag_prefix = isset($this->settings['ab_pagination_tag_prefix']) ? $this->settings['ab_pagination_tag_prefix'] : 'abp_';
        $tag_name = $tag_prefix.'pagination_html';

        if(isset($this->EE->config->_global_vars[$tag_name])) {
            $template = $this->EE->TMPL->advanced_conditionals(str_replace('{'.$tag_name.'}', $this->EE->config->_global_vars[$tag_name], $template ));

            if(isset($this->EE->config->_global_vars[$tag_prefix.'_all_vars'])) {
                $all_vars = unserialize($this->EE->config->_global_vars[$tag_prefix.'_all_vars']);
                foreach($all_vars as $var_name => $var_value) {
                    if(!is_array($var_value)) {
                        $template = $this->EE->TMPL->advanced_conditionals(str_replace('{'.$var_name.'}', $var_value, $template ));
                    }
                }
            }
        } else {
            $template = $this->EE->TMPL->advanced_conditionals(str_replace('{'.$tag_name.'}', '', $template )); // if no entries, we clear the {abp_pagination_html} variable
        }

        return $template;
    }

	/**
	 * on_channel_module_create_pagination
	 *
	 * @param
	 * @return
	 */
	public function on_pagination_create($ref, $count)
	{
        $tag_prefix = isset($this->settings['ab_pagination_tag_prefix']) ? $this->settings['ab_pagination_tag_prefix'] : 'abp_';
        $ab_enable_query_strings = isset($this->settings['ab_pagination_enable_query_strings']) && $this->settings['ab_pagination_enable_query_strings'] == 'y';
        $this->EE->config->_global_vars[$tag_prefix.'pagination_html'] = '';
        $paginate_location = ee()->TMPL->fetch_param('paginate');

        $offset = (!ee()->TMPL->fetch_param('offset') OR !is_numeric(ee()->TMPL->fetch_param('offset'))) ? '0' : ee()->TMPL->fetch_param('offset');
        $count  = $count - $offset;
        $per_page = ee()->TMPL->fetch_param('limit', 100);
        $paginate_base = ee()->TMPL->fetch_param('paginate_base');

        $query_string = $_SERVER['QUERY_STRING'];
        $request_uri = $_SERVER['REQUEST_URI'];

        // Pre 2.6 the global 'enable_query_strings' could be set in the config but after 2.6 this will be reset to FALSE by EE.
        // So we've added our own setting for this here
        if(($ab_enable_query_strings || $this->EE->config->item('enable_query_strings')) && $query_string)
        {
            $query_string = '?'.$query_string;
            $request_uri = str_replace($query_string, '', $request_uri);
        }
        else
        {
            $query_string = '';     // empty it if we don't have enable_query_strings set
        }

        $qm = ($this->EE->config->item('force_query_string') == 'y') ? '?' : '';

        if($this->EE->config->item('index_page') != '' && strpos($request_uri,$this->EE->config->item('index_page').$qm.'/') !== FALSE)
        {
            $strip_index = $this->EE->config->item('index_page').$qm.'/';
            $current_uri_string = trim(str_replace($strip_index, '', $request_uri),'/');
        }
        else if($this->EE->config->item('index_page') != '' && strpos($request_uri,$this->EE->config->item('index_page').$qm) !== FALSE)
        {
            $strip_index = $this->EE->config->item('index_page').$qm;
            $current_uri_string = trim(str_replace($strip_index, '', $request_uri),'/');
        }
        else
        {
            $current_uri_string = trim($request_uri,'/');
        }

        /**
         * If we have "session ID only" enabled in the CP the currrent_uri_string will be prefixed with S=blabla. We
         * strip that out here (it will be added by EE later)
         */
        if(strpos($current_uri_string,'S=') === 0) {
            $current_uri_string = substr($current_uri_string, strpos($current_uri_string,'/')+1);
        }

        $current_uri_arr = explode('/', $current_uri_string);

        $last_segment = array_pop($current_uri_arr);
        /**
         * If we have a ? then we need to strip those get variables (last_segment will be e.g. P10?sort=order at this point)
         */
        $q_pos = strpos($last_segment,'?');
        if($q_pos) {
            $last_segment = substr($last_segment, 0, $q_pos);
        }

        $site_url_arr = explode('/', trim($this->EE->config->item('site_url'),'/'));

        if(count($current_uri_arr) > 0 && $current_uri_arr[0] == array_pop($site_url_arr))
        {
            $remove_segment = $current_uri_arr[0].'/';
            if(strpos($current_uri_string,$remove_segment) !== FALSE && strpos($current_uri_string,$remove_segment) == 0)
            {
                $current_uri_string = substr($current_uri_string, strlen($remove_segment));
            }
        }

        $current_page_num = 0;

        if(substr($last_segment,0,1) == 'P') // might be a pagination page indicator
        {
            $end = substr($last_segment, 1, strlen($last_segment));
            if ((preg_match( '/^\d*$/', $end, $matches) == 1))
            {
                $current_uri_string = substr($current_uri_string, 0, strrpos($current_uri_string,'/'));
                $current_page_num = $matches[0] / $per_page;
            }


        }

        if($current_page_num > ($count/$per_page)) {
            $ref->template_data = '';

            if(isset($this->settings['ab_pagination_strict_urls']) && $this->settings['ab_pagination_strict_urls'] == 'y') {
                $ref->template_data = $this->EE->TMPL->parse($this->EE->TMPL->fetch_template('', '', FALSE));      // 404
            }

            return;
        }


        $paginate_data = $ref->template_data;

        $matches = array();             // (switch\s*=.+?)
        if(preg_match_all('/\{'.$tag_prefix.'pages( (\w+)\=["|\']([0-9|no]+)["|\'])?( (\w+)\=["|\']([0-9|no]+))?["|\']( (\w+)\=["|\']([0-9|no]+)["|\'])?( (\w+)\=["|\']([0-9|no]+)["|\'])?\}/i', $paginate_data, $matches, PREG_SET_ORDER))
        {
            $current_index = 2;

            $found_matches = $matches[0];

            while($current_index < count($found_matches))
            {
                switch($found_matches[$current_index])
                {
                    case 'padding':
                        $this->padding_left = $this->padding_right = $found_matches[$current_index+1];
                        break;

                    case 'padding_left':
                        $this->padding_left = intval($found_matches[$current_index+1]);
                        break;

                    case 'padding_right':
                        $this->padding_right = intval($found_matches[$current_index+1]);
                        break;

                    case 'initial_size':
                        $this->initial_size = intval($found_matches[$current_index+1]);
                        break;

                    case 'fixed_size':
                        $this->fixed_size = intval($found_matches[$current_index+1]);
                        break;
                }

                $current_index += 3;
            }
        }

        $pages = array();

        if($paginate_base)
        {
            $pagination_base_url = $this->EE->functions->create_url(trim_slashes($this->EE->TMPL->fetch_param('paginate_base')));
        }
        else
        {
            $pagination_base_url = trim($this->EE->functions->create_url($current_uri_string), '/');
        }

        $num_pages = ceil($count/$per_page);
        $previous = FALSE;

        if($this->padding_left != 'no')
        {
            $start_pagination_on = $current_page_num - $this->padding_left;
            $end_pagination_on = $current_page_num + $this->padding_right + 1;

            if($start_pagination_on < 0)
            {
                $start_pagination_on = 0;
            }

        }
        else
        {
            $start_pagination_on = 0;
            $end_pagination_on = $num_pages;
        }

        if(!$this->initial_size && $this->fixed_size)
        {
            $this->initial_size = $this->fixed_size;
        }

        if($this->fixed_size)
        {
            $start_pagination_on = $current_page_num - floor($this->fixed_size/2);
            $end_pagination_on = $current_page_num + ceil(($this->fixed_size)/2);
            if($end_pagination_on > $num_pages)
            {
                $end_pagination_on = $num_pages;
                $start_pagination_on = $num_pages - $this->fixed_size;
            }


            if($start_pagination_on < 0)
            {
                $start_pagination_on = 0;
            }
        }

        if($this->initial_size && $end_pagination_on < $this->initial_size)
        {
            $end_pagination_on = $this->initial_size;
        }

        if($end_pagination_on > $num_pages)
        {
            $end_pagination_on = $num_pages;
        }

        for($j=$start_pagination_on; $j<$end_pagination_on; $j++)
        {
            $add_uri = '';
            if($j>0)
            {
                $add_uri = "/P".($j*$per_page);
            }

            $current_p_val = ($j*$per_page);

            $current = array(
                $tag_prefix.'is_current' => $j == $current_page_num,
                $tag_prefix.'link' => $pagination_base_url.$add_uri,
                $tag_prefix.'link_p_only' => 'P'.$current_p_val,
                $tag_prefix.'current_p' => $current_p_val,
                $tag_prefix.'num' => $j+1,
                $tag_prefix.'previous_link' => '',
                $tag_prefix.'previous_link_p_only' => '',
                $tag_prefix.'previous_num' => FALSE,
                $tag_prefix.'previous_p' => FALSE,
                $tag_prefix.'has_previous' => FALSE,
                $tag_prefix.'is_last_page' => ($j == ($num_pages-1)),
                $tag_prefix.'is_first_page' => ($j == 0),
                $tag_prefix.'has_next' => ($j<$end_pagination_on),
                $tag_prefix.'next_link' => '',
                $tag_prefix.'next_link_p_only' => '',
                $tag_prefix.'next_num' => FALSE,
                $tag_prefix.'next_p' => FALSE,
            );

            if($current[$tag_prefix.'has_next'])
            {
                $next_p_val = ($j+1)*$per_page;
                $current[$tag_prefix.'next_link'] = $pagination_base_url.'/P'.$next_p_val;
                $current[$tag_prefix.'next_num'] = $j+2;
                $current[$tag_prefix.'next_link_p_only'] = 'P'.$next_p_val;
                $current[$tag_prefix.'next_p'] = $next_p_val;
            }

            if($previous)
            {
                $previous_p_val = $previous[$tag_prefix.'current_p'];
                $current[$tag_prefix.'previous_link'] = $previous[$tag_prefix.'link'];
                $current[$tag_prefix.'previous_num'] = $previous[$tag_prefix.'num'];
                $current[$tag_prefix.'previous_link_p_only'] = 'P'.$previous_p_val;
                $current[$tag_prefix.'has_previous'] = TRUE;
                $current[$tag_prefix.'previous_p'] = $previous_p_val;
            }

            $pages[] = $current;
            $previous = $current;
        }

        $entry_from = $current_page_num*$per_page+1;
        $entry_to = $current_page_num*$per_page + $per_page;
        if($entry_to > $count) { $entry_to = $count; }

        $vars = array(
            $tag_prefix.'pages' => $pages,
            $tag_prefix.'total_pages' => $num_pages,
            $tag_prefix.'per_page' => $per_page,
            $tag_prefix.'total_entries' => $count,
            $tag_prefix.'first_link' => $pagination_base_url,
            $tag_prefix.'last_page_not_linked' => !($end_pagination_on == $num_pages),
            $tag_prefix.'query_string' => $query_string,
            $tag_prefix.'entry_from' => $entry_from,
            $tag_prefix.'entry_to' => $entry_to,
        );

        if($num_pages > 1)
        {
            $vars[$tag_prefix.'last_link'] = $pagination_base_url.'/P'.(($num_pages-1)*$per_page);
        }
        else
        {
            $vars[$tag_prefix.'last_link'] = $pagination_base_url;
        }


        $vars[$tag_prefix.'current_page_num'] = $current_page_num;
        $vars[$tag_prefix.'current_page_num_liber'] = $current_page_num+1;
        $vars[$tag_prefix.'current_p'] = ($current_page_num * $per_page);

        if($current_page_num <= $start_pagination_on)
        {
            $vars[$tag_prefix.'has_previous'] = FALSE;
            $vars[$tag_prefix.'previous_link'] = '';
            $vars[$tag_prefix.'previous_page_num'] = FALSE;
            $vars[$tag_prefix.'previous_p'] = FALSE;
        }
        else
        {
            $vars[$tag_prefix.'has_previous'] = TRUE;
            $vars[$tag_prefix.'previous_link'] = $pages[$current_page_num-1-$start_pagination_on][$tag_prefix.'link'];
            $vars[$tag_prefix.'previous_page_num'] = $current_page_num-1;
            $vars[$tag_prefix.'previous_p'] = ($current_page_num-1)*$per_page;
        }

        if(round($current_page_num) >= $end_pagination_on-1)
        {
            $vars[$tag_prefix.'has_next'] = FALSE;
            $vars[$tag_prefix.'next_link'] = '';
            $vars[$tag_prefix.'next_page_num'] = $num_pages;
            $vars[$tag_prefix.'next_p'] = FALSE;
        }
        else
        {
            $vars[$tag_prefix.'has_next'] = TRUE;
            $vars[$tag_prefix.'next_link'] = $pages[$current_page_num+1-$start_pagination_on][$tag_prefix.'link'];
            $vars[$tag_prefix.'next_page_num'] = $current_page_num+1;
            $vars[$tag_prefix.'next_p'] = ($current_page_num+1)*$per_page;
        }

            $ref->template_data =  $this->EE->TMPL->parse_variables($paginate_data, array($vars));

            if($paginate_location == 'custom') {

                $pagination_html = $ref->template_data;

                if(count($pages) <= 1) {
                    $pagination_html = '';
                }
                $this->EE->config->_global_vars[$tag_prefix.'pagination_html'] = $pagination_html;

                $this->EE->config->_global_vars[$tag_prefix.'_all_vars'] = serialize($vars);
                $ref->template_data = '';

            }
	}

	// ----------------------------------------------------------------------

	/**
	 * Disable Extension
	 *
	 * This method removes information from the exp_extensions table
	 *
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}

	// ----------------------------------------------------------------------

	/**
	 * Update Extension
	 *
	 * This function performs any necessary db updates when the extension
	 * page is visited
	 *
	 * @return 	mixed	void on update / false if none
	 */
	function update_extension($current = '')
	{
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}

        if ($current < '1.6.4') {
            $this->EE->db->where('class', __CLASS__)->delete('extensions'); // remove any old hooks
            $this->activate_extension();    // activate extension again.
        }
	}	
	
	// ----------------------------------------------------------------------
}

/* End of file ext.ab_pagination.php */
/* Location: /system/expressionengine/third_party/ab_pagination/ext.ab_pagination.php */