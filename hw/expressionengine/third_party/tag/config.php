<?php if ( ! defined('EXT')) exit('No direct script access allowed');

/**
 * Tag - Config
 *
 * NSM Addon Updater config file.
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/config.php
 */

require_once 'constants.tag.php';

$config['name']									= 'Tag';
$config['version']								= TAG_VERSION;
$config['nsm_addon_updater']['versions_xml'] 	= 'http://www.solspace.com/software/nsm_addon_updater/tag';