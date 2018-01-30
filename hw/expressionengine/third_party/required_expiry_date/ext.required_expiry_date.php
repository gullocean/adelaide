<?php

/*
=====================================================
 This ExpressionEngine add-on was created by Laisvunas
 - http://devot-ee.com/developers/laisvunas
=====================================================
 Copyright (c) Laisvunas
=====================================================
 This is commercial Software.
 One purchased license permits the use this Software on the SINGLE website.
 Unless you have been granted prior, written consent from Laisvunas, you may not:
 * Reproduce, distribute, or transfer the Software, or portions thereof, to any third party
 * Sell, rent, lease, assign, or sublet the Software or portions thereof
 * Grant rights to any other person
=====================================================
 Purpose: Allows you to make expiry date required for selected channels.
=====================================================
*/

class Required_expiry_date_ext {

  var $settings = array();
  var $name = 'Required Expiry Date';
  var $version = '1.0.1';
  var $description = 'Allows you to make expiry date required for selected channels.';
  var $settings_exist = 'y';
  var $docs_url = 'http://devot-ee.com/developers/laisvunas';
  
  var $author = 'Laisvunas';
  var $site_id = 1;
  var $selected_channels = array();
  
  // -------------------------------
 	// Constructor
 	// -------------------------------
  
  function Required_expiry_date_ext($settings = array()) 
  {
    $this->EE =& get_instance();
    
    $this->site_id = $this->EE->config->item('site_id');
    
    $this->settings = $settings;
    
    if (isset($this->settings[$this->site_id]))
    {
      foreach($this->settings[$this->site_id] as $channel_id => $selected)
      {
        if ($selected)
        {
          array_push($this->selected_channels, $channel_id);
        }
      }
    }
    
    return TRUE;
  }
  // END FUNCTION 
  
  // --------------------------------
 	//  Activate Extension
 	// --------------------------------
  
  function activate_extension()
  {
    $sites_sql = "SELECT site_id FROM exp_sites";
    $sites_query = $this->EE->db->query($sites_sql);
    $channels_sql = "SELECT channel_id FROM exp_channels WHERE site_id = '".$this->site_id."'";
    $channels_query = $this->EE->db->query($channels_sql);
    
    foreach ($sites_query->result_array() as $site_row)
    {
      foreach ($channels_query->result_array() as $channel_row)
      {
        $settings[$site_row['site_id']][$channel_row['channel_id']] = FALSE;
      }
    }
    
    $data = array(
      'class'		=> get_class($this),
      'method'	=> 'entry_submission_start',
      'hook'		=> 'entry_submission_start',
      'priority'	=> 1,
      'version'	=> $this->version,
      'enabled'	=> 'y',
      'settings'	=> serialize($settings)
    );
    $this->EE->db->insert('exp_extensions', $data);
    
    return TRUE;
  }
  // END FUNCTION
  
  // --------------------------------
 	//  Update Extension
 	// --------------------------------
  
