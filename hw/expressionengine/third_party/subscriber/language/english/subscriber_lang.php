<?php

require_once PATH_THIRD.'subscriber/config.php';

$css_style = "display:block; margin-top:2px; font-weight:normal";

$lang = array(
	
	'duplicate' => 'Duplicate',
	
	// Module Information
	'subscriber_module_name'        => SUBSCRIBER_NAME, 
	'subscriber_module_description' => SUBSCRIBER_DESC, 
	
	// Navigation
	'subscriber_new_form'  => 'Create New Form',
	'subscriber_dashboard' => 'Dashboard', 
	
	// Terms
	'everyone'     => 'Everyone',
	'switch_field' => 'Switch Field',
	
	// Index
	'subscriber_form_name' => 'Form Name',
	'subscriber_token'     => 'Token',
	
	// Provider Settings
	'settings_provider' => "Newsletter Provider",
	'campaign_monitor'  => 'Campaign Monitor',
	'mailchimp'         => 'MailChimp',

	// Settings Page
	'add_custom_field'          => 'Add Custom Field', 
	"settings_form_name"        => "Form Name <small style='$css_style'>Just for organization's sake.</small>",
	"settings_api_key"          => "API Key <small style='$css_style'>Take a look at <a href='http://www.campaignmonitor.com/api/getting-started/#apikey'>Campaign Monitor's Documentation</a> or <a href='http://kb.mailchimp.com/article/where-can-i-find-my-api-key/'>MailChimp's Documentation</a></small>",
	"settings_list_id"          => "List ID <small style='$css_style'>Take a look at <a href='http://www.campaignmonitor.com/api/getting-started/#listid'>Campaign Monitor's Documentation</a> or <a href='http://kb.mailchimp.com/article/how-can-i-find-my-list-id/'>MailChimp's Documentation</a>.</small>",
	"settings_method"           => "Add Method <small style='$css_style'>You can either add everyone or add based upon a specific field and it's value.",
	'settings_switch_field'     => "Switch Input Field Name <small style='$css_style'>This is the value that will determine whether to add the email address to the subscriber list.</small>", 
	"settings_switch_value"     => "Switch Input Field Value", 
	"settings_name_field"       => "Name Input Field Name", 
	"settings_first_name_field" => "First Name Input Field Name", 
	"settings_last_name_field"  => "Last Name Input Field Name", 
	"settings_email_field"      => "Email Input Field Name", 
	'settings_custom_field'     => 'Input Name', 
	'settings_custom_field_tag' => '(MERGE) Tag', 
	'settings_custom_field_multiple' => 'Multiple Options?', 
	'settings_custom_field_help' => '<strong>For Campaign Monitor</strong>: You can find the tag name in &ldquo;Your existing field&rdquo; under Personalization under Custom Fields in Campaign Monitor. Also, if you&rsquo;re using a custom field with multiple options, make sure to check the &ldquo;Multiple Options&rdquo; checkbox; and make sure that in <strong>only</strong> the template the input name is an array with square brackets (e.g. color[]).<br /><strong>For MailChimp</strong>: You can find the MERGE Tags in the list&rsquo;s Setting&rsquo;s page under List Fields and *|MERGE|* Tags.',
	
	// Delete
	'form_delete'         => 'Delete Form', 
	'form_delete_left'    => "Delete &lsquo;",
	'form_delete_right'   => '&rsquo?',
	'form_delete_confirm' => 'Are you sure you want to delete this form?', 

	// Errors
	"switch_field_missing" => 
	"If you want to use the switch field method, you'll need to enter the switch field's name and what the value needs to be to add someone to your list.",
	"api_key_missing" => 
	"Your API Key is missing, please add it. If you don't know where to find it, please look at <a href='http://www.campaignmonitor.com/api/getting-started/'>Campaign Monitor's Documentation</a> or <a href='http://kb.mailchimp.com/article/where-can-i-find-my-api-key/'>MailChimp's Documentation</a>.", 
	"list_id_missing" => 
	"Your List ID is missing, please add it. If you don't know where to find it, please look at <a href='http://www.campaignmonitor.com/api/getting-started/'>Campaign Monitor's Documentation</a> or <a href='http://kb.mailchimp.com/article/how-can-i-find-my-list-id/'>MailChimp's Documentation</a>.",
	'install_freeform' =>
	'Please install <a href="http://www.solspace.com/software/detail/freeform/">Solspace\'s Freeform</a> module before installing Subscriber.'
);