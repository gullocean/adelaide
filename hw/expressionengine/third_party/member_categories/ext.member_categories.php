<?php

/*
=====================================================
 Member categories
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: ext.member_categories.php
-----------------------------------------------------
 Purpose: Assign categories to site members
=====================================================
*/

if ( ! defined('BASEPATH'))
{
	exit('Invalid file request');
}

require_once PATH_THIRD.'member_categories/config.php';

class Member_categories_ext {

	var $name	     	= MEMBER_CATEGORIES_ADDON_NAME;
	var $version 		= MEMBER_CATEGORIES_ADDON_VERSION;
	var $description	= 'Extension for Member categories module';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://www.intoeetive.com/docs/member_categories.html';
    var $settings       = array();
    
	/**
	 * Constructor
	 *
	 * @param 	mixed	Settings array or empty string if none exist.
	 */
	function __construct($settings = '')
	{
		$this->EE =& get_instance();
        
        $this->settings = $settings;

	}
    
    /**
     * Activate Extension
     */
    function activate_extension()
    {
        
        $hooks = array(
    		array(
    			'hook'		=> 'cp_members_member_delete_end',
    			'method'	=> 'delete_record',
    			'priority'	=> 10
    		)
            
    	);
    	
        foreach ($hooks AS $hook)
    	{
    		$data = array(
        		'class'		=> __CLASS__,
        		'method'	=> $hook['method'],
        		'hook'		=> $hook['hook'],
        		'settings'	=> '',
        		'priority'	=> $hook['priority'],
        		'version'	=> $this->version,
        		'enabled'	=> 'y'
        	);
            $this->EE->db->insert('extensions', $data);
    	}	
    }
    
    function settings()
    {
        $settings = array();

        return $settings;
    }
    
    /**
     * Update Extension
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}
    	
    	if ($current < '2.0')
    	{
    		// Update to version 1.0
    	}
    	
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->update(
    				'extensions', 
    				array('version' => $this->version)
    	);
    }
    
    
    /**
     * Disable Extension
     */
    function disable_extension()
    {
    	$this->EE->db->where('class', __CLASS__);
    	$this->EE->db->delete('extensions');
    }
    
    
    function delete_record()
    {
        $ids = array();
		$mids = array();
		
		foreach ($this->EE->input->post('delete') as $key => $val)
		{		
			if ($val != '')
			{
				$ids[] = "member_id = '".$this->EE->db->escape_str($val)."'";
			}		
		}
		
		$IDS = implode(" OR ", $ids);
        
        $this->EE->db->query("DELETE FROM exp_category_members WHERE ".$IDS);
    }


}
// END CLASS
?>