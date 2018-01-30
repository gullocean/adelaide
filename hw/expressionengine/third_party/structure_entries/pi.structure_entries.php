<?php
/*
====================================================================================================
 Author: Peter Lewis - peter@peteralewis.com
 http://www.peteralewis.com
====================================================================================================
 This file must be placed in the /system/plugins/ folder in your ExpressionEngine installation.
 package 		Structure Entries
 version 		Version 1.2.8
 copyright 		Copyright (c) 2011 Peter Lewis
 license 		Attribution No Derivative Works 3.0: http://creativecommons.org/licenses/by-nd/3.0/
 Last Update	April 2011
----------------------------------------------------------------------------------------------------
 Purpose: Extends the Structure Module with a powerful tag pair allowing html markup freedom
====================================================================================================

Change Log

v1.0.8	Fix for status, default if no status specified is != 'closed'
		Fix for EE update (v1.6.9) - page URI
		Added new variable {current_parent} which is set to true if parent of current page
v1.0.9	Fix for current_parent variable not working!  Tsk!
v1.0.10 Bug fix for limiting depth and close markup not closing parents with deeper children
		Added encoding of output (htmlentities and category variables parsed with htmlspecialchars)
v1.0.11 License change
v1.0.12 Added new parameter for converting special characters to html entities, added parse="inward" to documentation
v1.0.13 Added parent="current" parameter
v1.1.0 Re-written the FieldFrame parsing
v1.1.1 Added multiple parentID's
v1.1.2 Added match_parent parameter
v1.2.0 Compatible with EE v2
v1.2.1 Fixed some bugs and errors (db results_array and parentMatch variable)
		New variable added: {current_parent}
		New variable added: {parent_active}
v1.2.2 Fixed bug with extra "/" being added at front with index.php rewrites
v1.2.3 Fixed bug with current_page flagging root as current on certain listing pages children
v1.2.4 Fixed close_markup bug(s)
v1.2.5 Various fixes and re-coding
		current_page & current_parent variables improved
		added no_results variable
		added Next and previous functions
		added parsing of directory variables
		added Matrix support (tested with v2.0.11)
v1.2.6 Fixed bug with Matrix integration
        Disabled Matrix integration due ot user issues
v1.2.7 Minor fixes including multiple parents & include_parent bug (with depth)
v1.2.8 Matrix support re-introduced
*/

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
						'pi_name'			=> 'Structure Entries',
						'pi_version'		=> '1.2.8',
						'pi_author'			=> 'Peter Lewis',
						'pi_author_url'		=> 'http://www.peteralewis.com/',
						'pi_description'	=> 'Extends the Structure Module with a tag pair allowing html markup freedom, supports all EE standard and custom fields, categories and P&T Matrix.',
						'pi_usage'			=> Structure_entries::usage()
					);

class Structure_entries {

	var $return_data;

