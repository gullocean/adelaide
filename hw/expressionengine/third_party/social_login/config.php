<?php

if ( ! defined('SOCIAL_LOGIN_ADDON_NAME'))
{
	define('SOCIAL_LOGIN_ADDON_NAME',         'Social Login');
	define('SOCIAL_LOGIN_ADDON_VERSION',      '2.1.1');
}

$config['name']=SOCIAL_LOGIN_ADDON_NAME;
$config['version']=SOCIAL_LOGIN_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/39';