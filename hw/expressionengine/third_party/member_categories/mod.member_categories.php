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
 File: mod.member_categories.php
-----------------------------------------------------
 Purpose: Assign categories to site members
=====================================================
*/


if ( ! defined('BASEPATH'))
{
    exit('Invalid file request');
}


class Member_categories {

    var $return_data	= ''; 						// Bah!
    
    var $settings 		= array();    
    

    /** ----------------------------------------
    /**  Constructor
    /** ----------------------------------------*/

    function __construct()
    {        
    	$this->EE =& get_instance(); 

        $this->EE->lang->loadfile('member_categories');  
        
        $query = $this->EE->db->query("SELECT settings FROM exp_modules WHERE module_name='Member_categories' LIMIT 1");
        $this->settings = unserialize($query->row('settings'));      
    }
    /* END */
    
    function members($category_id='')
    {
        if ($category_id=='')
        {
            if ($this->EE->TMPL->fetch_param('category_id')!='')
            {
                $category_id = $this->EE->TMPL->fetch_param('category_id');
            }
            else if ($this->EE->TMPL->fetch_param('category_url_title')!='')
            {
                $this->EE->db->select('cat_id');
                $this->EE->db->where('cat_url_title', $this->EE->TMPL->fetch_param('category_url_title'));
                $q = $this->EE->db->get('categories');
                if ($q->num_rows()==0)
                {
                    if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                    {
                        return;
                    }
                    return $this->EE->output->show_user_error('general', array($this->EE->lang->line('category_does_not_exist')));
                }
                $category_id = $q->row('cat_id');
            }
            else
            {
                if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                {
                    return;
                }
                return $this->EE->output->show_user_error('general', array($this->EE->lang->line('no_category_provided')));
            }
        }
        
        $start = 0;
        $paginate = ($this->EE->TMPL->fetch_param('paginate')=='top')?'top':(($this->EE->TMPL->fetch_param('paginate')=='both')?'both':'bottom');
        if ($this->EE->TMPL->fetch_param('limit')!='')
        {
            $this->EE->db->select('COUNT(*) AS cnt');
            $this->EE->db->from('exp_category_members');
            $this->EE->db->where('cat_id', $category_id);
            $q = $this->EE->db->get();
            $total_results = $q->row('cnt');
            $limit = intval($this->EE->TMPL->fetch_param('limit'));
            
            $basepath = $this->EE->functions->create_url($this->EE->uri->uri_string);
            $query_string = ($this->EE->uri->page_query_string != '') ? $this->EE->uri->page_query_string : $this->EE->uri->query_string;

			if (preg_match("#^P(\d+)|/P(\d+)#", $query_string, $match))
			{
				$start = (isset($match[2])) ? $match[2] : $match[1];
				$basepath = $this->EE->functions->remove_double_slashes(str_replace($match[0], '', $basepath));
			}

        }
        
        if ($this->EE->TMPL->fetch_param('custom_fields')=='yes')
        {
            $custom_fields = array();
            $this->EE->db->select('m_field_id, m_field_name');
            $q = $this->EE->db->get('member_fields');
            foreach ($q->result() as $obj)
            {
                $custom_fields[$obj->m_field_id] = $obj->m_field_name;
            }
        }
        
        $what = 'exp_members.*';
        if ($this->EE->TMPL->fetch_param('custom_fields')=='yes') 
        {
            $what .= ', exp_member_data.*';
        }
        $this->EE->db->select($what);
        $this->EE->db->from('exp_category_members');
        $this->EE->db->join('exp_members', 'exp_category_members.member_id=exp_members.member_id', 'right');
        if ($this->EE->TMPL->fetch_param('custom_fields')=='yes') 
        {
            $this->EE->db->join('exp_member_data', 'exp_members.member_id=exp_member_data.member_id', 'left');
        }
        $this->EE->db->where('cat_id', $category_id);
        $sort = ($this->EE->TMPL->fetch_param('sort')=='desc')?'desc':'asc';
        $order = ($this->EE->TMPL->fetch_param('order_by')!='')?$this->EE->TMPL->fetch_param('order_by'):'member_id';
        $this->EE->db->order_by('exp_members.'.$order, $sort);
        if ($this->EE->TMPL->fetch_param('limit')!='')
        {
            $this->EE->db->limit($limit, $start);
        }
        $q = $this->EE->db->get();
        if ($q->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        if ($this->EE->TMPL->fetch_param('limit')=='')
        {
            $total_results = $q->num_rows();
        }
        
        $tagdata_orig = $this->EE->TMPL->swap_var_single('total_results', $total_results, $this->EE->TMPL->tagdata);
        $paginate_tagdata = '';
        
        if ( preg_match_all("/".LD."paginate".RD."(.*?)".LD."\/paginate".RD."/s", $tagdata_orig, $tmp)!=0)
        {
            $paginate_tagdata = $tmp[1][0];
            $tagdata_orig = str_replace($tmp[0][0], '', $tagdata_orig);
        }
        
        $out = '';
        $i = 0;
        
        foreach ($q->result_array() as $row)
        {
            $i++;
            $tagdata = $tagdata_orig;
            $tagdata = $this->EE->TMPL->swap_var_single('count', $i, $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('absolute_count', $i+$start, $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('member_id', $row['member_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('group_id', $row['group_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('username', $row['username'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('screen_name', $row['screen_name'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('email', $row['email'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('url', $row['url'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('location', $row['location'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('occupation', $row['occupation'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('interests', $row['interests'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('aol_im', $row['aol_im'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('yahoo_im', $row['yahoo_im'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('msn_im', $row['msn_im'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('icq', $row['icq'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('bio', $row['bio'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('signature', $row['signature'], $tagdata);
            
            if ($this->EE->session->userdata('display_avatars') == 'n' || $row['avatar_filename'] == '')
			{			
				$tagdata = $this->EE->TMPL->swap_var_single('avatar_url', '', $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_width', '', $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = $this->EE->TMPL->swap_var_single('avatar_url', $this->EE->config->item('avatar_url').$row['avatar_filename'], $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_width', $q->row('avatar_width'), $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('avatar_image_height', $q->row('avatar_height'), $tagdata);						
			}
            if ($this->EE->session->userdata('display_photos') == 'n' || $row['photo_filename'] == '')
			{			
				$tagdata = $this->EE->TMPL->swap_var_single('photo_url', '', $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('photo_image_width', '', $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('photo_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = $this->EE->TMPL->swap_var_single('photo_url', $this->EE->config->item('photo_url').$row['photo_filename'], $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('photo_image_width', $q->row('photo_width'), $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('photo_image_height', $q->row('photo_height'), $tagdata);						
			}
            if ($this->EE->session->userdata('display_signatures') == 'n' || $row['sig_img_filename'] == '')
			{			
				$tagdata = $this->EE->TMPL->swap_var_single('signature_image_url', '', $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('signature_image_width', '', $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('signature_image_height', '', $tagdata);
			}
			else
			{
				$tagdata = $this->EE->TMPL->swap_var_single('signature_image_url', $this->EE->config->item('sig_img_url').$row['sig_img_filename'], $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('signature_image_width', $q->row('sig_img_width'), $tagdata);
				$tagdata = $this->EE->TMPL->swap_var_single('signature_image_height', $q->row('sig_img_height'), $tagdata);						
			}
            
            $birthday = '';
			if ($row['bday_m'] != '' AND $row['bday_m'] != 0)
			{
				$month = (strlen($row['bday_m']) == 1) ? '0'.$row['bday_m'] : $row['bday_m'];

				$m = $this->EE->localize->localize_month($month);

				$birthday .= $this->EE->lang->line($m['1']);

				if ($row['bday_d'] != '' AND $row['bday_d'] != 0)
				{
					$birthday .= ' '.$row['bday_d'];
				}
			}
			if ($row['bday_y'] != '' AND $row['bday_y'] != 0)
			{
				if ($birthday != '')
				{
					$birthday .= ', ';
				}

				$birthday .=$row['bday_y'];
			}
			$this->EE->TMPL->tagdata = $this->EE->TMPL->swap_var_single('birthday', $birthday, $tagdata);
            
            $tagdata = $this->EE->TMPL->swap_var_single('ip_address', $row['ip_address'], $tagdata);
            
            if (preg_match_all("#".LD."join_date format=[\"|'](.+?)[\"|']".RD."#", $tagdata, $matches))
    		{
    			foreach ($matches['1'] as $match)
    			{
    				$this->EE->TMPL->tagdata = preg_replace("#".LD."join_date format=.+?".RD."#", $this->EE->localize->decode_date($match, $row['join_date']), $tagdata, true);
    			}
    		}
            if (preg_match_all("#".LD."last_visit format=[\"|'](.+?)[\"|']".RD."#", $tagdata, $matches))
    		{
    			foreach ($matches['1'] as $match)
    			{
    				$this->EE->TMPL->tagdata = preg_replace("#".LD."last_visit format=.+?".RD."#", $this->EE->localize->decode_date($match, $row['last_visit']), $tagdata, true);
    			}
    		}
            if (preg_match_all("#".LD."last_activity format=[\"|'](.+?)[\"|']".RD."#", $tagdata, $matches))
    		{
    			foreach ($matches['1'] as $match)
    			{
    				$this->EE->TMPL->tagdata = preg_replace("#".LD."last_activity format=.+?".RD."#", $this->EE->localize->decode_date($match, $row['last_activity']), $tagdata, true);
    			}
    		}
            
            
            if ($this->EE->TMPL->fetch_param('custom_fields')=='yes')
            {
                foreach ($custom_fields as $field_id=>$field_name)
                {
                    $tagdata = $this->EE->TMPL->swap_var_single($field_name, $row['m_field_id_'.$field_id], $tagdata);
                }
            } 
            
            $out .= $tagdata;
             
        }
        
        $out = trim($out);
        
        if ($this->EE->TMPL->fetch_param('backspace')!='')
        {
            $backspace = intval($this->EE->TMPL->fetch_param('backspace'));
            $out = substr($out, 0, - $backspace);
        }
        
        if ($total_results > $q->num_rows())
        {
            $this->EE->load->library('pagination');

			$config['base_url']		= $basepath;
			$config['prefix']		= 'P';
			$config['total_rows'] 	= $total_results;
			$config['per_page']		= $limit;
			$config['cur_page']		= $start;
			$config['first_link'] 	= $this->EE->lang->line('pag_first_link');
			$config['last_link'] 	= $this->EE->lang->line('pag_last_link');

			$this->EE->pagination->initialize($config);
			$pagination_links = $this->EE->pagination->create_links();	
            $paginate_tagdata = $this->EE->TMPL->swap_var_single('pagination_links', $pagination_links, $paginate_tagdata);			
        }
        else
        {
            $paginate_tagdata = $this->EE->TMPL->swap_var_single('pagination_links', '', $paginate_tagdata);		
        }
        
        switch ($paginate)
        {
            case 'top':
                $out = $paginate_tagdata.$out;
                break;
            case 'both':
                $out = $paginate_tagdata.$out.$paginate_tagdata;
                break;
            case 'bottom':
            default:
                $out = $out.$paginate_tagdata;
        }
        
        return $out;
    }
    
    function categories($member_id='')
    {
        if ($member_id=='')
        {
            if ($this->EE->TMPL->fetch_param('member_id')!='')
            {
                if ($this->EE->TMPL->fetch_param('member_id')=='{member_id}' || $this->EE->TMPL->fetch_param('member_id')=='{logged_in_member_id}')
                {
                    $member_id = $this->EE->session->userdata('member_id');
                }
                else
                {
                    $member_id = $this->EE->TMPL->fetch_param('member_id');
                }
            }
            else if ($this->EE->TMPL->fetch_param('username')!='')
            {
                $this->EE->db->select('member_id');
                $this->EE->db->where('username', $this->EE->TMPL->fetch_param('username'));
                $q = $this->EE->db->get('members');
                if ($q->num_rows()==0)
                {
                    if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                    {
                        return;
                    }
                    return $this->EE->output->show_user_error('general', array($this->EE->lang->line('member_does_not_exist')));
                }
                $member_id = $q->row('member_id');
            }
            else if ($this->EE->session->userdata('member_id')!=0)
            {
                $member_id = $this->EE->session->userdata('member_id');
            }
            else
            {
                if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                {
                    return;
                }
                return $this->EE->output->show_user_error('general', array($this->EE->lang->line('no_member_provided')));
            }
        }
        
        if ($this->EE->TMPL->fetch_param('custom_fields')=='yes')
        {
            $custom_fields = array();
            $this->EE->db->select('field_id, field_name');
            $q = $this->EE->db->get('category_fields');
            foreach ($q->result() as $obj)
            {
                $custom_fields[$obj->field_id] = $obj->field_name;
            }
        }
        
        $what = 'exp_categories.cat_id, exp_categories.cat_name, exp_categories.cat_url_title, exp_categories.cat_image, exp_categories.cat_description, exp_categories.parent_id, exp_categories.group_id';
        if ($this->EE->TMPL->fetch_param('custom_fields')=='yes') 
        {
            $what .= ', exp_category_field_data.*';
        }
        $this->EE->db->select($what);
        $this->EE->db->from('exp_category_members');
        $this->EE->db->join('exp_categories', 'exp_category_members.cat_id=exp_categories.cat_id', 'right');
        if ($this->EE->TMPL->fetch_param('custom_fields')=='yes') 
        {
            $this->EE->db->join('exp_category_field_data', 'exp_categories.cat_id=exp_category_field_data.cat_id', 'left');
        }
        $this->EE->db->where('member_id', $member_id);
        if ($this->EE->TMPL->fetch_param('category_group')!='') 
        {
            $category_group_a = explode('|', $this->EE->TMPL->fetch_param('category_group'));
			$this->EE->db->where_in('exp_categories.group_id', $category_group_a);
        }
        $sort = ($this->EE->TMPL->fetch_param('sort')=='desc')?'desc':'asc';
        $order = ($this->EE->TMPL->fetch_param('order_by')=='order')?'cat_order':'cat_name';
        $this->EE->db->order_by('exp_categories.group_id, exp_categories.parent_id, exp_categories.'.$order, $sort);
        $q = $this->EE->db->get();
        if ($q->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
        
        $tagdata_orig = $this->EE->TMPL->swap_var_single('total_results', $q->num_rows(), $this->EE->TMPL->tagdata);
        $out = '';
        $i = 0;
        
        foreach ($q->result_array() as $row)
        {
            $i++;
            $tagdata = $tagdata_orig;
            $tagdata = $this->EE->TMPL->swap_var_single('count', $i, $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('category_id', $row['cat_id'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('category_name', $row['cat_name'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('category_url_title', $row['cat_url_title'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('category_image', $row['cat_image'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('category_description', $row['cat_description'], $tagdata);
            $tagdata = $this->EE->TMPL->swap_var_single('parent_id', $row['parent_id'], $tagdata);    
			$tagdata = $this->EE->TMPL->swap_var_single('category_group', $row['group_id'], $tagdata);            
            
            if ($this->EE->TMPL->fetch_param('custom_fields')=='yes')
            {
                foreach ($custom_fields as $field_id=>$field_name)
                {
                    $tagdata = $this->EE->TMPL->swap_var_single($field_name, $row['field_id_'.$field_id], $tagdata);
                }
            } 
            
            $out .= $tagdata;
        }
        
        $out = trim($out);
        
        if ($this->EE->TMPL->fetch_param('backspace')!='')
        {
            $backspace = intval($this->EE->TMPL->fetch_param('backspace'));
            $out = substr($out, 0, - $backspace);
        }
        return $out;
    }
    
    function check($member_id='', $category_id='')
    {

        if ($member_id=='')
        {
            if ($this->EE->TMPL->fetch_param('member_id')!='')
            {
                if ($this->EE->TMPL->fetch_param('member_id')=='{member_id}' || $this->EE->TMPL->fetch_param('member_id')=='{logged_in_member_id}')
                {
                    $member_id = $this->EE->session->userdata('member_id');
                }
                else
                {
                    $member_id = $this->EE->TMPL->fetch_param('member_id');
                }
            }
            else if ($this->EE->TMPL->fetch_param('username')!='')
            {
                $this->EE->db->select('member_id');
                $this->EE->db->where('username', $this->EE->TMPL->fetch_param('username'));
                $q = $this->EE->db->get('members');
                if ($q->num_rows()==0)
                {
                    if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                    {
                        return;
                    }
                    return $this->EE->output->show_user_error('general', array($this->EE->lang->line('member_does_not_exist')));
                }
                $member_id = $q->row('member_id');
            }
            else if ($this->EE->session->userdata('member_id')!=0)
            {
                $member_id = $this->EE->session->userdata('member_id');
            }
            else
            {
                if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                {
                    return;
                }
                return $this->EE->output->show_user_error('general', array($this->EE->lang->line('no_member_provided')));
            }
        }
        
        if ($category_id=='')
        {
            if ($this->EE->TMPL->fetch_param('category_id')!='')
            {
                $category_id = $this->EE->TMPL->fetch_param('category_id');
            }
            else if ($this->EE->TMPL->fetch_param('category_url_title')!='')
            {
                $this->EE->db->select('cat_id');
                if (strpos($this->EE->TMPL->fetch_param('category_url_title'), '|')===FALSE)
		        {
		            $this->EE->db->where('cat_url_title', $this->EE->TMPL->fetch_param('category_url_title'));
		        }
                else
                {
                	$category_url_title_a = explode("|", $this->EE->TMPL->fetch_param('category_url_title'));
					$this->EE->db->where_in('cat_url_title', $category_url_title_a);
                }
                $q = $this->EE->db->get('categories');
                if ($q->num_rows()==0)
                {
                    if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                    {
                        return;
                    }
                    return $this->EE->output->show_user_error('general', array($this->EE->lang->line('category_does_not_exist')));
                }
                else if ($q->num_rows()==1)
                {
                	$category_id = $q->row('cat_id');
               	}
               	else
               	{
               		$category_id_a = array();
		   			foreach ($q->result_array() as $row)
			        {
			            $category_id_a[] = $row['cat_id'];
			        }
               	}
            }
            else
            {
                if ($this->EE->TMPL->fetch_param('errors')=='off' || $this->EE->TMPL->fetch_param('errors')=='no')
                {
                    return;
                }
                return $this->EE->output->show_user_error('general', array($this->EE->lang->line('no_category_provided')));
            }
        }
        
        $this->EE->db->select('*');
        $this->EE->db->from('exp_category_members');
        if (strpos($category_id, '|')===FALSE && !isset($category_id_a))
        {
            $this->EE->db->where('cat_id', $category_id);
        }
        else
        {
            if (!isset($category_id_a))
            {
				$category_id_a = explode("|", $category_id);
			}
            $this->EE->db->where_in('cat_id', $category_id_a);
        }
        $this->EE->db->where('member_id', $member_id);
        $q = $this->EE->db->get();
        if ($q->num_rows()==0)
        {
            return $this->EE->TMPL->no_results();
        }
        return $this->EE->TMPL->tagdata;
    }
    
    
	
	function form()
    {
    	if ($this->EE->session->userdata('member_id')==0)
        {
        	return $this->EE->TMPL->no_results();
        }
        
        $categories_checkboxes = '';

        $this->EE->load->helper('form');
        $this->EE->load->library('api');
        $this->EE->api->instantiate('channel_categories');
        
        $this->EE->db->select('cat_id');
        $this->EE->db->from('category_members');
        $this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
        $cat_query = $this->EE->db->get();
        $selected = array();
        foreach ($cat_query->result_array() as $row)
        {
            $selected[] = $row['cat_id'];
        }
        
        foreach ($this->settings[$this->EE->config->item('site_id')]['category_groups'] as $group_id)
        {

			$categories	= array();		
				
			$category_tree = $this->EE->api_channel_categories->category_tree($group_id, $selected);
	        $prev_level = 1;
	        $i = 0;
	        foreach ($category_tree as $category_array)
	        {
	            $i++;
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
   		

	        $cats['categories'] = $categories;
	        
	        $categories_checkboxes .= $this->EE->load->view('categories_edit', $cats, TRUE);
	        
		}

        $tagdata = $this->EE->TMPL->tagdata;        
        $tagdata = $this->EE->TMPL->swap_var_single('categories', $categories_checkboxes, $tagdata);
        

        if ($this->EE->TMPL->fetch_param('return')=='')
        {
            $return = $this->EE->functions->fetch_site_index();
        }
        else if ($this->EE->TMPL->fetch_param('return')=='SAME_PAGE')
        {
            $return = $this->EE->functions->fetch_current_uri();
        }
        else if (strpos($this->EE->TMPL->fetch_param('return'), "http://")!==FALSE || strpos($this->EE->TMPL->fetch_param('return'), "https://")!==FALSE)
        {
            $return = $this->EE->TMPL->fetch_param('return');
        }
        else
        {
            $return = $this->EE->functions->create_url($this->EE->TMPL->fetch_param('return'));
        }
        
        $data['hidden_fields'] = array(
										'ACT'	=> $this->EE->functions->fetch_action_id('Member_categories', 'categories_save'),
										'RET'	=> $return
									  );            
        if ($this->EE->config->item('secure_forms') == 'y') { $data['hidden_fields']['XID']=$this->EE->functions->add_form_security_hash('{XID_HASH}'); }
        $data['id']		= ($this->EE->TMPL->fetch_param('id')!='') ? $this->EE->TMPL->fetch_param('id') : 'member_categories_form';
        $data['name']		= ($this->EE->TMPL->fetch_param('name')!='') ? $this->EE->TMPL->fetch_param('name') : 'member_categories_form';
        $data['class']		= ($this->EE->TMPL->fetch_param('class')!='') ? $this->EE->TMPL->fetch_param('class') : 'member_categories_form';

        $out = $this->EE->functions->form_declaration($data).$tagdata."\n"."</form>";
        
        return $out;
	
    }
    
    function categories_save()
    {
        $this->EE->lang->loadfile('member');
        
		if ($this->EE->session->userdata('member_id')==0)
        {
            return $this->EE->output->show_user_error('submission', $this->EE->lang->line('log_in'));		
        }
        
        //get all categories for this member
        $this->EE->db->select('exp_category_members.cat_id');
        $this->EE->db->from('category_members');
        $this->EE->db->join('categories', 'exp_category_members.cat_id=exp_categories.cat_id', 'left');
        $this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
        $this->EE->db->where('site_id', $this->EE->config->item('site_id'));
        $query = $this->EE->db->get();
        $delete = array();
        foreach ($query->result() as $obj)
        {
            //mark records for deleting
            $delete[] = $obj->cat_id;
        }
        
        if (!empty($delete))
        {
            $this->EE->db->where('member_id', $this->EE->session->userdata('member_id'));
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
                            'member_id' => $this->EE->session->userdata('member_id')
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
                                'member_id' => $this->EE->session->userdata('member_id')
                            );
                        }
                    }
                    while ($category!=0);
                }
    
            }
    
            $data = array_intersect_key($data, array_unique(array_map('serialize', $data)));
            
            $this->EE->db->insert_batch('category_members', $data);
            
        }
        
        $site_name = ($this->EE->config->item('site_name') == '') ? $this->EE->lang->line('back') : stripslashes($this->EE->config->item('site_name'));
        $return = ($_POST['RET']!='')?$_POST['RET']:$this->EE->config->item('site_url');
		
        $data = array(	'title' 	=> $this->EE->lang->line('profile_updated'),
        				'heading'	=> $this->EE->lang->line('profile_updated'),
        				'content'	=> $this->EE->lang->line('mbr_profile_has_been_updated'),
        				'redirect'	=> $return,
        				'link'		=> array($return, $site_name),
                        'rate'		=> 5
        			 );
			
		$this->EE->output->show_message($data);
    }    

}
/* END */

?>