<?php

/*
=====================================================
 Member categories
-----------------------------------------------------
 http://www.intoeetive.com/
-----------------------------------------------------
 Copyright (c) 2011-2012 Yuri Salimovskiy
=====================================================
 This software is intended for usage with
 ExpressionEngine CMS, version 2.0 or higher
=====================================================
 File: mcp.member_categories.php
-----------------------------------------------------
 Purpose: Assign categories to site members
=====================================================
*/

if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}

require_once PATH_THIRD.'member_categories/config.php';

error_reporting(E_ALL & ~E_NOTICE);

class Member_categories_mcp {

    var $version = MEMBER_CATEGORIES_ADDON_VERSION;
    
    var $settings = array();
    
    var $perpage = 50;
    
    function __construct() { 
        // Make a local reference to the ExpressionEngine super object 
        $this->EE =& get_instance(); 

        $this->EE->lang->loadfile('members');  
        $this->EE->lang->loadfile('member_categories');  
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Member_categories' LIMIT 1");
        $this->settings = unserialize($query->row('settings'));    

    } 
    
    
    /**
     * Booking calendar
     *
     * @param	Array	Settings
     * @return 	void
     */
    function index()
    {
    	//exit();
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('pagination');
        
        $site_id = $this->EE->config->item('site_id');
        
        $p_config['base_url'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=index';
		
    	$vars = array();
        
        $vars['member_groups'] = array();
        $vars['member_groups'][''] = $this->EE->lang->line('all_groups');

        $this->EE->db->select('group_id, group_title');
        $this->EE->db->from('member_groups');
        $this->EE->db->where('site_id', $site_id);  
        $query = $this->EE->db->get();
        foreach ($query->result() as $obj)
        {
           $vars['member_groups'][$obj->group_id] = $obj->group_title;
        }
        
        $vars['categories'] = '';
        $vars['categories'][''] = $this->EE->lang->line('all_categories');
        
        $this->EE->db->select('cat_id, cat_name');
        $this->EE->db->from('categories');
        $this->EE->db->where('site_id', $site_id); 
        $this->EE->db->where_in('group_id', implode(',', $this->settings[$this->EE->config->item('site_id')]['category_groups'])); 
        $this->EE->db->order_by('cat_order', 'asc'); 
        $query = $this->EE->db->get();
        foreach ($query->result() as $obj)
        {
           $vars['categories'][$obj->cat_id] = $obj->cat_name;
        }
        
        $vars['filter_by'] = array(
                            'any' => $this->EE->lang->line('any_field'),
                            'username' => $this->EE->lang->line('username'),
                            'screen_name' => $this->EE->lang->line('screen_name'),
                            'email' => $this->EE->lang->line('email')
                            );

    	$vars['selected'] = array();
        $vars['selected']['keyword']=$this->EE->input->get_post('keyword');
        $vars['selected']['group_id']=$this->EE->input->get_post('group_id');
        $vars['selected']['category_id']=$this->EE->input->get_post('category_id');
        $vars['selected']['filter_by']=$this->EE->input->get_post('filter_by');
        
        $vars['selected']['rownum']=($this->EE->input->get_post('rownum')!='')?$this->EE->input->get_post('rownum'):0;
        
        $this->EE->db->start_cache();
        if ($vars['selected']['group_id']!='')
        {
            $this->EE->db->where('exp_members.group_id', $vars['selected']['group_id']);
        }
        if ($vars['selected']['category_id']!='')
        {
            $this->EE->db->where('exp_category_members.cat_id', $vars['selected']['category_id']);
        }
        if ($vars['selected']['keyword']!='')
        {
            switch($vars['selected']['filter_by'])
            {
                case 'username':
                    $this->EE->db->where("exp_members.username LIKE '%".$vars['selected']['keyword']."%'");
                    break;
                case 'screen_name':
                    $this->EE->db->where("exp_members.screen_name LIKE '%".$vars['selected']['keyword']."%'");
                    break;
                case 'email':
                    $this->EE->db->where("exp_members.email LIKE '%".$vars['selected']['keyword']."%'");
                    break;
                default:
                    $this->EE->db->where("exp_members.username LIKE '%".$vars['selected']['keyword']."%' OR exp_members.screen_name LIKE '%".$vars['selected']['keyword']."%' OR exp_members.email LIKE '%".$vars['selected']['keyword']."%'");
                    break;
            }
        }
        $this->EE->db->stop_cache();
        
        $this->EE->db->select('COUNT(DISTINCT exp_members.member_id) AS cnt');
        $this->EE->db->from('members');
        $this->EE->db->join('category_members', 'exp_members.member_id=exp_category_members.member_id', 'left');
        $query = $this->EE->db->get();
        
        $vars['total_count'] = $query->row('cnt');
        
        $this->EE->db->distinct();
        $this->EE->db->select('exp_members.member_id, username, screen_name, email, group_title');
        $this->EE->db->from('members');
        $this->EE->db->join('member_groups', 'exp_members.group_id=exp_member_groups.group_id AND exp_member_groups.site_id='.$site_id, 'left');
        if ($vars['selected']['category_id']!='')
        {
            $this->EE->db->join('category_members', 'exp_category_members.member_id=exp_members.member_id', 'left');
        }
        $this->EE->db->order_by('screen_name', 'asc');
        $this->EE->db->limit($this->perpage, $vars['selected']['rownum']);

        $query = $this->EE->db->get();
        
        $this->EE->db->flush_cache();
        
        $i = 0;
        $vars['table_headings'] = array(
                        $this->EE->lang->line('username'),
                        $this->EE->lang->line('screen_name'),
                        $this->EE->lang->line('email'),
                        $this->EE->lang->line('member_group'),
                        $this->EE->lang->line('category'),
                        ''
                    );
      
        foreach ($query->result() as $obj)
        {
           $vars['members'][$i]['username'] = "<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$obj->member_id."\">".$obj->username."</a>";
           $vars['members'][$i]['screen_name'] = $obj->screen_name;
           $vars['members'][$i]['email'] = "<a href=\"mailto:".$obj->email."\">".$obj->email."</a>";
           $vars['members'][$i]['member_group'] = $obj->group_title; 
           
            $this->EE->db->select('cat_name');
            $this->EE->db->from('category_members');
            $this->EE->db->join('categories', 'exp_category_members.cat_id=exp_categories.cat_id', 'left');
            $this->EE->db->where('member_id', $obj->member_id);
            $q = $this->EE->db->get();
            if ($q->num_rows()==0)
            {
                $vars['members'][$i]['categories'] = '-';
            }
            else
            {
                $vars['members'][$i]['categories'] = '';
                foreach ($q->result_array() as $row)
                {
                    $vars['members'][$i]['categories'] .= $row['cat_name'] . ", ";
                }
                $vars['members'][$i]['categories'] = trim($vars['members'][$i]['categories'], ", ");
            }    

           $vars['members'][$i]['edit'] = "<a href=\"".BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=edit'.AMP.'member_id='.$obj->member_id."\">".$this->EE->lang->line('edit')."</a>";
           $i++;
        }
        
        if ($vars['selected']['keyword']!='') $p_config['base_url'] .= AMP.'keyword='.$vars['selected']['keyword'];
        if ($vars['selected']['group_id']!='') $p_config['base_url'] .= AMP.'group_id='.$vars['selected']['group_id'];
        if ($vars['selected']['category_id']!='') $p_config['base_url'] .= AMP.'category_id='.$vars['selected']['category_id'];
        if ($vars['selected']['filter_by']!='') $p_config['base_url'] .= AMP.'filter_by='.$vars['selected']['filter_by'];
        
        $p_config['total_rows'] = $vars['total_count'];
		$p_config['per_page'] = $this->perpage;
		$p_config['page_query_string'] = TRUE;
		$p_config['query_string_segment'] = 'rownum';
		$p_config['full_tag_open'] = '<p id="paginationLinks">';
		$p_config['full_tag_close'] = '</p>';
		$p_config['prev_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="&lt;" />';
		$p_config['next_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt="&gt;" />';
		$p_config['first_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="&lt; &lt;" />';
		$p_config['last_link'] = '<img src="'.$this->EE->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="&gt; &gt;" />';
        

		$this->EE->pagination->initialize($p_config);
        
		$vars['pagination'] = $this->EE->pagination->create_links();
        
        $this->EE->cp->set_variable('cp_page_title', lang('member_categories_module_name'));
        
    	return $this->EE->load->view('index', $vars, TRUE);
	
    }
    
   
    
    

    function edit()
    {
    	$site_id = $this->EE->config->item('site_id');
        if (empty($this->settings[$site_id]['category_groups']))
        {
            show_error($this->EE->lang->line('provide_settings'));
        }
        
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');  
        $this->EE->load->library('api');
 
        $this->EE->db->select('exp_members.member_id, username, screen_name, email, group_title');
        $this->EE->db->from('members');
        $this->EE->db->join('member_groups', 'exp_members.group_id=exp_member_groups.group_id', 'left');
        $this->EE->db->where('member_id', $this->EE->input->get('member_id'));
        $this->EE->db->where('exp_member_groups.site_id', $site_id);
        $query = $this->EE->db->get();
        
        $this->EE->db->select('cat_id');
        $this->EE->db->from('category_members');
        $this->EE->db->where('member_id', $this->EE->input->get('member_id'));
        $cat_query = $this->EE->db->get();
        $selected = array();
        foreach ($cat_query->result_array() as $row)
        {
            $selected[] = $row['cat_id'];
        }
        
        $this->EE->api->instantiate('channel_categories');

		$categories	= array();		
			
		$category_tree = $this->EE->api_channel_categories->category_tree($this->settings[$site_id]['category_groups'], $selected);
        $prev_level = 1;
        $i = 0;
        if (!empty($category_tree))
        {
            foreach ($category_tree as $category_array)
            {
                $i++;
                if (!isset ($categories[$category_array[0]]))
				{
					$categories[$category_array[0]] = new stdClass();
				}
				$categories[$category_array[0]]->name = $category_array[1];
                $categories[$category_array[0]]->selected = $category_array[4];
                $level = $category_array[5];
                $categories[$category_array[0]]->start_tag = 0;
                $categories[$category_array[0]]->end_tag = 0;
                $categories[$category_array[0]]->last_tag = 0;
                if ($prev_level < $level)
                {
                    $categories[$category_array[0]]->start_tag = 1;
                }
                else if ($prev_level > $level)
                {
                    $categories[$category_array[0]]->end_tag = $prev_level - $level;
                }
                $categories[$category_array[0]]->level = $level;
                $prev_level = $level;
                if ($i == count($category_tree))
                {
                    $categories[$category_array[0]]->last_tag = $level;
                }
            }
        }
        //var_dump($cat_query->result_array());
        $cats['categories'] = $categories;
        $categories_checkboxes = $this->EE->load->view('_categories', $cats, TRUE);

        $vars['data'] = array(	
            'username'	=> form_hidden('member_id', $query->row('member_id'))."<a href=\"".BASE.AMP.'C=myaccount'.AMP.'id='.$query->row('member_id')."\">".$query->row('username')."</a>",
            'screen_name'	=> $query->row('screen_name'),
            'email'	=> "<a href=\"mailto:".$query->row('email')."\">".$query->row('email')."</a>",
            'member_group'	=> $query->row('group_title'),
            'categories'	=> $categories_checkboxes
    		);

        $this->EE->cp->set_variable('cp_page_title', lang('member_categories_module_name'));
        
    	return $this->EE->load->view('edit', $vars, TRUE);
	
    }        
    
    
    function save()
    {
        //does member exist?
        $this->EE->db->select('member_id');
        $this->EE->db->from('members');
        $this->EE->db->where('member_id', $this->EE->input->get_post('member_id'));
        $query = $this->EE->db->get();
        if ($query->num_rows()==0)
        {
            $this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('member_not_exist'));
        }
        else
        {
            //get all categories for this member
            $this->EE->db->select('exp_category_members.cat_id');
            $this->EE->db->from('category_members');
            $this->EE->db->join('categories', 'exp_category_members.cat_id=exp_categories.cat_id', 'left');
            $this->EE->db->where('member_id', $this->EE->input->get_post('member_id'));
            $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
            $query = $this->EE->db->get();
            $delete = array();
            foreach ($query->result() as $obj)
            {
                //mark records for deleting
                $delete[] = $obj->cat_id;
            }
        }
        
        if (!empty($delete))
        {
            $this->EE->db->where('member_id', $this->EE->input->get_post('member_id'));
            $this->EE->db->where_in('cat_id', $delete);
            $this->EE->db->delete('category_members');
        }
        
        $data = array();
        
        if (!empty($_POST['category']))
        {

            foreach ($_POST['category'] as $i=>$category)
            {
                $data[] = array(
                            'cat_id' => $category,
                            'member_id' => $this->EE->input->get_post('member_id')
                        );
                
                if ($this->settings[$this->EE->config->item('site_id')]['assign_parent'] == 'y')
                {
                    do 
                    {
                        $this->EE->db->select('parent_id');
                        $this->EE->db->from('categories');
                        $this->EE->db->where('cat_id', $category);
                        $q = $this->EE->db->get();
                        if ($q->num_rows()==0)
                        {
                            continue;
                        }
                        $category = $q->row('parent_id');
                        if ($category!=0)
                        {
                            $data[] = array(
                                'cat_id' => $category,
                                'member_id' => $this->EE->input->get_post('member_id')
                            );
                        }
                    }
                    while ($category!=0);
                }
    
            }
    
            $data = array_intersect_key($data, array_unique(array_map('serialize', $data)));
            
            $this->EE->db->insert_batch('category_members', $data);
            
        }
        
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('categories_assigned'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=index');
    }
    
    
    function settings()
    {
    	$site_id = $this->EE->config->item('site_id');
        
        $this->EE->load->helper('form');
    	$this->EE->load->library('table');

        
        $yesno = array(
                        'y' => $this->EE->lang->line('yes'),
                        'n' => $this->EE->lang->line('no')
                    );
        
        $this->EE->db->select('group_id, group_name');
        $this->EE->db->from('category_groups');
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $query = $this->EE->db->get();
        $category_groups = array();
        if ($query->num_rows() > 0)
        {
            foreach ($query->result() as $obj)
            {
                $category_groups[$obj->group_id] = $obj->group_name;
            }
        }
 
        $vars['settings'] = array(	
            'assign_parent'	=> form_dropdown('assign_parent', $yesno, $this->settings[$site_id]['assign_parent']),
            'category_groups'	=> form_multiselect('category_groups[]', $category_groups, $this->settings[$site_id]['category_groups'])
    		);
    	
        $this->EE->cp->set_variable('cp_page_title', lang('member_categories_module_name'));
        
    	return $this->EE->load->view('settings', $vars, TRUE);
	
    }    
    
    function save_settings()
    {
        $current_site_id = $this->EE->config->item('site_id');
        
        $settings = array();
        $this->EE->db->select('site_id');
        $q = $this->EE->db->get('sites');
        foreach ($q->result() as $obj)
        {
            if ($obj->site_id != $current_site_id)
            {
                $settings[$obj->site_id] = $this->settings[$obj->site_id];
            }
            else
            {
                $settings[$obj->site_id]['assign_parent'] = $this->EE->input->get_post('assign_parent');
                $settings[$obj->site_id]['category_groups'] = $_POST['category_groups'];
            }
        }
        
        $this->EE->db->where('module_name', 'Member_categories');
        $this->EE->db->update('modules', array('settings' => serialize($settings)));
        
        $this->EE->session->set_flashdata('message_success', $this->EE->lang->line('settings_updated'));
        
        $this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=member_categories'.AMP.'method=index');
    }


}
/* END */
?>