	//###
	//###   Template tag pair to loop through relevant Structure managed entries allowing for custom markup   ###
	//###   Template parameters: parent			=	Entry ID of parent entry or URL of parent
	//###   Template parameters: category_id	=	Category ID to restict results to category matches only
	//###   Template parameters: depth			=	How many levels down to go with displaying/including children
	//###   Template parameters: limit			=	Restrict the amount of entries returned
	//###   Template parameters: random			=	Randomise the order of the entries returned
	//###	Returns: true or false, or string containing error
	//###
	function Structure_entries() {
		//###   Get EE Super Global   ###
		$this->EE =& get_instance();

		//###   Get Site ID for SQL   ###
		$site_id = $this->EE->config->item('site_id');

		//###   Load EE Typography Class   ###
		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		//###   Get initial URL path (e.g. /index.php)   ###
		$tempIndexArray = explode("/", str_replace(array("http://","https://"), "", $this->EE->functions->fetch_site_index()) );
		array_shift($tempIndexArray);

		$siteIndex = implode("/", $tempIndexArray);
		if (substr($siteIndex, -1, 1) == "/")
			$siteIndex = substr($siteIndex, 0, -1);
		if (substr($siteIndex, 0, 1) != "/" && strlen($siteIndex) > 0)
			$siteIndex = "/" . $siteIndex;

		$site_pages = $this->get_site_pages();
		if ($site_pages === false)
			return;

		$output = "";

		//###   Setup the Cache   ###
		if(!isset($this->EE->session->cache)) $this->EE->session->cache = array();
		if(!isset($this->EE->session->cache['structure_entries'])) $this->EE->session->cache['structure_entries'] = array();

		//###   Get the upload folder paths   ###
//		$this->EE->load->library('typography');
//		$this->EE->typography->initialize();
//		$folderPaths = $this->EE->typography->file_paths;
		
		//###   Get Template Tags   ###
		$parentURL = "";
		$parentID = 0;
		$catID = 0;
		$depth = 0;

		//###   Parent URL or entry_id   ###
		$param = html_entity_decode($this->EE->TMPL->fetch_param('parent'));
		if ($param == "current") {
			$parentURL = $this->EE->functions->fetch_current_uri();
		} else if (substr($param, 0, 1) == "/" || substr($param, 0, 7) == "http://") {
			$parentURL = $param;
			if (substr($parentURL, -1, 1) !== "/")
				$parentURL = $parentURL . "/";
		} else {
			$parentID = preg_replace("/[^0-9\|]/", '', $param);
			if (strpos($parentID, "|"))
				$parentID = explode("|", $parentID);
		}
		if (empty($parentID) && !empty($parentURL))
			$parentID = array_search($parentURL, $site_pages['uris']);
		if (empty($parentID))
			$parentID = 0;

		//###   category ID   ###
		$param = $this->EE->TMPL->fetch_param('category_id');
		$catID = preg_replace("/[^0-9]/", '', $param);
		if (empty($catID))
			$catID = 0;

//TO DO - HANDLE Multiple categories and "NOT" (excluding categories)
//TO DO - weblog parameter, restricting weblog - include the NOT option and multiples

		//###   Child/Parent Depth   ###
		$param = $this->EE->TMPL->fetch_param('depth');
		$depth = preg_replace("/[^0-9]/", '', $param);
		if (empty($depth))
			$depth = 0;

		//###   Limit the amount of returns   ###
		$param = $this->EE->TMPL->fetch_param('limit');
		$limit = preg_replace("/[^0-9]/", '', $param);

		//###   Check if Status is restricted   ###
		$param = $this->EE->TMPL->fetch_param('status');
		$param = $this->EE->db->escape_str($param);
		$restrictStatus = $this->EE->security->xss_clean($param);

		//###   Debug!   ###
		$debug = false;
		if( $param = $this->EE->TMPL->fetch_param('debug') )
			if (strtolower($param) === "true" || strtolower($param) === "t" || strtolower($param) === "yes" || strtolower($param) === "y" || strtolower($param) === "1")
				$debug = true;

		//###   Randomise the entries returned   ###
		$random = false;
		if( $param = $this->EE->TMPL->fetch_param('random') )
			if (strtolower($param) === "true" || strtolower($param) === "t" || strtolower($param) === "yes" || strtolower($param) === "y" || strtolower($param) === "1")
				$random = true;

		//###   Convert html entities?   ###
		$convertHTML = false;
		if( $param = $this->EE->TMPL->fetch_param('convert_html') )
			if (strtolower($param) === "true" || strtolower($param) === "t" || strtolower($param) === "yes" || strtolower($param) === "y" || strtolower($param) === "1")
				$convertHTML = true;

		//###   Include the parent in the output?   ###
		$matchParent = false;
		if( $param = $this->EE->TMPL->fetch_param('include_parents') )
			if (strtolower($param) === "true" || strtolower($param) === "t" || strtolower($param) === "yes" || strtolower($param) === "y" || strtolower($param) === "1")
				$matchParent = true;
		if( $param = $this->EE->TMPL->fetch_param('include_parent') )
			if (strtolower($param) === "true" || strtolower($param) === "t" || strtolower($param) === "yes" || strtolower($param) === "y" || strtolower($param) === "1")
				$matchParent = true;

		//###   Add Matrix Field Support   ###
		if ( file_exists(APPPATH . 'third_party/matrix/ext.matrix'.EXT) ) {
			if (!class_exists('Matrix_ext'))
				require_once(APPPATH . 'third_party/matrix/ext.matrix'.EXT);
			$Matrix = new Matrix_ext();	//###   Matrix Class   ###

			if (!class_exists('EE_Fieldtype'))
				require_once PATH_THIRD.'../fieldtypes/EE_Fieldtype'.EXT;

			if (!class_exists('Matrix_ft'))
				require_once PATH_THIRD.'matrix/ft.matrix'.EXT;
		}

		if (!class_exists('Structure'))
			require_once(APPPATH . 'third_party/structure/mod.structure'.EXT);
		$Structure = new Structure();	//###   Structure Class   ###

		$sqlStructure = $this->get_structure_data();

		//###   Get Field Titles   ###
		$sql = "SELECT field_id, field_name
				FROM exp_channel_fields
				WHERE site_id = ".$site_id;
		$sqlFields = $this->EE->db->query($sql);

		$matchCounter = 0;			//###   Counts matched entries (for output tag)   ###
		$switchCounter = 0;			//###   Counts each time switch is used   ###
		$siblingCounter = array();	//###   Counts siblings (for output tag)   ###
		$structureArray = array();

		//###   Debug Output   ###
		$this->EE->TMPL->log_item("structure_entries: Starting main loop through entries. Total rows from DB=".$sqlStructure->num_rows() );

		foreach ($sqlStructure->result_array() as $structureField) {
			$entry_id = $structureField['entry_id'];
			$status = $structureField['status'];
			if (!empty($entry_id) && ($status != "closed" || $restrictStatus == "closed")) {
				$entryURL = $siteIndex . $site_pages['uris'][$entry_id];

				//###   Get specific entry information   ###
				if (isset($this->EE->session->cache['structure_entries']['entryData'.$entry_id])) {
					$sqlEntryResult = $this->EE->session->cache['structure_entries']['entryData'.$entry_id];
				} else {
					$sql = "SELECT exp_channel_data.*, exp_channel_titles.*, exp_channels.channel_name
							  FROM exp_channel_data, exp_channel_titles, exp_channels
							 WHERE exp_channel_data.entry_id = ".$entry_id."
							   AND exp_channel_titles.entry_id = ".$entry_id."
							   AND exp_channels.channel_id = exp_channel_titles.channel_id
							 LIMIT 1";
					$sqlEntryResult = $this->EE->db->query($sql);
					$this->EE->session->cache['structure_entries']['entryData'.$entry_id] = $sqlEntryResult;
				}
				$EntryDetails = $sqlEntryResult->result_array();

				//###   Bug in Structure with ability to map an entry to be it's own parent   ###
				if ($structureField['parent_id'] == $structureField['entry_id'])
					$structureField['parent_id'] = 0;

				//###   Check complete parentage and ancestory - restricting depth of child/parents if set   ###
				$match = false;
				$searchParentID = $structureField['parent_id'];
				$depthCount = 1;
				do {
					if (is_array($parentID)) {
						if (in_array($searchParentID, $parentID)) {
							$match = true;
							break;
						}
					} else {
						if ($searchParentID == $parentID) {
							$match = true;
							break;
						}
					}

					$searchParentID = $this->get_parent($searchParentID);
					//if ($depth == 0 || $depth > $depthCount)
						$depthCount++;
				} while ($searchParentID != 0); // && $depthCount != $depth

				if ($parentID == 0)
					$match = true;

				if ($matchParent) {
					if ($entry_id == $parentID) {
						$match = true;
						$depthCount = 0;
					} else if (is_array($parentID)) {
						if (in_array($entry_id, $parentID)) {
							$match = true;
							$depthCount = 0;
						}
					}
				}

				if ($depth != 0 && $depthCount > $depth)
					$match = false;

				//###   Check Category   ###
				if ($catID > 0 && $match == true) {
					$match = $this->within_category($entry_id, $catID);
				}

				if ($match) {
					//###   Found Parent   ###

					//###   This entries Parent   ###
					$thisParentID = $this->get_parent($entry_id);
					$children = 0;
					$siblings = 0;

					//###   Increment sibling counter   ###
					$matchCounter++;
					if (!isset($siblingCounter[$thisParentID]))
						$siblingCounter[$thisParentID] = 1;
					else
						$siblingCounter[$thisParentID]++;

					$parentMatch = false;
					foreach ($sqlStructure->result_array() as $structureData) {
						//###   Get total siblings   ###
						if ($structureData['parent_id'] === $thisParentID) {
							//###   Matching Parents   ###
							$parentMatch = true;
							if ($catID > 0)
								$parentMatch = $this->within_category($structureData['entry_id'], $catID);

							if ($parentMatch)
								$siblings++;
						}

						//###   Get total children   ###
						if ($structureData['parent_id'] === $entry_id) {
							if ($catID > 0)
								$parentMatch = $this->within_category($structureData['entry_id'], $catID);

							if ($parentMatch)
								$children++;
						}
					} //###   End of foreach $sqlStructure->result

					$slug = $Structure->page_slug($entry_id);

					$structureArray[$entry_id]['parentID'] = $thisParentID;
					$structureArray[$entry_id]['siblings'] = $siblings;
					$structureArray[$entry_id]['children'] = $children;
					$structureArray[$entry_id]['slug'] = $slug;
					$structureArray[$entry_id]['depth'] = $depthCount;
					$structureArray[$entry_id]['sibling_count'] = $siblingCounter[$thisParentID];

					$output .= $this->EE->TMPL->tagdata;

//###   Debug Output   ###
$this->EE->TMPL->log_item("structure_entries: tagdata=".$this->EE->TMPL->tagdata );

					//###   Parse Matrix Fields   ###
					if (!empty($Matrix)) {
						//###   Legacy Matrix requires this to be unset   ###
						unset($this->EE->extensions->last_call);

						//###   Legacy Matrix required third parameter (from v2.1.4 or thereabouts it doesn't)   ###
						$output = $Matrix->channel_entries_tagdata($output, array("entry_id" => $entry_id, "entry_site_id" => $site_id), $EntryDetails[0]["channel_name"]);
					}

					//###   Loop through all the pair variables from template   ###
					foreach ($this->EE->TMPL->var_pair as $key => $val) {
						if (preg_match("/^close_markup/", $key)) {
							//$totalPairs = preg_match_all( "/".LD.$key.RD."(.*?)".LD.SLASH.$key.RD."/s", $output, $pairMatch);
							$totalPairs = preg_match_all( "/".LD.$key.RD."(.*?)".LD."\/".$key.RD."/s", $output, $pairMatch);

							//###   Loop through each pair   ###
							for ($pairLoop = 0; $pairLoop < $totalPairs; $pairLoop++) {
								$pairOutput = "";

								if ($children == 0 || $depth == $depthCount) {
									//###   This entry has no Children - so lowest depth (only time closing markup will be output)   ###

									//###   Set the LookupID to this entry   ###
									$lookupID = $entry_id;

									//###   Loop through all ancestory for current entry   ###
									for ($depthLoop = $depthCount; $depthLoop > ($matchParent?0:1); $depthLoop--) {
										//###   Replace variables and generate tag pair content   ###
										$buildOutput = str_replace(LD."depth".RD, $depthLoop, $pairMatch[1][$pairLoop]);
										$buildOutput = str_replace(LD."restricted_depth".RD, $depth, $buildOutput);
										$buildOutput = str_replace(LD."parent_id".RD, $structureArray[$lookupID]['parentID'], $buildOutput);
										$buildOutput = str_replace(LD."sibling_total".RD, $structureArray[$lookupID]['siblings'], $buildOutput);
										$buildOutput = str_replace(LD."siblings_total".RD, $structureArray[$lookupID]['siblings'], $buildOutput);
										$buildOutput = str_replace(LD."total_siblings".RD, $structureArray[$lookupID]['siblings'], $buildOutput);
										$buildOutput = str_replace(LD."total_sibling".RD, $structureArray[$lookupID]['siblings'], $buildOutput);
										$buildOutput = str_replace(LD."children_total".RD, $structureArray[$lookupID]['children'], $buildOutput);
										$buildOutput = str_replace(LD."total_children".RD, $structureArray[$lookupID]['children'], $buildOutput);
										$buildOutput = str_replace(LD."page_url".RD, $structureArray[$lookupID]['slug'], $buildOutput);
										$buildOutput = str_replace(LD."sibling_count".RD, $structureArray[$lookupID]['sibling_count'], $buildOutput);
										//if ($structureArray[$lookupID]['sibling_count'] == $structureArray[$lookupID]['siblings'])
										if ($structureArray[$lookupID]['sibling_count'] == $structureArray[$structureArray[$lookupID]['parentID']]['siblings'])
											$buildOutput = str_replace(LD."last_sibling".RD, "1", $buildOutput);
//										else
//											$buildOutput = str_replace(LD."last_sibling".RD, "1", $buildOutput);

										$pairOutput .= $buildOutput;

										$lookupID = $structureArray[$lookupID]['parentID'];
									} //###   End of for loop

//								} else {
//									$output = preg_replace( "/".LD.$key."(.*?)".$key.RD."/s", "", $output);
								}

								$output = str_replace( $pairMatch[0][$pairLoop], $pairOutput, $output );
							} //###   End of for loop
						}
						
					} //###   End of foreach ($this->EE->TMPL->var_pair)

					//###   Debug Output   ###
					$this->EE->TMPL->log_item("structure_entries: ID=".$entry_id." url=".$entryURL );

					//###   Loop through all the single variables from template   ###
					foreach ($this->EE->TMPL->var_single as $key => $fieldName) {
						if ($fieldName == "page_uri") {
							//###   This is the full URL to (and including) the page
							$output = $this->EE->TMPL->swap_var_single($fieldName, $entryURL, $output);

						} else if ($fieldName == "channel" || $fieldName == "channel_title") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $EntryDetails[0]["channel_name"], $output);

						} else if ($fieldName == "depth") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $depthCount, $output);

						} else if ($fieldName == "count" || $fieldName == "counter") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $matchCounter, $output);

						} else if ($fieldName == "parent_id") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $thisParentID, $output);

						} else if ($fieldName == "children_total" || $fieldName == "total_children") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $children, $output);

						} else if ($fieldName == "sibling_total" || $fieldName == "siblings_total" || $fieldName == "total_siblings" || $fieldName == "total_sibling") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $siblings, $output);

						} else if ($fieldName == "sibling_count") {
							$output = $this->EE->TMPL->swap_var_single($fieldName, $siblingCounter[$thisParentID], $output);

						} else if ($fieldName == "last_sibling" || $fieldName == "last_child") {
							if ($siblingCounter[$thisParentID] == $siblings)
								$output = $this->EE->TMPL->swap_var_single($fieldName, 1, $output);
							else
								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);

						} else if ($fieldName == "page_url") {
							//###   This is the individual segment URL (not the full URL)
							$output = $this->EE->TMPL->swap_var_single($fieldName, $slug, $output);

						} else if (preg_match("/^switch\s*=.+/i", $fieldName)) {
							$varParam = $this->EE->functions->assign_parameters($fieldName);
							if (isset($varParam['switch'])) {
								$varOptions = explode("|", $varParam['switch']);
								//$switchOption = $varOptions[(($matchCounter-1) + count($varOptions)) % count($varOptions)]; //###   Will count using entries loop
								$switchOption = $varOptions[($switchCounter + count($varOptions)) % count($varOptions)]; //###   Will count using switch usage
								//$switchOption = $varOptions[$siblingCounter[$thisParentID] + count($varOptions)) % count($varOptions)]; //###   Will count using sibling counter
								$switchCounter++;
							}
							$output = $this->EE->TMPL->swap_var_single($fieldName, $switchOption, $output);

						} else if (isset($EntryDetails[0][$fieldName])) {
							//###   Replace any built-in EE fields   ###
							if ($convertHTML)
								$newContent = htmlentities($EntryDetails[0][$fieldName], ENT_QUOTES, "UTF-8");
							else
								$newContent = $EntryDetails[0][$fieldName];
//							$output = $this->EE->TMPL->swap_var_single($fieldName, $newContent, $output);
							$output = $this->EE->TMPL->parse_variables_row($output, array($fieldName => $newContent));
//TO DO: Parse variables correctly with EE, including format parameter
/*							$variables = array();
							$variables[] = array($fieldName => $newContent);
							$output = $this->EE->TMPL->parse_variables($output, $variables); */
						}

						//###   Look through all the EE custom Field references in the Database, setting the correct Field Name   ###
						foreach($sqlFields->result_array() as $field) {
							//###   Find Field Reference   ###
							if ($field['field_name'] === $fieldName) {
								if ($convertHTML)
									$newContent = htmlentities($EntryDetails[0]["field_id_".$field['field_id']], ENT_QUOTES, "UTF-8");
								else
									$newContent = $EntryDetails[0]["field_id_".$field['field_id']];
								$output = $this->EE->TMPL->swap_var_single($fieldName, $newContent, $output);
								break;
							}
						} //###   End of foreach ($sqlFieldsResult)

						$compareIndex = $siteIndex;
						if ($fieldName == "current_parent") {
//###   Debug Output   ###
$this->EE->TMPL->log_item("structure_entries: current_parent variable: Actual URL=".$compareIndex ."/".$this->EE->uri->uri_string()." Structure page_uri=".$entryURL);

							if (strpos($compareIndex ."/".$this->EE->uri->uri_string(), $entryURL) !== false && $entryURL !== $compareIndex ."/". $this->EE->uri->uri_string() && $entryURL !== $compareIndex && $entryURL !== "/")
								$output = $this->EE->TMPL->swap_var_single($fieldName, 1, $output);
							else
								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
						}
						if ($fieldName == "parent_active") {
//echo "#".$compareIndex . $this->EE->uri->uri_string() ." ~ ". $entryURL."#<br />";
							$parentURL = rtrim($entryURL, "/");
							$parentURL = explode("/", $parentURL);
							array_pop($parentURL);
							$parentURL = implode("/", $parentURL);

//###   Debug Output   ###
$this->EE->TMPL->log_item("structure_entries: parent_active variable: Actual URL=[".$compareIndex ."/".$this->EE->uri->uri_string()."] parentURL=[".$parentURL."] entryURL=[".$entryURL."]");
							if (!empty($parentURL)) {
								if (strpos($compareIndex ."/". $this->EE->uri->uri_string(), $parentURL) !== false && $entryURL !== $compareIndex ."/". $this->EE->uri->uri_string() && $entryURL !== "/")
									$output = $this->EE->TMPL->swap_var_single($fieldName, 1, $output);
								else
									$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
							} else {
								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
							}
						}
						if ($fieldName == "current_page") {
//###   Debug Output   ###
$this->EE->TMPL->log_item("structure_entries: current_page variable: Actual URL=[".$siteIndex ."/".$this->EE->uri->uri_string()."] Structure page_uri=[".$entryURL."] page_url=[".$slug."] Structure slug=".$Structure->page_slug());
// TO DO - NOT CORRECTLY COMPARING PAGE - NEEDS FULL URI
//POSSIBLE FIX - NEEDS TESTING ON NORMAL WEBSITE:

//							if ($slug != $Structure->page_slug() || ($slug == "" && $siteIndex.$this->EE->uri->uri_string() != "")) {
//								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
							if ($slug == $Structure->page_slug() && $this->EE->uri->segment(2) === FALSE) {
								//###   Page URL (Slug) is identical, and at root level in URL (no segment 2)   ###
								$output = $this->EE->TMPL->swap_var_single($fieldName, 1, $output);
							} else if ($siteIndex."/".$this->EE->uri->uri_string()."/" == $entryURL) {
								//###   Full URL match   ###
								$output = $this->EE->TMPL->swap_var_single($fieldName, 1, $output);
							} else {
								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
							}
//$output = "#start#".$siteIndex."/".$this->EE->uri->uri_string()."#".$entryURL."#end#";
						}
						if ($fieldName == "current_sibling") {
							//###   Get Parent URL to compare   ###
							$parentURL = rtrim($entryURL, "/");
							$parentURL = explode("/", $parentURL);
							array_pop($parentURL);
							$parentURL = implode("/", $parentURL);
							//###   Get Current URL minus current page   ###
							$compareURL = explode("/", $compareIndex . $this->EE->uri->uri_string());
							array_pop($compareURL);
							$compareURL = implode("/", $compareURL);

							if ($parentURL == $compareURL)
								$output = $this->EE->TMPL->swap_var_single($fieldName, 1, $output);
							else
								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
//$output = $this->EE->TMPL->swap_var_single($fieldName, "~".$parentURL."~".$compareURL, $output);
						}

						//###   Replace any Category variables?   ###
						if (substr($fieldName, 0, 3) === "cat") {
							//###   Get Associated Categories   ###
							if (isset($this->EE->session->cache['structure_entries']['catLookup'.$entry_id])) {
								$sqlCatLookup = $this->EE->session->cache['structure_entries']['catLookup'.$entry_id];
							} else {
								$sql = "SELECT cat_id
										  FROM exp_category_posts
										 WHERE entry_id = ".$entry_id."
										 LIMIT 1";
								$sqlCatLookup = $this->EE->db->query($sql);
								$this->EE->session->cache['structure_entries']['catLookup'.$entry_id] = $sqlCatLookup;
							}
							$catLookup = $sqlCatLookup->result_array();

							if($sqlCatLookup->num_rows() > 0) {
								//###   Get list of categories   ###
								$sql = "SELECT * FROM exp_categories
										 WHERE site_id = $site_id
										   AND cat_id = ".$catLookup[0]['cat_id']."
										 LIMIT 1";
								$sqlCategories = $this->EE->db->query($sql);
								$categories = $sqlCategories->result_array();

								if($sqlCategories->num_rows() > 0) {
									//###   Currently only loops through the first matching category it finds for matching fields   ###
									foreach($categories[0] as $catField => $catValue) {
										$AlternativeName = "";
										if (substr($fieldName, 0, 8) === "category")
											$AlternativeName = "cat".substr($fieldName, 8);
										if ($fieldName === $catField || $AlternativeName === $catField) {
											$output = $this->EE->TMPL->swap_var_single($fieldName, htmlspecialchars($catValue), $output);
											break;
										}
									}
								} else {
									$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
								}
							} else {
								$output = $this->EE->TMPL->swap_var_single($fieldName, 0, $output);
							}
						} //###   End of Category IF

					} //###   End of foreach ($this->EE->TMPL->var_single)

					//###   Limit output if required   ###
					if (!empty($limit))
						if ($limit != 0 && $limit <= $matchCounter)
							break;


				} //###   End of $match IF

 
			}
		} //###   End of foreach ($sqlStructure->result)

		//###   Parse directory variables   ###
		$output = $this->EE->typography->parse_file_paths($output);

		//###   Replace all occurances of total_results variable (now we know it's value)
		$output = str_replace(LD."total_results".RD, $matchCounter, $output);

		//###   If no results, need to get any no_results conditional   ###
		if ($matchCounter == 0) {
			$output .= $this->EE->TMPL->tagdata;

			$output = str_replace(LD."no_results".RD, 1, $output);
		} else {
			$output = str_replace(LD."no_results".RD, 0, $output);
		}

		$this->return_data = $output;

	} //###   End of Structure_entries function



	//###
	//###   Internal function to communicate with the FieldFrame class and parse the relevant variables
	//###	Returns: Array
	//###
	//###   Added by Peter Lewis - www.twobelowzero.net - 05 Jan 2010
	//###
