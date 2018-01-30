<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine Updatable Select Dropdown Fieldtype
 *
 * @author		Denis Gorin
 * @link		denis.gorin@gmail.com
 

Updatable Select Dropdown Commercial License:
http://devot-ee.com/add-ons/license/updatable-select-dropdown/

Permitted Use
One license grants the right to perform one installation of the Software. 
Each additional installation of the Software requires an additional purchased license

Buy:
http://devot-ee.com/add-ons/updatable-select-dropdown/
 
 */
 
// load class Select to extend it for updatability

$this->EE =& get_instance();
$this->EE->api_channel_fields->include_handler('select');

class Cstslct_ft extends Select_ft {

	var $info = array(
		'name'		=> 'Updatable Select Dropdown',
		'version'	=> '1.3'
	);

	var $has_array_data = TRUE;
	
	/** -------------------------------------
	/** Constructor
	/** -------------------------------------*/
	
	function Cstslct_ft()
	{
		
		if(APP_VER < '2.1.5') {
			parent::EE_Fieldtype();
		} else {
			parent::__construct();
		}
				
		if($this->EE->input->get('D') == 'cp')
        {
			$this->EE->lang->loadfile('cstslct');
			$this->EE->javascript->output('
				var cstslct_revBtn = $(\'#revision_button\');
				if(cstslct_revBtn.length) cstslct_revBtn.attr(\'name\',\'revision_submit\');
			');
		}
	}
	
	
	/** -------------------------------------
	/** Display Global Settings
	/** -------------------------------------*/
	
	function display_global_settings()
	{
		$license_key = isset($this->settings['license_key']) ? $this->settings['license_key'] : '';
		$this->EE->load->library('table');
		$this->EE->table->set_template(array(
			'table_open'    => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
			'row_start'     => '<tr class="even">'
		));
		$this->EE->table->set_heading(array('data' => lang('preference'), 'style' => 'width: 50%'), lang('setting'));
		$this->EE->table->add_row(
			lang('cstslct_license_key', 'license_key'),
			form_input('license_key', $license_key, 'id="license_key" style="width:100%;"')
		);
		return $this->EE->table->generate();
	}

	/** -------------------------------------
	/** Save Global Settings
	/** -------------------------------------*/
	
	function save_global_settings()
	{
		return array(
			'license_key' => isset($_POST['license_key']) ? $_POST['license_key'] : ''
		);
	}
	
	// --------------------------------------------------------------------
	
	function display_field($data)
	{
		$this->EE->javascript->output('
			var cstslct_s_'.$this->field_id.' = $(\'#cstslct_show_'.$this->field_id.'\');
			var cstslct_h_'.$this->field_id.' = $(\'#cstslct_hide_'.$this->field_id.'\');
			cstslct_s_'.$this->field_id.'.find(\'img\').click(function(){
				cstslct_h_'.$this->field_id.'.fadeIn(500);
				cstslct_s_'.$this->field_id.'.hide().find(\'input\').val(\'\');
			}).css({\'cursor\':\'pointer\'});
			cstslct_h_'.$this->field_id.'.find(\'img\').click(function(){
				cstslct_s_'.$this->field_id.'.fadeIn(500);
				cstslct_h_'.$this->field_id.'.hide();
			}).css({\'cursor\':\'pointer\'});
			
			cstslct_s_'.$this->field_id.'.find(\'input\').change(function(){
				//var v = $(this).val();
				//cstslct_h_'.$this->field_id.'.find(\'select\').append(\'<option selected value="\'+v+\'" >\'+v+\'</option>\');
				cstslct_h_'.$this->field_id.'.find(\'select option\').attr(\'selected\',\'\');
				cstslct_h_'.$this->field_id.'.find(\'select option:last-child\').attr(\'selected\',\'selected\');
			});
			
		');
		$this->EE->javascript->compile();
		$cp_theme  = $this->EE->config->item('cp_theme'); 
		$cp_theme_url = $this->EE->config->slash_item('theme_folder_url').'cp_themes/'.$cp_theme.'/';
		
		$field_options[''] = '--';
		foreach ($this->_get_field_options($data) as $row)
			{
				$field_options[$row] = $row;
			}
		
		$__str = '<span id="cstslct_hide_'.$this->field_id.'">';
		//$__str .=  form_dropdown($this->field_name, $field_options, $data, 'id="'.$this->field_id.'"');
		$__str .=  form_dropdown('field_id_'.$this->field_id, $field_options, $data, 'id="'.$this->field_name.'"');
		$__str .= '&nbsp;&nbsp;<img src="'.$cp_theme_url.'images/add_item.png" width="12" height="14" title="'. lang('cstslct_add') .'" align="absmiddle" /></span><span id="cstslct_show_'.$this->field_id.'" style="display:none;">';
		//$__str .= form_input('cstslct_'.$this->field_name,"", 'style="width:30%;" id="cstslct_'.$this->field_id.'"');
		$__str .= form_input('cstslct_field_id_'.$this->field_id,"", 'style="width:30%;" id="cstslct_'.$this->field_name.'"');
		$__str .= '&nbsp;&nbsp;<img src="'.$cp_theme_url.'images/write_mode_close.png" width="13" height="13" title="'. lang('cstslct_remove') .'" align="absmiddle" /></span>';
		return $__str;
		
	}
	// --------------------------------------------------------------------
	
	function display_settings($data)
	{
		$prefix = 'cstslct';
 		$this->field_formatting_row($data, $prefix);
		$this->multi_item_row($data, $prefix);
	}
	
	
	
	
	
	
	
	
	
		
	
	
}

// END Cstslct_ft class

/* End of file ft.cstslct.php */
/* Location: ./system/expressionengine/third_party/cstslct/ft.cstslct.php */
