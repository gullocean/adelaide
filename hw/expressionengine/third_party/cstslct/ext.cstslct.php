<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
=====================================================
 File: ext.cstslct.php
-----------------------------------------------------
 Purpose: 	Enable to add a new item in Select 
 			Dropdown custom field
-----------------------------------------------------
 Support thread: denis.gorin@gmail.com
=====================================================
 Requires Jquery
=====================================================

Updatable Select Dropdown Commercial License:
http://devot-ee.com/add-ons/license/updatable-select-dropdown/

Permitted Use
One license grants the right to perform one installation of the Software. 
Each additional installation of the Software requires an additional purchased license

Buy:
http://devot-ee.com/add-ons/updatable-select-dropdown/
*/


class Cstslct_ext {


	/** -------------------------------------
	/** Settings
	/** -------------------------------------*/

	var $settings       = array();
	var $name           = 'Updatable Select Dropdown Extension';
	var $version        = '1.3';
	var $description    = 'Enable to add a new item in Select Dropdown custom field';
	var $settings_exist = 'n';
	var $docs_url       = '';


	/** -------------------------------------
	/** Constructor
	/** -------------------------------------*/
	
	function Cstslct_ext($settings='') {
		
		$this->settings = $settings;
	    $this->EE =& get_instance();
		
	}


	/** -------------------------------------
	/** Activate $this->extensions->last_call
	/** -------------------------------------*/

	function activate_extension() {
		$data = array(
			'class'        => "Cstslct_ext",
			'method'       => "process_entry_submission_redirect",
			'hook'         => "entry_submission_redirect",
			'settings'     => "",
			'priority'     => 0,
			'version'      => $this->version,
			'enabled'      => "y"
		);
		$this->EE->db->insert('exp_extensions', $data);
	}


	/** -------------------------------------
	/** Update Extension
	/** -------------------------------------*/
	
	function update_extension($current='') {
		if ($current == '' OR $current == $this->version) {
			return FALSE;
		}
	}


	/** -------------------------------------
	/** Disable
	/** -------------------------------------*/
	
	function disable_extension() {
	    $this->EE->db->where('class', 'Cstslct_ext');
	    $this->EE->db->delete('exp_extensions');
	}
	
	
	/** -------------------------------------
	/** process
	/** -------------------------------------*/

	function process_entry_submission_redirect($entry_id, $meta, $data, $cp_call, $orig_loc) 
	{
		$field_id=array();
		$field = array();
		$value = array();
		
		/*
		if($this->extensions->last_call)
		{
			$data = $this->extensions->last_call;
			echo"<pre>";var_dump($data);echo"</pre>";
		}
		*/
		
		
		// find editable drop down field (has cstslct_ prefix)
		//echo "<pre>";print_r($data);echo "</pre>";
		foreach($data as $key => $val)
        {
            if (preg_match("/^cstslct_/",$key))
			{
				// assign needed attrs
				$__field = preg_replace("/cstslct_/","",$key);
				$field[] = $__field;
				$value[] = $val;
				$field_id[] = preg_replace("/field_id_/","",$__field);
			}
        }
		
		if (count($field_id)>0)
		{
			foreach($field_id as $key => $field_id)
			{
			//echo "[ - ".$field_id." - ]<br>";
				$this->EE->db->select('field_list_items');
				$this->EE->db->where('field_id', $field_id);
				$query = $this->EE->db->get('channel_fields');
				
				if ($query->num_rows() > 0)
				{ // compare drop down list with new value
					$chk = 0;
					$field_list_items=explode("\n", trim($query->row('field_list_items')));
					foreach ($field_list_items as $v)
					{
						$v = trim($v);
						if ($v==$value[$key]) $chk++;
					}
				}
					
				if (isset($chk) && $chk==0 && $value[$key]!=""){ //add new item to custom drop down and write to DB
					array_push($field_list_items,$value[$key]); 
					natcasesort($field_list_items);
					$field_list_items =  implode("\n", $field_list_items);
					
					$this->EE->db->where('field_id', $field_id);
					$this->EE->db->set('field_list_items', $field_list_items); 
					$this->EE->db->update('channel_fields'); // reinsert fields
					
					$this->EE->db->where('entry_id', $entry_id);
					$this->EE->db->set($field[$key], $value[$key]); 
					$this->EE->db->update('channel_data'); // reinsert entry with new value 
				}
			}
		}
		if($cp_call===TRUE)
		{
			if (isset($data['revision_submit']))
			{
				// get back
				$loc = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$data['channel_id'].AMP.'entry_id='.$entry_id.AMP.'revision=saved';
			}else{
				$loc = $orig_loc;
			}
			
			return $loc;	
		}
		else
		{
			return $orig_loc;	
		}
		//$this->EE->functions->redirect($loc);
	}

}