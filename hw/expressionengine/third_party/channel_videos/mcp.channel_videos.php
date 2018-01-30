<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include_once dirname(dirname(__FILE__)).'/channel_videos/config.php';

/**
 * Channel Videos Module Control Panel Class
 *
 * @package         DevDemon_ChannelVideos
 * @author          DevDemon <http://www.devdemon.com> - Lead Developer @ Parscale Media
 * @copyright       Copyright (c) 2007-2010 Parscale Media <http://www.parscale.com>
 * @license         http://www.devdemon.com/license/
 * @link            http://www.devdemon.com/channel_videos/
 * @see             http://expressionengine.com/user_guide/development/module_tutorial.html#control_panel_file
 */
class Channel_videos_mcp
{

    /**
     * Constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        // Creat EE Instance
        $this->EE =& get_instance();
        $this->EE->load->library('channel_videos_helper');

        // Some Globals
        $this->initGlobals();

        // Global Views Data
        $this->vData['base_url'] = $this->base;
        $this->vData['base_url_short'] = $this->base_short;
        $this->vData['method'] = $this->EE->input->get('method');

        // Add Right Top Menu
        $this->EE->cp->set_right_nav(array(
            'cv:docs'           => $this->EE->cp->masked_url('http://www.devdemon.com/channel_videos/docs/'),
        ));
    }

    // ********************************************************************************* //

    /**
     * MCP PAGE: Index
     *
     * @access public
     * @return string
     */
    public function index()
    {
        return $this->players();
    }

    // ********************************************************************************* //

    public function players()
    {
        // Page Title & BreadCumbs
        $this->vData['PageHeader'] = 'players';

        // Grab Settings
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Channel_videos'");
        if ($query->row('settings') != FALSE)
        {
            $settings = @unserialize($query->row('settings'));

            if (isset($settings['site:'.$this->site_id]) == FALSE)
            {
                $settings['site:'.$this->site_id] = array();
            }
        }

        if (isset($settings['site:'.$this->site_id]['players']) == FALSE OR $settings['site:'.$this->site_id]['players'] == FALSE) $settings['site:'.$this->site_id]['players'] = array();

        $this->vData = array_merge($this->vData, $settings['site:'.$this->site_id]['players']);

        return $this->EE->load->view('mcp/players', $this->vData, TRUE);
    }

    // ********************************************************************************* //

    public function update_players()
    {
        // Grab Settings
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name = 'Channel_videos'");
        if ($query->row('settings') != FALSE)
        {
            $settings = @unserialize($query->row('settings'));

            if (isset($settings['site:'.$this->site_id]) == FALSE)
            {
                $settings['site:'.$this->site_id] = array();
            }
        }

        $settings['site:'.$this->site_id]['players'] = $this->EE->input->post('players');

        // Put it Back
        $this->EE->db->set('settings', serialize($settings));
        $this->EE->db->where('module_name', 'Channel_videos');
        $this->EE->db->update('exp_modules');


        $this->EE->functions->redirect($this->base . '&method=index');
    }

    // ********************************************************************************* //

    private function initGlobals()
    {
        // Some Globals
        $this->base = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=channel_videos';
        $this->base_short = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=channel_videos';
        $this->site_id = $this->EE->config->item('site_id');

        // Page Title & BreadCumbs
        $this->EE->cp->set_breadcrumb($this->base, $this->EE->lang->line('channel_videos_module_name'));

        if (function_exists('ee')) {
            ee()->view->cp_page_title = $this->EE->lang->line('channel_videos_module_name');
        } else {
            $this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('channel_videos_module_name'));
        }

        $this->EE->channel_videos_helper->addMcpAssets('gjs');
        $this->EE->channel_videos_helper->addMcpAssets('css', 'css/mcp.css?v='.CHANNEL_VIDEOS_VERSION, 'channel_videos', 'mcp');

        if ($this->EE->config->item('channel_videos_debug') == 'yes') {
             $this->EE->channel_videos_helper->addMcpAssets('js', 'js/mcp.js?v='.CHANNEL_VIDEOS_VERSION, 'channel_videos', 'mcp');
        } else {
             $this->EE->channel_videos_helper->addMcpAssets('js', 'js/mcp.min.js?v='.CHANNEL_VIDEOS_VERSION, 'channel_videos', 'mcp');
        }
    }

    // ********************************************************************************* //

    public function ajax_router()
    {

        // -----------------------------------------
        // Ajax Request?
        // -----------------------------------------
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        {
            // Load Library
            if (class_exists('Channel_Videos_AJAX') != TRUE) include 'ajax.channel_videos.php';

            $AJAX = new Channel_Videos_AJAX();

            // Shoot the requested method
            $method = $this->EE->input->get_post('ajax_method');
            echo $AJAX->$method();
            exit();
        }
    }

    // ********************************************************************************* //

} // END CLASS

/* End of file mcp.shop.php */
/* Location: ./system/expressionengine/third_party/points/mcp.shop.php */
