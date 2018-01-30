<?php

if (! defined('SUBSCRIBER_NAME'))
{
	define('SUBSCRIBER_NAME', 'Subscriber');
	define('SUBSCRIBER_MACHINE', 'Subscriber');
	define('SUBSCRIBER_VER',  '3.4.5');
	define('SUBSCRIBER_DESC', 'Freeform Extension that sends Names, Email Addresses and custom fields to Campaign Monitor.');

	define('SUBSCRIBER_DB_FORMS', 'subscriber_forms');
}

$config['name']    = SUBSCRIBER_NAME;
$config['version'] = SUBSCRIBER_VER;
$config['nsm_addon_updater']['versions_xml'] = 'http://wesbaker.github.com/subscriber.ee2_addon/versions.xml';
