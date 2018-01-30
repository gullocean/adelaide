<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'subscriber/config.php';

/**
 * Addon Core Module File
 */
class Subscriber
{
	var $return_data = '';
	
	function __construct()
	{
		$this->EE =& get_instance();
	}
	
	public function form()
	{
		$form_id = $this->EE->TMPL->fetch_param('form_id');
		
		$this->EE->load->model(SUBSCRIBER_DB_FORMS.'_model');
		
		if ($this->EE->subscriber_forms_model->count(array('id' => $form_id)))
		{
			$this->return_data = form_hidden('subscriber_form_id[]', $form_id);
		}
		
		return $this->return_data;
	}
}

// End File mod.subscriber.php
// File Source /system/expressionengine/third_party/subscriber/mod.subscriber.php