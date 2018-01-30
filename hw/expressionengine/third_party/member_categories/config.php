<?php

if ( ! defined('MEMBER_CATEGORIES_ADDON_NAME'))
{
	define('MEMBER_CATEGORIES_ADDON_NAME',         'Member categories');
	define('MEMBER_CATEGORIES_ADDON_VERSION',      '1.5.2');
}

$config['name']=MEMBER_CATEGORIES_ADDON_NAME;
$config['version']=MEMBER_CATEGORIES_ADDON_VERSION;

$config['nsm_addon_updater']['versions_xml']='http://www.intoeetive.com/index.php/update.rss/38';