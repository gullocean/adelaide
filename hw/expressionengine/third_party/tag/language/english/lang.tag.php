<?php

/**
 * Tag - Language
 *
 * @package		Solspace:Tag
 * @author		Solspace, Inc.
 * @copyright	Copyright (c) 2008-2014, Solspace, Inc.
 * @link		http://solspace.com/docs/tag
 * @license		http://www.solspace.com/license_agreement
 * @version		4.2.7
 * @filesource	tag/language/english/lang.tag.php
 */

$lang = $L = array(

//----------------------------------------
//	Required for MODULES page
//----------------------------------------

"tag_module_name" =>
"Tag",

"tag_module_description" =>
"Intuitive Relationship Tool",

"tag_module_version" =>
"Tag",


//----------------------------------------
//	Language for Content Wrapper
//----------------------------------------

"tag" =>
"Tag",

"online_documentation"	 =>
"Online Documentation",

//----------------------------------------
//	Language for home
//----------------------------------------

"homepage" =>
"Homepage",

'utilities' =>
"Utilities",

//----------------------------------------
//	Language for urls
//----------------------------------------

"js_urls" =>
"Javascript URLs",

"js_details" =>
"Use this snippet of code to embed helpful Tag module Javascript functions into your Stand-Alone Entry Forms.",

//----------------------------------------
//	Language for Statistics
//----------------------------------------

"statistics" =>
"Statistics",

"tags" =>
"Tags",

"top_tags" =>
"Top Tags",

"add_tags" =>
"Add Tags",

"total_channel_entries_tagged" =>
"Total Channel Entries Tagged",

"percent_channel_entries_tagged" =>
"Percent Channel Entries Tagged",

"top_five_tags" =>
"Top Five Tags",

"search_tags" =>
"Search Tags...",

"all_tags" =>
"All Tags",

"harvest_description" =>
"This harvest tool will help you automatically pull tags from a channel field that was previously used for tagging or keywords. This will separate on spaces and create tags out of each word. It is inadvisable to use this on fields that contain full sentences, such as body, summary, or title fields as it will make a tag out of each word.",

'harvest_sources_desc' =>
"To harvest tag fields in channels you must first set them per channel in preferences.",

//----------------------------------------
//	Language for tag list
//----------------------------------------

'tag_id'						=>
"Tag ID",

"no_tags" =>
"There are currently no Tags.",

"tag" =>
"Tag",

"count" =>
"Count",

"tag_count" =>
"Count",

"author" =>
"Author",

"screen_name" =>
"Screen Name",

"entered_date" =>
"Entered Date",

"entry_date" =>
"Entry Date",

"edit_date" =>
"Last Edit Date",

'tag_added_by' =>
"Added By",

"delete" =>
"Delete",

"tag_type" =>
"Type",


//----------------------------------------
//	Language for manage tags
//----------------------------------------

"manage_tags" =>
"Manage Tags",

//----------------------------------------
//	Language for entries by tag
//----------------------------------------

'entry_title' =>
"Entry Title",

'entry_id' =>
"Entry ID",


"edit_entries_for_" =>
"Edit entries for ",

"channel_entries_by_tag" =>
"Channel Entries by Tag",

"channel_entries_by_tag_name" =>
"Channel Entries for \"%tag_name%\"",

"view_channel_entries_for_tag" =>
"View Channel Entries for this Tag.",

"title" =>
"Title",

"no_entries_found" =>
"There are currently no entries for this Tag.",

//----------------------------------------
//	Language for browse tags
//----------------------------------------

"browse_tags" =>
"Browse Tags",

'browse_tags_by' =>
"Browse Tags by",

"browse_instructions" =>
"Type in the field below to browse for Tags.",

'no_tags_found' =>
"No Tags Found",

'no_bad_tags_found' =>
"No Bad Tags Found",

//----------------------------------------
//	Language for edit tags
//----------------------------------------

"edit_tag" =>
"Edit Tag",

"update_tag" =>
"Update Tag",

'tag_entries' =>
"Entries",

'tag_view_entries' =>
"View Entries",

"tag_exists" =>
"The tag, %tag_name%, has already been added.",

"tags_combined" =>
"Any entries having the Tag of '%old_tag_name%' have been successfully re-tagged with '%new_tag_name%'.",

"tag_updated" =>
"Your tag has been updated.",

//----------------------------------------
//	Language for manage bad tags
//----------------------------------------

"bad_tag" =>
"Bad Tag",

"bad_tags" =>
"Bad Tags",

"tag_mark_as_bad_" =>
"Mark tag as bad ",

"bad_tag_explanation_long" =>
"Bad Tags are blacklisted words that you do not want submitted into the system.",

"manage_bad_tags" =>
"Manage Bad Tags",

"no_bad_tags" =>
"There are currently no bad Tags.",

//----------------------------------------
//	Language for edit bad tags
//----------------------------------------

"add_bad_tag" =>
"Add as Bad Tag",

"add_bad_tags" =>
"Add Bad Tags",

"add_bad_tags_instructions" =>
"Separate each bad Tag with a line break.",

"tag_name" =>
"Tag",

"tag_name_required" =>
"A Tag name is required.",

"bad_tag_exists" =>
"The bad Tag, %tag_name%, has already been added.",

"bad_tag_added" =>
"The following Bad Tag has been added: %tag_name%.",

"bad_tags_added" =>
"The following Bad Tags have been added: %tag_name%.",

//----------------------------------------
//	Language for delete tags
//----------------------------------------

"tag_delete_confirm" =>
"Delete Tag Confirmation",

"tag_delete_question" =>
"Are you sure you want to delete %i% %tags%?",

"action_can_not_be_undone" =>
"This action cannot be undone.",

"tag_deleted" =>
"%i% Tag successfully deleted.",

"tags_deleted" =>
"%i% Tags successfully deleted.",

//----------------------------------------
//	Language for delete bad tags
//----------------------------------------

'tag_remove' =>
"Remove",

'remove_bad_tags' =>
"Remove from Bad Tags",

"bad_tag_delete_confirm" =>
"Remove from Bad Tags list Confirmation",

"bad_tag_delete_question" =>
"Are you sure you want to remove %i% %tags% from Bad Tags list?",

"action_can_not_be_undone" =>
"This action cannot be undone.",

"bad_tag_deleted" =>
"%i% Tag successfully removed from Bad Tags list.",

"bad_tags_deleted" =>
"%i% Tags successfully removed from Bad Tags list.",

// -------------------------------------
//	tag field sync
// -------------------------------------

'sync_tags_fields' =>
"Sync Tag Fields",

'update_tag_field' =>
"Update Tag Field",

'update_tag_field_purpose' =>
"This will update tags for each entry via AJAX and will notify you when all entries have been finished. Click 'Sync Tag Fields' to begin.",

'update_all_tag_counts' =>
"Update All Tag Counts",

"updating_tag_fields_for_entry_" =>
"Updating tag fields for entry: ",

'number_of_entries_updated' =>
"Number Of Entries Updated",

'tag_fields_complete' =>
"All Tag Fields have been completed.",

'no_tag_custom_field_type' =>
"You have no Tag Custom Field Type to sync.",

'no_tag_fields_needed_updating' =>
"No Tag Fields needed updating.",

'tag_field_updated' =>
"Tag Field Updated",

// -------------------------------------
//	tag counts
// -------------------------------------

'update_tag_counts' =>
"Update Tag Counts",

'update_tag_count_from_upgrade_notice' =>
"The Tag 4.1.0 update adds support for tag group counts and needs to update each individual tag's total entries count to accommodate.",

'update_tag_count_purpose' =>
"This will update tag counts one by one via AJAX and will notify when all tags have been recounted. Click 'Update All Tag Counts' to begin.",

'update_all_tag_counts' =>
"Update All Tag Counts",

"updating_counts_for_tag_" =>
"Updating counts for Tag: ",

'percent_completed' =>
"Percent Completed",

'number_of_tags_updated' =>
"Number Of Tags Updated",

'wrong_value' =>
"You have submitted an incorrect value type",

'pause' =>
"Pause",

'resume' =>
"Resume",

'return_to_tag_mcp' =>
"You may now return to the Tag Control Panel.",

'tag_recounts_complete' =>
"All Tag recounts have been completed.",

//----------------------------------------
//	Language for manage prefs
//----------------------------------------

"tag_preferences" =>
"Preferences",

'site_switcher' =>
"Site Switcher",

'channel_switcher' =>
"Channel Switcher",

"site_preferences" =>
"Site Preferences",

"site_preferences_for_" =>
"Site Preferences For",

'channel_preferences' =>
"Channel Preferences",

'channel_preferences_for_' =>
"Channel Preferences For",

'no_channels_for_site' =>
"No Channels for Site",

'tag_preferences_updated' =>
"Preferences Updated",

"tag_module_separator" =>
"Tag Separator",

'tag_module_separator_subtext' =>
"This is used on the front end when parsing input for tag searches or custom tag inputs for SAEF.",

"tag_module_convert_case" =>
"Convert all Tags to Lowercase",

"tag_module_allow_tag_creation_publish" =>
"Allow New Tag Creation via Publish Tab",

"tag_module_allow_tag_creation_publish_subtext" =>
"If set to 'No', then no NEW tags can be created via the Publish area, either via a Publish tab
or a Tag Custom Channel Field.  Only existing tags can be submitted for an entry.  This allows
an Administrator to control the tags allowed for entires.",

"tag_module_publish_entry_tag_limit" =>
"Maximum Tags Allowed per Entry in Publish Tab",

'tag_preference_maximum_tags_allowed' =>
"Maximum %n% Tags Allowed",

"tag_module_enable_tag_form" =>
"Enable Tag Form for Site Visitors",

"y" =>
"Yes",

"n" =>
"No",

"tag_field" =>
"Tags",

"tag_harvest_field" =>
"Tag Harvesting Custom Field",

"tag_harvest_field_for_" =>
"Tag Harvesting Custom Field For",

"tag_harvest_field_subtext" =>
"If you wish to use the Tag Harvest Tool at any point: any text values in this custom field will be converted into proper Tag module tags upon submission of the Harvest Tool. This respects the Tag Separator preference.",

"preferences_for_site" =>
"Preferences For Site",

"preferences_for_" =>
"Preferences For",

"publish_tab_label" =>
"Publish Tab Label",

"publish_tab_label_for_" =>
"Publish Tab Label for",

"publish_tab_label_subtext" =>
"Leave the field blank to disable the Tag module's Publish tab for a given channel.",

"publish_tab_label_deprecated" =>
"Did you know that Tag is now a field type? It is the recommened way to use it in the publish area. This publish tab label is deprecated, but left in for legacy use.",

'choose_custom_field' =>
"Choose Custom Field",

'explode_input_on_separator' =>
"Separate Tag Field Input on Separator",

'explode_input_on_separator_subtext' =>
"This option lets you input multiple tags into the tag field at the same time, separated by the chosen tag separator. For instance, with this option enabled, and the tag separator set to 'comma', entering 'one, two, three' will add three tags, 'one', 'two', and 'three', to your tag field.",

"explode_input_on_separator_note" =>
"Text separated with a <span class=\"sep\">%sep%</span> will be split into multiple tags.",

"select_delimiter" =>
"Select Delimiter: ",

"enable_explode_controls" =>
"Enable User Controls for Separating Tag Field Input on Separator",

"enable_explode_controls_subtext" =>
"Allows users to have extra controls, such as choosing the text separator, when enabling the 'Separating Tag Field Input on Separator' preference.",


//--------------------------------------------
//	tag groups
//--------------------------------------------

'viewing_tags_for_group' =>
"Viewing Tags for Group",

'view_tags_in_all_groups' =>
"View Tags In All Groups",

'undefined_tag_group' =>
"Undefined Tag Group",

'tag_groups' =>
"Tag Groups",

'tag_group' =>
"Tag Group",

'group_id' =>
"Group ID",

'group_name' =>
"Group Name",

'group_short_name' =>
"Group Short Name",

'delete_tag_groups' =>
"Delete Tag Groups",

'insert_new_tag_group' =>
"Insert New Tag Group",

'cancel' =>
"Cancel",

'error' =>
"Error",

'tag_limit_reached' =>
"Tag Limit of %num% reached.",

'new_group_name' =>
"New Group Name",

'short_name' =>
"Short Name",

'tag_group_subtext' =>
"Tag groups allow the usage of more than one tag field per Channel. New tag group names can only have letters, numbers and underscores",

"tag_group_name_taken" =>
"The tag group name you have entered has the same short name as another tag group that already exists. Please choose another tag group name.",

'all_open' =>
"All Open",

'all_open_subtext' =>
"This dictates whether or not the Suggest Tags and Top Tags fields in the publish are open by default.",

'delete_tag_groups' =>
"Delete Tag Groups",

'tag_groups' =>
"Tag Groups",

'tag_group_id' =>
"Group ID",

'tag_group_name' =>
"Group Name",

'tag_group_short_name' =>
"Group Short Name",

'locked' =>
"Locked",

'total_tags_in_group' =>
"Total Tags In Group",

'view_group_tags' =>
"View Group Tags",

'view_tags_in_group' =>
"View Tags In Group",

"tag_group_delete_confirm" =>
"Delete Tag Group Confirmation",

"tag_group_delete_question" =>
"Are you sure you want to delete %i% Tag Group(s)?",

"tag_group_deleted" =>
"Tag Group(s) successfully deleted.",

'create_tag_group' =>
"Create Tag Group",

'update_tag_group' =>
"Update Tag Group",

'edit_tag_group' =>
"Edit Tag Group",

'tag_group_updated' =>
"Tag Group Updated",

'tag_group_created' =>
"Tag Group Created",

'locked_tag_group_description' =>
"Tag Groups that are currently being used in custom fields are locked until that field is removed from its group.",

'suggest_from' =>
"Autocomplete, Suggest and Top Tags from",

'suggest_from_subtext' =>
"Normally, Tag custom fields will only display autocomplete results, as well as suggest and show top tags from its own group. You can use this setting to display autocomplete results, as well as suggest and show top tags from all Tag groups.",

'this_group' =>
"This Group",

'all_groups' =>
"All Groups",

'top_tag_limit' =>
"Top Tags Limit",

'top_tag_limit_subtext' =>
"Limit the amount of Top Tags shown.",

//----------------------------------------
//	Language for harvest
//----------------------------------------

'tag_harvest' =>
"Harvest",

'harvest_tags' =>
"Harvest tags from pre-existing fields.",

"tag_harvest_instructions" =>
"You can harvest data from your site either by mapping categories into tags or by turning the
contents of a custom channel field into Tags. In either case, entries will be assigned to the Tags
harvested as appropriate.",

"categories" =>
"Categories",

"galleries" =>
"Galleries",

'harvest_sources' =>
"Harvest Sources",

'per_batch' =>
"Batch Size",

"harvest_from_channel_categories" =>
"Channel Categories",

"harvest_from_channel_tag_field" =>
"Tag Fields for Channels",

"tag_harvest_batch_process" =>
"Harvest Batch Process",

"tag_process_batch_of" =>
"Process %batch% of %total%",

"start" =>
"Start",

"continue" =>
"Continue",

"done" =>
"Done",

'error_invalid_custom_fields_for_channels' =>
"Error: One of the Custom Channel Fields is Invalid in your Tag Preferences",

'success_harvest_processing_is_complete' =>
"Success!  Harvest Processing is Complete!",

//----------------------------------------
//	Language for edit CP
//----------------------------------------

"tag_field_instructions_" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a line break.",

"tag_field_instructions_linebreak" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a line break.",

"tag_field_instructions_comma" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a comma.",

"tag_field_instructions_colon" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a colon.",

"tag_field_instructions_semicolon" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a semicolon.",

"tag_field_instructions_space" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a space.",

"tag_field_instructions_doublepipe" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a double pipe.",

"tag_field_instructions_pipe" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a pipe.",

"tag_field_instructions_newline" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a hard return.",

"tag_field_instructions_tilde" =>
"Use this field to freely enter Tags for your entry. Separate each Tag with a tilde.",

"tag_browser" =>
"Tag Browser",

"tag_browser_instructions" =>
"Type in the field below to search for Tags. Click to add tag to the entry. List ALL Tags with an asterisk (*).",

"tag_browser_results" =>
"Tag browse results",

"tag_collector" =>
"Tag collector",

"searching" =>
"Searching...",

"or" =>
"OR",

"suggest_tags" =>
"Suggest Tags",

'search_all_sites'	=>
"Search All Sites",

"type_above" =>
"Please type in the field above.",

"no_matching_tags" =>
"Couldn't find any matching Tags.",

'popular_tags' =>
"Most Popular Tags",

'popular_tags_instructions' =>
"Click to add tag to the entry.",

// -------------------------------------
//	separators
// -------------------------------------

'separator_colon'		=>
'Colon \':\'',

'separator_comma'		=>
'Comma \',\'',

'separator_doublepipe'	=>
'Doublepipe \'||\'',

'separator_newline'		=>
'Newline \'\\\n\'',

'separator_pipe'		=>
'Pipe \'|\'',

'separator_semicolon'	=>
'Semicolon \';\'',

'separator_space'		=>
'Space \' \'',

'separator_tab'			=>
'Tab \'\\\t\'',

'separator_tilde'		=>
'Tilde \'~\'',

//----------------------------------------
//	Language for insert tags
//----------------------------------------

"missing_entry_id" =>
"Entry ID is missing.",

"no_tags_submitted" =>
"No Tags were submitted.",

"not_authorized" =>
"You are not authorized to submit this form.",

//----------------------------------------
//	Tag Recount Tab
//----------------------------------------

'recount' =>
"Recount Stats",

'tag_recount' =>
"Recount Tag Group Stats",

'no_tags_to_recount'			=>
"There were no tags to recount.",

'tags_successfully_recounted' 	=>
"Tags Successfully Recounted",

'tag_recount_running'			=>
"Counting %num tags starting from %start. Total tags: %total.",

//----------------------------------------
// Backport Tags from Tab
//----------------------------------------

'tag_sync' =>
"Sync",

'tag_field_sync' =>
"Tag Field Sync",

'tag_field_sync_subtext' =>
"Synchronize tags with the tag custom field to make your tags searchable by the Search module.
Only necessary if you previously used the Tag module's Publish tab.",

//----------------------------------------
//	Tag Subscription
//----------------------------------------

'successful_tag_subscribe' =>
"You were successfully subscribed to the tag.",

'successful_tag_unsubscribe' =>
"You were successfully unsubscribed from the tag.",

'tag_subscribed' =>
"Tag Subscribed",

'tag_unsubscribed' =>
"Tag Unsubscribed",

//----------------------------------------
//----------------------------------------
//	General
//----------------------------------------

"submit" =>
"Submit",

"member" =>
"Member",

"search" =>
"Search",

"edit" =>
"Edit",

"action_can_not_be_undone" =>
"This action cannot be undone.",

// --------------------------------------------
//  Errors
// --------------------------------------------

"error_tag_parsing" =>
"There was a tag parsing error.",

'tag_module_disabled' =>
"The Tag module is currently disabled.  Please insure it is installed and up to date by going
to the module's control panel in the ExpressionEngine Control Panel",

'disable_module_to_disable_extension' =>
"To disable this extension, you must disable its corresponding <a href='%url%'>module</a>.",

'enable_module_to_enable_extension' =>
"To enable this extension, you must install its corresponding <a href='%url%'>module</a>.",

'cp_jquery_requred' =>
"The 'jQuery for the Control Panel' extension must be <a href='%extensions_url%'>enabled</a> to use this module.",

// --------------------------------------------
//  Update Routine
// --------------------------------------------

'update_tag_module' =>
"Update Tag Module",

'tag_update_message' =>
"You have recently uploaded a new version of Tag, please click here to run the update script.",

'update_successful' =>
"Update Successful!",

"upgrade_message" =>
"It looks like you have installed a new version of Tag. We recommend that you run the upgrade script",

"update_successful" =>
"The module was successfully updated.",

"update_failure" =>
"There was an error while trying to update your module to the latest version.",


// END
''=>''
);