  function update_extension($current = '')
  {
    if ($current == '' OR $current == $this->version)
  		{
  			return FALSE;
  		}
    
    if ($current < $this->version)
    {
      $this->EE->db->query("UPDATE exp_extensions 
  					SET version = '".$this->version."' 
  					WHERE class = '".get_class($this)."'");
    }
    
    return TRUE;
  }
  // END FUNCTION
  
  // --------------------------------
 	//  Disable Extension
 	// --------------------------------
  
  function disable_extension()
  {
    $this->EE->db->query("DELETE FROM exp_extensions WHERE class = '".get_class($this)."'");
    
    return TRUE;
  }
  // END FUNCTION
  
  // --------------------------------
 	//  Settings form
 	// --------------------------------
  
  function settings_form($current)
  {
    // Contents of title tag and last breadcrumb
    if (method_exists($this->EE->cp, 'set_variable'))
    {
      $this->EE->cp->set_variable('cp_page_title', $this->name.' '.$this->version);
    }
    else
    {
      $this->EE->view->cp_page_title = $this->name.' '.$this->version;
    }
    
    $this->EE->load->helper('form');
  	 $this->EE->load->library('table');
    $vars = array();
    
    if ($this->EE->input->get('method') != 'docs')
    {
      // Documentation link
      $this->EE->cp->set_right_nav(array(
  				  'documentation'		=> BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=required_expiry_date'.AMP.'method=docs'
  			 ));

      $channels_sql = "SELECT * FROM exp_channels WHERE site_id = '".$this->site_id."'";
      $channels_query = $this->EE->db->query($channels_sql);
      
      $vars['channels'] = array();
      foreach ($channels_query->result_array() as $channel_row)
      {
        $channel_info = array();
        $channel_info['channel_id'] = $channel_row['channel_id'];
        $channel_info['channel_title'] = $channel_row['channel_title'];
        $channel_info['selected'] = (isset($current[$this->site_id][$channel_row['channel_id']])) ? $current[$this->site_id][$channel_row['channel_id']] : FALSE;
        array_push($vars['channels'], $channel_info);
      }
      
      return $this->EE->load->view('settings_form', $vars, TRUE);
    }
    
    if ($this->EE->input->get('method') == 'docs')
    {
      // Documentation link
      $this->EE->cp->set_right_nav(array(
  				  'settings'		=> BASE.AMP.'C=addons_extensions'.AMP.'M=extension_settings'.AMP.'file=required_expiry_date'
  			 ));
      
      // CSS
      $this->EE->cp->add_to_head('
      <style type="text/css">
      tr.even, tr.odd {
        vertical-align: top;
      }
      </style>
      ');
      
      $docs = array();
      $docs['author'] = $this->author;
      $docs['author_url'] = '<a href="'.$this->docs_url.'" target="_blank">'.$this->docs_url.'</a>';
      $docs['description'] = $this->description;
      $docs['usage'] = $this->usage();
      $vars['docs'] = $docs;
      
      return $this->EE->load->view('docs', $vars, TRUE);
    }
    
  }
  // END FUNCTION
  
  function save_settings()
  {
    if (empty($_POST))
   	{
   		show_error($this->EE->lang->line('unauthorized_access'));
   	}
    
    unset($_POST['submit']);
    
    $channels_sql = "SELECT * FROM exp_channels WHERE site_id = '".$this->site_id."'";
    $channels_query = $this->EE->db->query($channels_sql);
    
    $current_settings_sql = "SELECT settings FROM exp_extensions WHERE class = '".get_class($this)."' LIMIT 1";
    $current_settings_query = $this->EE->db->query($current_settings_sql);
    $current_settings_row_array = $current_settings_query->row_array();
    $current_settings = unserialize($current_settings_row_array['settings']);
    
    $settings = $current_settings;
    
    foreach ($channels_query->result_array() as $channel_row)
    {
      $settings[$this->site_id][$channel_row['channel_id']] = ($this->EE->input->post($channel_row['channel_id'])  == 'yes') ? TRUE : FALSE;
    }
    
    $settings = serialize($settings);
    
    $update_settings_sql = "UPDATE exp_extensions SET settings = '".$settings."' WHERE class = '".get_class($this)."' ";
    $this->EE->db->query($update_settings_sql);
    
    $this->EE->session->set_flashdata(
    		'message_success',
    	 $this->EE->lang->line('preferences_updated')
   	);
  }
  // END FUNCTION
  
  function entry_submission_start($channel_id = 0, $autosave = FALSE)
  {
    if (!$channel_id || $autosave === TRUE )
    {
      return;
    }
    if(!in_array($channel_id, $this->selected_channels)) 
    {
      return;
    }
    
    	$this->EE->lang->loadfile('required_expiry_date');
    
    //var_dump($_POST);
    if(!$this->EE->input->post('expiration_date')) 
    {
      $this->EE->javascript->output('$.ee_notice("'.$this->EE->lang->line('expiry_error_msg').'", {type : "error"})');
      $this->EE->api_channel_entries->_set_error($this->EE->lang->line('expiry_error_msg'), 'expiration_date');
      $this->extensions->end_script = TRUE;
    }
  }
  // END FUNCTION
  
  function usage()
  {
    ob_start(); 
?>

Entries in ExpressionEngine have an entry date. Entry date field is required.
Entries also have Expiration Date, but this field is optional and by default it is left blank.
Sometimes it is a need to force entry authors to set expiration date for entries in certain channels.
This is the purpose of this extension.

INSTALLATION

1) unzip the files

2) upload the directory "required_expiry_date" into /system/expressionengine/third_party folder on the server.

3) log into Expressionengine's Control Panel, go Add-ons > Extensions, find in the list "Required Expiry Date" and click "Enable".

USAGE

After installation click "Settings" link and select the channels in which you need to make Expiry Date required and click "Submit".

That's it! Now if entry author will forget to set expiry date the error alert will be displayed telling "Entries in this channel require expiry date".

<?php
    $buffer = ob_get_contents();
    ob_end_clean();
    
    $this->EE->load->library('typography');
    $this->EE->typography->initialize();
    
    $prefs = array(
      'text_format' => 'br', 
      'highlight_code' => TRUE
    ); 
    $buffer = $this->EE->typography->parse_type(trim($buffer), $prefs); 
    return $buffer;
  }
  // END FUNCTION
}
// END CLASS
?>