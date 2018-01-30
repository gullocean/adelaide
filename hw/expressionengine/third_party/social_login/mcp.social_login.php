<?php

/*
=====================================================
 Social login
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: mcp.social_login.php
-----------------------------------------------------
 Purpose: Enable login/registration using popular social networks
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'social_login/config.php';

class Social_login_mcp {

    var $version = SOCIAL_LOGIN_ADDON_VERSION;
    
    var $settings = array();
    
    var $docs_url = "http://www.intoeetive.com/docs/social_login.html";
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Social_login' LIMIT 1");
        $this->settings = unserialize($query->row('settings')); 
        
        if ($this->EE->config->item('app_version')>=260)
        {
        	$this->EE->view->cp_page_title = lang('social_login_module_name');
        }
        else
        {
        	$this->EE->cp->set_variable('cp_page_title', lang('social_login_module_name'));
        }
    } 
    
    function index()
    {
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('javascript');
        
        $providers_view = '';
        $providers = array();
        
        foreach(scandir(PATH_THIRD.'social_login/libraries/') as $file) {
            if (is_file(PATH_THIRD.'social_login/libraries/'.$file)) 
            {
                $providers[] = str_replace("_oauth.php", "", $file);
            }
        }
        
        $outputjs = "
            $(\".editAccordion\").css(\"borderTop\", $(\".editAccordion\").css(\"borderBottom\")); 
            $(\".editAccordion h3\").click(function() {
                if ($(this).hasClass(\"collapsed\")) { 
                    $(this).siblings().slideDown(\"fast\"); 
                    $(this).removeClass(\"collapsed\").parent().removeClass(\"collapsed\"); 
                } else { 
                    $(this).siblings().slideUp(\"fast\"); 
                    $(this).addClass(\"collapsed\").parent().addClass(\"collapsed\"); 
                }
            }); 
        ";
        
        $custom_fields = array();
        $custom_fields[''] = '';
        $this->EE->db->select('m_field_id, m_field_label');
        $this->EE->db->order_by('m_field_order', 'asc');
        $q = $this->EE->db->get('exp_member_fields');
        foreach ($q->result() as $obj)
        {
            $custom_fields[$obj->m_field_id] = $obj->m_field_label;
        }        
        
        foreach ($providers as $provider)
        {
            $data['empty'] = (isset($this->settings[$this->EE->config->item('site_id')][$provider]['app_id']) && $this->settings[$this->EE->config->item('site_id')][$provider]['app_id']!='')?false:true;
            $data['name'] = lang($provider);
            $data['docs_url'] = $this->docs_url."#".$provider;
            $data['app_register_url'] = lang($provider.'_app_register_url');
            
            $data['fields'] = array(	
                0 => array(
                        'label'=>lang($provider.'_app_id'),
                        'subtext'=>lang($provider.'_app_id_subtext'),
                        'field'=>form_input("app_id[$provider]", (isset($this->settings[$this->EE->config->item('site_id')][$provider]['app_id'])?$this->settings[$this->EE->config->item('site_id')][$provider]['app_id']:''), 'style="width: 80%"')
                    ),
                1 => array(
                        'label'=>lang($provider.'_app_secret'),
                        'subtext'=>lang($provider.'_app_secret_subtext'),
                        'field'=>form_input("app_secret[$provider]", (isset($this->settings[$this->EE->config->item('site_id')][$provider]['app_secret'])?$this->settings[$this->EE->config->item('site_id')][$provider]['app_secret']:''), 'style="width: 80%"')
                    ),
                2 => array(
                        'label'=>lang($provider.'_custom_field'),
                        'subtext'=>lang($provider.'_custom_field_subtext'),
                        'field'=>form_dropdown("custom_field[$provider]", $custom_fields, (isset($this->settings[$this->EE->config->item('site_id')][$provider]['custom_field'])?$this->settings[$this->EE->config->item('site_id')][$provider]['custom_field']:''))
                    )
      		);
            $providers_view .= $this->EE->load->view('provider', $data, TRUE);
        }
        
        $vars = array();
        $vars['providers'] = $providers_view;
        
        $icon_sets = array();
        foreach(scandir(PATH_THEMES.'third_party/social_login/') as $dir) {
            if (substr($dir, 0, 1)!='.' && is_dir(PATH_THEMES.'third_party/social_login/'.$dir)) 
            {
                $icon_sets[$dir] = $dir;
            }
        }
        
        $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Social_login' AND method='request_token'");
        $vars['settings']['act_value']	= $act->row('action_id');        
        
        $act = $this->EE->db->query("SELECT action_id FROM exp_actions WHERE class='Social_login' AND method='access_token'");
        $access_token_url = trim($this->EE->config->item('site_url'), '/').'/?ACT='.$act->row('action_id');
        $vars['settings']['callback_uri']	= $access_token_url;
        
        $member_groups = array();
        $this->EE->db->select('group_id, group_title');
        $this->EE->db->where('group_id NOT IN (1,2,4)');
        $q = $this->EE->db->get('member_groups');
        foreach ($q->result() as $obj)
        {
            $member_groups[$obj->group_id] = $obj->group_title;
        }
        $vars['settings']['member_group']	= form_dropdown('member_group', $member_groups, (isset($this->settings[$this->EE->config->item('site_id')]['member_group'])?$this->settings[$this->EE->config->item('site_id')]['member_group']:''));
        
        $vars['settings']['force_pending_if_no_email']	= form_checkbox('force_pending_if_no_email', 'y', (isset($this->settings[$this->EE->config->item('site_id')]['force_pending_if_no_email'])?$this->settings[$this->EE->config->item('site_id')]['force_pending_if_no_email']:false));
        
        $vars['settings']['icon_set']	= form_dropdown('icon_set', $icon_sets, (isset($this->settings[$this->EE->config->item('site_id')]['icon_set'])?$this->settings[$this->EE->config->item('site_id')]['icon_set']:'bar'));
        
        $this->EE->javascript->output(str_replace(array("\n", "\t"), '', $outputjs));
        
    	return $this->EE->load->view('settings', $vars, TRUE);
        
    }

    
    function save_settings()
    {

        $this->EE->load->library('table');  
        
        $site_id = $this->EE->config->item('site_id');
        
        foreach(scandir(PATH_THIRD.'social_login/libraries/') as $file) {
            if (is_file(PATH_THIRD.'social_login/libraries/'.$file)) 
            {
                $providers[] = str_replace("_oauth.php", "", $file);
            }
        }
        
        $settings = array();
        $this->EE->db->select('site_id')
                    ->from('sites');
        $q = $this->EE->db->get();
        foreach ($q->result() as $obj)
        {
            if (isset($this->settings[$obj->site_id]))
            {
                foreach ($this->settings[$obj->site_id] as $key=>$value)
                {
                    $settings[$obj->site_id][$key] = $value;
                }
            }
        }
        
        $custom_field_used = array();

        foreach ($providers as $provider)
        {
            $settings[$site_id][$provider]['app_id'] = trim($_POST["app_id"]["$provider"]);
            $settings[$site_id][$provider]['app_secret'] = trim($_POST["app_secret"]["$provider"]);
            $settings[$site_id][$provider]['custom_field'] = $_POST["custom_field"]["$provider"];
            if ( ($settings[$site_id][$provider]['app_id']!=''||$settings[$site_id][$provider]['app_secret']!=''||$settings[$site_id][$provider]['custom_field']!='') )
            {
                $custom_field_used[] = $_POST["custom_field"]["$provider"];
            }
            if ( ($settings[$site_id][$provider]['app_id']!=''||$settings[$site_id][$provider]['app_secret']!=''||$settings[$site_id][$provider]['custom_field']!='') && ($settings[$site_id][$provider]['app_id']==''||$settings[$site_id][$provider]['app_secret']==''||$settings[$site_id][$provider]['custom_field']=='') )
            {
                $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('provide_all_settings_for').' '.$this->EE->lang->line($provider));    
                $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login');
                return;  
            }
        }
        
        $custom_field_used_uniq = array_unique($custom_field_used); 
        if(count($custom_field_used_uniq) != count($custom_field_used)) 
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('cannot_use_duplicate_custom_fields'));    
            $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=social_login');
            return;    
        }
        
        $settings[$site_id]['member_group'] = $_POST["member_group"];
        $settings[$site_id]['icon_set'] = $_POST["icon_set"];
        $settings[$site_id]['force_pending_if_no_email'] = (isset($_POST["force_pending_if_no_email"])&&$_POST["force_pending_if_no_email"]=='y')?true:false;
        
        $this->EE->db->where('module_name', 'Social_login');
        $this->EE->db->update('modules', array('settings' => serialize($settings)));
        
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('updated'));        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules');
        
    }    
   
    

}
/* END */
?>