<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Entrieslib
{
    public function __construct()
    {
        $this->EE = get_instance();
    }

    private function get_channel_fields()
    {
        if(!isset($this->EE->session->cache['entrieslib']['channel_field_map'])) {
            $map = array();
            $q = $this->EE->db->get('channel_fields');
            foreach($q->result() as $field) {
                $map['field_id_'.$field->field_id] = $field->field_name;
            }
            $this->EE->session->cache['entrieslib']['channel_field_map'] = $map;
        }

        return $this->EE->session->cache['entrieslib']['channel_field_map'];
    }


    /**
     * Get EE's database field name (ie. field_id_33) from a field name (ie. 'blog_entry')
     *
     * @param $field_name
     * @return int|string
     */
    public function get_field_db_name($field_name) {
        $fields = $this->get_channel_fields();
        foreach($fields as $field_db_name => $fname) {
            if($fname == $field_name) {
                return $field_db_name;
            }
        }

        return $field_name;
    }


    /**
     * Make field_id_1 => blog_body etc.
     *
     * @param $arr
     */
    public function remap_entry_arr($arr)
    {
        $entry = array();
        $fields = $this->get_channel_fields();
        foreach($arr as $field_name => $field_value) {

            if(isset($fields[$field_name])) {
                $entry[ $fields[$field_name] ] = $field_value;
            } else {

                if(strpos($field_name, 'field_ft_') === FALSE) {
                    $entry[ $field_name ] = $field_value;
                }
            }
        }

        return $entry;
    }

    /**
     * Return information about an entry, or FALSE if not found
     *
     * @param $entry_id
     * @param bool $url_title
     * @return bool
     */
    public function get_entry_info($entry_id, $url_title=FALSE)
    {
        $where_arr = array();
        if($entry_id) {
            $where_arr['t.entry_id'] = $entry_id;
        } else {
            $where_arr['url_title'] = $url_title;
        }

        $q = $this->EE->db->from('channel_titles t, channel_data d')->where($where_arr)->where('t.entry_id', 'd.entry_id', FALSE)->get();
        if($q->num_rows() > 0) {
            $entries = $q->result_array();
            return $this->remap_entry_arr($entries[0]);
        } else {
            return FALSE;
        }
    }
}