/*	function parseFieldFrameVars($tagdata, $row, $weblog=NULL) {
		global $FF;

		require_once(PATH . 'extensions/ext.fieldframe.php');
		$FFMain = new Fieldframe_Main(array(), array());	//###   FieldFrame Class   ###

		//###   $FF Class is defined within FieldFrame_Main Class   ###
		$FF->tagdata = $tagdata;
		$FF->row = $row;

		//###   Get ALL the FieldFrame fields from DB   ###
		if ($fields = $FF->_get_fields()) {
			$fields_by_name = array();

			foreach($fields as $field_id => $field) {
				$fieldsByName[$field['name']] = array(
					'data'     => (isset($row['field_id_'.$field_id]) ? $FF->_unserialize($row['field_id_'.$field_id], FALSE) : ''),
					'settings' => $field['settings'],
					'ftype'    => $field['ftype'],
					'helpers'  => array('field_id' => $field_id, 'field_name' => $field['name'])
				);
			}

			$FF->_parse_tagdata($tagdata, $fieldsByName, FALSE);
		}

		return $tagdata;
	} //###   End of parseFieldFrameVars function
*/


	//###
	//###   Function to get site page data and to check pages exist and Structure is installed
	//###	Returns: false or site data
	//###
	//###   Added by Peter Lewis - www.peteralewis.com - 12 February 2011
	//###
	private function get_site_pages() {
		//###   Setup the Cache   ###
		if(!isset($this->EE->session->cache)) $this->EE->session->cache = array();
		if(!isset($this->EE->session->cache['structure_entries'])) $this->EE->session->cache['structure_entries'] = array();

		//###   Get Site ID for SQL   ###
		$site_id = $this->EE->config->item('site_id');

		if (isset($this->EE->session->cache['structure_entries']['site_pages'.$site_id])) {
			$site_pages = $this->EE->session->cache['structure_entries']['site_pages'.$site_id];
		} else {
			$this->EE->db->select('site_pages');
			$this->EE->db->where('site_id', $site_id);
			$query = $this->EE->db->get('sites');

			$site_pages = unserialize(base64_decode($query->row('site_pages')));
			$site_pages = $site_pages[$site_id];

			//###   Save result to page-load Session   ###
			$this->EE->session->cache['structure_entries']['site_pages'.$site_id] = $site_pages;

			if (empty($site_pages)) {
				//###   Debug Output   ###
				$this->EE->TMPL->log_item("structure_entries: No pages are currently defined in Structure, so nothing to output!");
				return false;
			}
		}

		//###   Check if Structure Module is installed   ###
		$SQLResult = $this->EE->db->query("SELECT module_name
								   FROM exp_modules
								   WHERE module_name = 'Structure'
								   LIMIT 1");
		$StructureEnabled = ($SQLResult->num_rows() > 0) ? TRUE : FALSE;
		if (!$StructureEnabled) {
			//###   Debug Output   ###
			$this->EE->TMPL->log_item("structure_entries: Structure is not installed - this plugin extends and requires Structure");
			return false;
		}

		return $site_pages;
	} //###   End of get_site_pages function


	//###
	//###   Function to get structure data
	//###	Returns: false or structure data
	//###
	//###   Added by Peter Lewis - www.peteralewis.com - 12 February 2011
	//###
	private function get_structure_data() {
		//###   Setup the Cache   ###
		if(!isset($this->EE->session->cache)) $this->EE->session->cache = array();
		if(!isset($this->EE->session->cache['structure_entries'])) $this->EE->session->cache['structure_entries'] = array();

		//###   Get Site ID for SQL   ###
		$site_id = $this->EE->config->item('site_id');

//		if (isset($this->EE->session->cache['structure_entries']['structure_data'.$site_id])) {
//			$sqlStructure = $this->EE->session->cache['structure_entries']['structure_data'.$site_id];

//		} else {
			//###   Check if Status is restricted   ###
			$param = $this->EE->TMPL->fetch_param('status');
			$param = $this->EE->db->escape_str($param);
			$restrictStatus = $this->EE->security->xss_clean($param);

			//###   Check Status - default is not closed   ###
			$statusSQL = "";
			if (!empty($restrictStatus))
				$statusSQL = "AND expt.status = '$restrictStatus' ";
			else
				$statusSQL = "AND expt.status != 'closed' ";

			//###   Get top-level structure data from DB   ###
			$sql = "SELECT node.*,
						   expt.title,
						   expt.status
					  FROM exp_structure AS node
				INNER JOIN exp_channel_titles AS expt
						ON node.entry_id = expt.entry_id
					 WHERE node.site_id = $site_id
						   ".$statusSQL."
				  GROUP BY node.entry_id
				  ORDER BY node.lft";
			$sqlStructure = $this->EE->db->query($sql);
			if($sqlStructure->num_rows() < 0) {
				//###   Debug Output   ###
				$this->EE->TMPL->log_item("structure_entries: No Structure data was returned - check Structure is installed correctly");
				return false;
			}
			
			//###   Save result to page-load Session   ###
//			$this->EE->session->cache['structure_entries']['structure_data'.$site_id] = $sqlStructure;
//		}

		return $sqlStructure;
	} //###   End of get_structure_data function


	//###   Function to get the details for the next page - tag pair or single tag
	//###	Returns: for single tag, depends what was requested via parameter, or tag pair will return any variable defined
	//###
	//###   Added by Peter Lewis - www.peteralewis.com - 12 February 2011
	//###
	function next() {
		return $this->getClosestPage(0, "next");
	} //###   End of next function


	//###   Function to get the details for the previous page - tag pair or single tag
	//###	Returns: for single tag, depends what was requested via parameter, or tag pair will return any variable defined
	//###
	//###   Added by Peter Lewis - www.peteralewis.com - 12 February 2011
	//###
	function previous() {
		return $this->getClosestPage(0, "previous");
	} //###   End of previous function


	//###   Function to get the details for the next page - tag pair or single tag
	//###	Returns: for single tag, depends what was requested via parameter, or tag pair will return any variable defined
	//###
	//###   Added by Peter Lewis - www.peteralewis.com - 23 February 2011
	//###
	private function getClosestPage($entry_id = 0, $direction) {
		//###   Get EE Super Global   ###
		$this->EE =& get_instance();

		require_once(APPPATH . 'third_party/structure/mod.structure.php');
		$Structure = new Structure();	//###   Structure Class   ###

		//###   Get Site ID for SQL   ###
		$site_id = $this->EE->config->item('site_id');

		$output = '';

		$this->EE->load->library('typography');
		$this->EE->typography->initialize();

		$site_pages = $this->get_site_pages();
		if ($site_pages === false)
			return;

		//###   Get Entry ID Parameter   ###
		$param = $this->EE->TMPL->fetch_param('entry_id');
		if (empty($param))
			$param = $entry_id;
		$entryID = preg_replace("/[^0-9]/", '', $param);
		if (empty($entryID))
			$entryID = 0;

		//###   Get Status Parameter   ###
		$param = $this->EE->TMPL->fetch_param('status');
		$param = $this->EE->db->escape_str($param);
		$restrictStatus = $this->EE->security->xss_clean($param);
		
		//###   Convert html entities?   ###
		$convertHTML = false;
		if( $param = $this->EE->TMPL->fetch_param('convert_html') )
			if (strtolower($param) === "true" || strtolower($param) === "t" || strtolower($param) === "yes" || strtolower($param) === "y" || strtolower($param) === "1")
				$convertHTML = true;

		$param = $this->EE->TMPL->fetch_param('page_uri');
		$param = $this->EE->db->escape_str($param);
		$currentPageURI = $this->EE->security->xss_clean($param);

		//###   Get initial URL path (e.g. /index.php)   ###
		$tempIndexArray = explode("/", str_replace(array("http://","https://"), "", $this->EE->functions->fetch_site_index()) );
		array_shift($tempIndexArray);

		$siteIndex = implode("/", $tempIndexArray);
		if (substr($siteIndex, -1, 1) == "/")
			$siteIndex = substr($siteIndex, 0, -1);
		if (substr($siteIndex, 0, 1) != "/" && strlen($siteIndex) > 0)
			$siteIndex = "/" . $siteIndex;
		$actualURI = $siteIndex ."/".$this->EE->uri->uri_string();

		if (empty($entryID) && empty($currentPageURI))
			$currentPageURI = $actualURI;

		if (empty($currentPageURI)) {
			//###   Debug Output   ###
			$this->EE->TMPL->log_item("structure_entries (Next): No page specified and no URI found?!?: Actual URL=".$actualURI);
			return;
		}

		$sqlStructure = $this->get_structure_data();

		$foundMatch = false;

		//###   Debug Output   ###
		$this->EE->TMPL->log_item("structure_entries (Next): Starting main loop through entries. Total rows from DB=".$sqlStructure->num_rows() );

		foreach ($sqlStructure->result_array() as $structureField) {
			$entry_id = $structureField['entry_id'];
			$status = $structureField['status'];
			$entryURL = $siteIndex . $site_pages['uris'][$entry_id];

			if (substr($entryURL, -1, 1) == "/")
				$entryURL = substr($entryURL, 0, -1);

			//###   Bug in Structure with ability to map an entry to be it's own parent   ###
			if ($structureField['parent_id'] == $structureField['entry_id'])
				$structureField['parent_id'] = 0;

			if (!empty($entry_id) && ($status != "closed" || $restrictStatus == "closed")) {

				//###   Get specific entry information   ###
				if (isset($this->EE->session->cache['structure_entries']['entryData'.$entry_id])) {
					$sqlEntryResult = $this->EE->session->cache['structure_entries']['entryData'.$entry_id];
				} else {
					$sql = "SELECT exp_channel_data.*, exp_channel_titles.*, exp_channels.channel_name
							  FROM exp_channel_data, exp_channel_titles, exp_channels
							 WHERE exp_channel_data.entry_id = ".$entry_id."
							   AND exp_channel_titles.entry_id = ".$entry_id."
							   AND exp_channels.channel_id = exp_channel_titles.channel_id
							 LIMIT 1";
					$sqlEntryResult = $this->EE->db->query($sql);
					$this->EE->session->cache['structure_entries']['entryData'.$entry_id] = $sqlEntryResult;
				}
				$EntryDetails = $sqlEntryResult->result_array();


				//###   Debug Output   ###
				$this->EE->TMPL->log_item("structure_entries (Next): entryURL=".$entryURL." actualURI=".$actualURI );

				if ($entryURL == $actualURI) {
					$foundMatch = true;

					if ($direction == "previous" || $direction == "prev") {
						$output .= $this->EE->TMPL->tagdata;

						//###   Debug Output   ###
						$this->EE->TMPL->log_item("structure_entries (previous): tagdata=".$this->EE->TMPL->tagdata);

						//###   Loop through all the single variables from template   ###
						foreach ($this->EE->TMPL->var_single as $key => $fieldName) {
							if ($fieldName == "page_uri") {
								//###   This is the full URL to (and including) the page
								$output = $this->EE->TMPL->swap_var_single($fieldName, $previousPageData["entryURL"], $output);

							} else if ($fieldName == "page_url") {
								//###   This is the individual segment URL (not the full URL)
								$output = $this->EE->TMPL->swap_var_single($fieldName, $previousPageData["page_slug"], $output);

							} else if (isset($previousPageData[$fieldName])) {
								//###   Replace any built-in EE fields   ###
								if ($convertHTML)
									$newContent = htmlentities($previousPageData[$fieldName], ENT_QUOTES, "UTF-8");
								else
									$newContent = $previousPageData[$fieldName];
								$output = $this->EE->TMPL->swap_var_single($fieldName, $newContent, $output);
							}
						} //###   End of foreach

						//###   Parse directory variables   ###
						$output = $this->EE->typography->parse_file_paths($output);

						return $output;
					}

				} else if ($foundMatch) {
					$output .= $this->EE->TMPL->tagdata;

					//###   Debug Output   ###
					$this->EE->TMPL->log_item("structure_entries (next): tagdata=".$this->EE->TMPL->tagdata);

					//###   Loop through all the single variables from template   ###
					foreach ($this->EE->TMPL->var_single as $key => $fieldName) {
						if ($fieldName == "page_uri") {
							//###   This is the full URL to (and including) the page
							$output = $this->EE->TMPL->swap_var_single($fieldName, $entryURL, $output);

						} else if ($fieldName == "page_url") {
							//###   This is the individual segment URL (not the full URL)
							$output = $this->EE->TMPL->swap_var_single($fieldName, $Structure->page_slug($entry_id), $output);

						} else if (isset($EntryDetails[0][$fieldName])) {
							//###   Replace any built-in EE fields   ###
							if ($convertHTML)
								$newContent = htmlentities($EntryDetails[0][$fieldName], ENT_QUOTES, "UTF-8");
							else
								$newContent = $EntryDetails[0][$fieldName];
							$output = $this->EE->TMPL->swap_var_single($fieldName, $newContent, $output);
						}
					} //###   End of foreach

					//###   Parse directory variables   ###
					$output = $this->EE->typography->parse_file_paths($output);

					if ($direction == "next")
						return $output;
				}
			} //###   End of Status conditional

			$previousPageData = $EntryDetails[0];
			$previousPageData["entryURL"] = $entryURL;
			$previousPageData["page_slug"] = $Structure->page_slug($entry_id);
		} //###   End of foreach ($sqlStructure->result)

	} //###   End of getClosestPage function


	//###
	//###   Internal function to get the parent of the passed entry_id
	//###	Returns: entry_id of the parent, or 0 if top level
	//###
	//###   Added by Peter Lewis - www.twobelowzero.net - 08 Dec 2009
	//###
	function get_parent($entry_id) {

		if (empty($entry_id))
			return;
		
		//###   Setup the Cache   ###
		if(!isset($this->EE->session->cache)) $this->EE->session->cache = array();
		if(!isset($this->EE->session->cache['structure_entries'])) $this->EE->session->cache['structure_entries'] = array();

		if (!empty($this->EE->session->cache['structure_entries']['parent'.$entry_id]))
			return $this->EE->session->cache['structure_entries']['parent'.$entry_id];

		//###   Get Entry's parent_id
		$sql = "SELECT *
				FROM exp_structure
				WHERE entry_id = $entry_id
				LIMIT 1";
		$sqlStructureParent = $this->EE->db->query($sql);
		if($sqlStructureParent->num_rows() > 0) {
			$ParentArray = $sqlStructureParent->result_array();
			$parentID = $ParentArray[0]['parent_id'];

			//###   Cache Entry's parent for later use
			$this->EE->session->cache['structure_entries']['parent'.$entry_id] = $parentID;
		} else {
			return;
		}

		return $parentID;
	} //###   End of GET_PARENT function

	
	//###
	//###   Internal function to check if the given entry_id is within the specified category
	//###	Returns: true or false
	//###
	//###   Added by Peter Lewis - www.twobelowzero.net - 08 Dec 2009
	//###
	function within_category($entry_id, $catID) {

		$match = false;
		if (isset($this->EE->session->cache['structure_entries']['catLookup'.$entry_id])) {
			$catLookup = $this->EE->session->cache['structure_entries']['catLookup'.$entry_id];
		} else {
			$sql = "SELECT cat_id
					  FROM exp_category_posts
					 WHERE entry_id = $entry_id";
			$catLookup = $this->EE->db->query($sql);
			$this->EE->session->cache['structure_entries']['catLookup'.$entry_id] = $catLookup;
		}

		foreach ($catLookup->result_array() as $category) {
			if ($category['cat_id'] == $catID) {
				$match = true;
				break;
			}
		}

		return $match;
	} //###   End of WITHIN_CATEGORY function


// ----------------------------------------
//  Plugin Usage
// ----------------------------------------
// This function describes how the plugin is used.
//  Make sure and use output buffering

function usage() {
	ob_start();
?>

Extends the excellent Structure module (www.buildwithstructure.com) with a tag pair that allows you to output your Structure managed page heirarchy with your own html markup, with control on the depth of parent/child and is completely independant of channel:entries (doesn't sit inside {exp:channel:entries} tag). This plugin can be used to output a menu, summarise a group of pages, bullet points on screen, whatever.  Supports all built in EE variables, custom fields, categories and Pixel & Tonic's Matrix fieldtype.

Obviously you'll need Structure installed!

WHAT'S NEW
Outputs debug information when running with EE 'Display Template Debugging' turned on.
Matrix support - parses all Matrix variables.
Image paths - finally they're correctly rendered, if using imgsizer, remember to use parse="inward" parameter.
Pagination - 2 new tag pair calls: {exp:structure_entries:previous}...{/exp:structure_entries:previous} & {exp:structure_entries:next}...{/exp:structure_entries:next}

Basic Example:
{exp:structure_entries depth="2" parent="10" category_id="7" limit="4"}
...
Your html markup and EE Fields.
...
{/exp:structure_entries}

It's a tag pair, so always needs the closing tag. All parameters are optional, defaults are shown below.

Parameters include:
depth - to restrict how deep the output is (defaults to 0 - all)
parent - to only show the children beneath the specified parent (including grand-children, etc).  This can be either the entry_id of the parent or the path to the parent. For multiple entry_id's separate with "|".
category_id - the category ID that you want shown - only entries (including children) assigned to the specified category will be shown.
limit - restricts the output so only the amount of pages you specify will be shown (handy for summary pages, where you might only want to show a few of the child pages). Default is unlimited.
status - limit output to a specific status (can only handle one status), by default outputs all status except "closed".
convert_html - parses output to convert relevant characters to HTML entries (e.g. '&' becomes '&amp;'), default is false.
include_parent - when a parent(s) is/are specified, setting this parameter to true will also include the parent's in the output.  Default is false.
parse="inward" - this is a built-in EE parameter which will parse this plug-in before processing any add-ons within (e.g. imgsizer, word_limit)

Additional variables available (beyond the standard EE fields, custom fields, category fields and Matrix) include:
{page_uri} = Is the entry URL value
{page_url} = Is the URL to the entry as returned by Structure (usually the page url_title)
{current_page} = returns 1 (true) if the current page matches the page output by the entries loop
{current_parent} = returns 1 (true) if the page output by the entries loop is the parent to the current active page
{current_sibling} = returns 1 (true) if the page output by the entries loop is a sibling (same level and same parent) to the current page
{parent_active} = returns 1 (true) if the page output by the entries loop is the child to the current active page
{depth} = Displays the current level of pages deep this page is (how many parents it has)
{restricted_depth} = the depth to restrict the output as passed in the parameter "depth"
{parent_id} = Entry ID for the parent of the current page, if it's top level, it returns 0
{count} = running counter for all entries that match the supplied parameters
{total_children} or {children_total} = Total entries that match the supplied parameters (depth, parent & category)
{last_child} or {last_sibling} = Returns true or false boolean to indicate if the entry is the last sibling in the current group and level of entries
{sibling_count} = running counter of siblings working through the current group at the same level
{total_siblings} or {sibling_total} = total siblings for the current group at this level
{channel} = channel name

{switch} variable is supported e.g. {switch="one|two|three"}

{close_markup}...{/close_markup} = use this variable pair to close the markup correctly, due to the complexity of unlimited depths, this should be used on any output deeper than 2.

Example 1:
{exp:structure_entries category_id="6" depth="1"}
    <li {if {current_page}}class="current"{/if}>
		<img src="{image-path}" alt="{image-alt-text}" />
		<a href="{page_uri}">{if "{alternative-title}" != ""}{alternative-title}{if:else}{title}{/if}</a>
	</li>
{/exp:structure_entries}

Displays the children (1 level) of a specific category (instead of using parent), using images and custom fields.

Example 2:
{exp:structure_entries category_id="4" depth="2"}
{if {depth} == 1}{!-- Top Level --}
        <li {if {current_parent}}class="current"{/if}{if {current_page}}class="active"{/if}>
            <strong class="opener"><a href="{page_uri}">{if "{alternative-title}" != ""}{alternative-title}{if:else}{title}{/if}</a></strong>
            <div class="slide">
    {if {children_total} == 0}{!-- No Children - so close markup --}
            </div>
        </li>
    {/if}
{if:else}{!-- Children (not top level) --}
    {if {sibling_count} == 1}{!-- First child - so open markup --}
                <ul class="drop-items level{depth}">
    {/if}
                    <li {if {current_parent}}class="current"{/if}{if {current_page}}class="active"{/if}>
                         <span class="title">{if "{alternative-title}" != ""}{alternative-title}{if:else}{title}{/if}</span>
                         {snippet-text}
	{close_markup}
       {if {total_children} == 0 || {depth} == {restricted_depth}}
                    </li>
       {/if}
       {if {last_sibling} && {sibling_count} == {sibling_total}}
                </ul><!-- End of level{depth} closing tags -->
        </li>
       {/if}
    {/close_markup}
{/if}
{/exp:structure_entries}

This displays pages 2 levels deep with custom markup based on a category.

Example 3:
<ul>
{exp:structure_entries}
{if {depth} == 1}{!-- Top Level --}
	<li {if {current_parent}}class="current"{/if}{if {current_page}}class="active"{/if}>
		<a href="{page_uri}">{title} Depth={depth} Parent={parent_id}</a>
  {if {children_total} == 0}{!-- No Children - so close markup --}
	</li>
  {/if}

{if:else}{!-- Children (not top level) --}
  {if {sibling_count} == 1}{!-- First child - so open markup --}
		<ul class="level{depth}">
  {/if}
			<li {if {current_parent}}class="current"{/if}{if {current_page}}class="active"{/if}>
				<a href="{page_uri}">{title} Depth={depth} Parent={parent_id}</a>
  {close_markup}
    {if {total_children} == 0 || {depth} == {restricted_depth}}
                    </li>
    {/if}
    {if {last_sibling} && {sibling_count} == {sibling_total}}
                </ul><!-- End of level{depth} closing tags -->
        </li>
    {/if}
  {/close_markup}
{/if}
{/exp:structure_entries}
</ul>

This will output any level of page depth with correct opening and closing of the list markup


Pagination Template Tags

Parameters include:
entry_id - Entry ID of the page you want the next or previous to be associated with.  If not specified, it will use the current URL.
status - limit output to a specific status (can only handle one status), by default outputs all status except "closed".
convert_html - parses output to convert relevant characters to HTML entries (e.g. '&' becomes '&amp;'), default is false.

Additional variables available (beyond the standard EE fields and custom fields)
{page_uri} = Is the entry URL value
{page_url} = Is the URL to the entry as returned by Structure (usually the page url_title)

{exp:structure_entries:next}
... Your html markup and EE Fields ...
{/exp:structure_entries:next}

{exp:structure_entries:previous}
... Your html markup and EE Fields ...
{/exp:structure_entries:previous}

Support

Support and more help can be found here: http://www.getsatisfaction.com/twobelowzero

	<?php
	$buffer = ob_get_contents();
	
	ob_end_clean();

	return $buffer;
} /* ###   End of usage() Function   ### */


}  /* ###   End of Class   ### */
?>