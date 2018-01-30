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
 File: upd.social_login.php
-----------------------------------------------------
 Purpose: Enable login/registration using popular social networks
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'social_login/config.php';

class Social_login_upd {

    var $version = SOCIAL_LOGIN_ADDON_VERSION;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 
    } 
    
    function install() { 
        
        $this->EE->load->dbforge(); 
        
        //----------------------------------------
		// EXP_MODULES
		// The settings column, Ellislab should have put this one in long ago.
		// No need for a seperate preferences table for each module.
		//----------------------------------------
		if ($this->EE->db->field_exists('settings', 'modules') == FALSE)
		{
			$this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
		}
        
        $settings = array();
        
        $data = array( 'module_name' => 'Social_login' , 'module_version' => $this->version, 'has_cp_backend' => 'y', 'settings'=> serialize($settings) ); 
        $this->EE->db->insert('modules', $data); 
        
        $data = array( 'class' => 'Social_login' , 'method' => 'request_token' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Social_login' , 'method' => 'access_token' ); 
        $this->EE->db->insert('actions', $data); 
        
        $data = array( 'class' => 'Social_login' , 'method' => 'save_userdata' ); 
        $this->EE->db->insert('actions', $data); 
        
        //exp_social_login_session_data
        $fields = array(
			'session_id'	=> array('type' => 'VARCHAR',	'constraint'=> 250,	'default' => ''),
			'set_date'	    => array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
			'data'			=> array('type' => 'TEXT',		'default' => '')
		);

		$this->EE->dbforge->add_field($fields);
		$this->EE->dbforge->add_key('session_id', TRUE);
		$this->EE->dbforge->add_key('set_date');
		$this->EE->dbforge->create_table('social_login_session_data', TRUE);
        
        return TRUE; 
        
    } 
    
    function uninstall() { 

        $this->EE->db->select('module_id'); 
        $query = $this->EE->db->get_where('modules', array('module_name' => 'Social_login')); 
        
        $this->EE->db->where('module_id', $query->row('module_id')); 
        $this->EE->db->delete('module_member_groups'); 
        
        $this->EE->db->where('module_name', 'Social_login'); 
        $this->EE->db->delete('modules'); 
        
        $this->EE->db->where('class', 'Social_login'); 
        $this->EE->db->delete('actions'); 
        
        $this->EE->db->query("DROP TABLE ".$this->EE->db->dbprefix."social_login_session_data");
        
        return TRUE; 
    } 
    
    function update($current='') { 
        if ($current < 1.3) 
        { 
            $data = array( 'class' => 'Social_login' , 'method' => 'save_userdata' ); 
            $this->EE->db->insert('actions', $data); 
        } 
        if ($current < 1.5) 
        { 
            $sql = "SELECT screen_name FROM exp_members GROUP BY screen_name HAVING COUNT(screen_name) > 1";
            $q = $this->EE->db->query($sql);
            if ($q->num_rows() > 0)
            {
                $this->_update_screen_names($q);
            }
        } 
        
        if ($current < 1.9)
        {
        	$this->EE->load->dbforge(); 
			
			//exp_social_login_session_data
	        $fields = array(
				'session_id'	=> array('type' => 'VARCHAR',	'constraint'=> 250,	'default' => ''),
				'set_date'	    => array('type' => 'INT',		'unsigned' => TRUE, 'default' => 0),
				'data'			=> array('type' => 'TEXT',		'default' => '')
			);
	
			$this->EE->dbforge->add_field($fields);
			$this->EE->dbforge->add_key('session_id', TRUE);
			$this->EE->dbforge->add_key('set_date');
			$this->EE->dbforge->create_table('social_login_session_data', TRUE);
        }
        
        return TRUE; 
    } 
    
    
    function _update_screen_names($q)
    {
        foreach ($q->result_array() as $row)
        {
            $i = 0;
            $sql = "SELECT member_id FROM exp_members WHERE screen_name='".$this->EE->db->escape_str($row['screen_name'])."' ORDER BY join_date ASC";
            $q2 = $this->EE->db->query($sql);
            foreach ($q2->result_array() as $row2)
            {
                if ($i>0)
                {
                    $data['screen_name'] = $row['screen_name']." ".$i;
                    $this->EE->db->where('member_id', $row2['member_id']);
                    $this->EE->db->update('members', $data); 
                }
                $i++;
            }
        }

        $sql = "SELECT screen_name FROM exp_members GROUP BY screen_name HAVING COUNT(screen_name) > 1";
        $q3 = $this->EE->db->query($sql);
        if ($q3->num_rows() > 0)
        {
            $this->_update_screen_names($q3);
        }
    }
	

}
/* END */
?>