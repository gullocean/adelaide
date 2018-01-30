<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD.'subscriber/config.php';

/**
 * 
 */
class Subscriber_forms_model extends CI_Model
{
	private $_fields = array(
		'id',
		'form_name',
		'provider',
		'api_key',
		'list_id',
		'method',
		'name_field',
		'first_name_field',
		'last_name_field',
		'email_field',
		'switch_field',
		'switch_value',
		'custom_fields'
	);
	
	/**
	 * 
	 * @param array $parameters An associative array of database columns and their data
	 * @param integer $limit The number of items to show, optional
	 * @param integer $offset The row number to start at, optional, but required if you're using $limit
	 */
	public function get($parameters = array(), $limit = NULL, $offset = NULL, $array = FALSE)
	{
		$this->_where($parameters);
		
		if ( ! is_null($limit)) { $this->db->limit($limit, $offset);}
		
		if (isset($parameters['id']))
		{
			if ($array === TRUE)
			{
				return $this->db->get(SUBSCRIBER_DB_FORMS)->row_array();
			}
			
			return $this->db->get(SUBSCRIBER_DB_FORMS)->row();
		}
		else
		{
			if ($array === TRUE)
			{
				return $this->db->get(SUBSCRIBER_DB_FORMS)->result_array();
			}
			
			return $this->db->get(SUBSCRIBER_DB_FORMS)->result();
		}
	}
	
	/**
	 * 
	 * @param array $parameters An associative array of database columns and their data
	 */
	public function count($parameters = array())
	{
		$this->_where($parameters);
		
		return $this->db->count_all_results(SUBSCRIBER_DB_FORMS);
	}
	
	/**
	 * 
	 * @param array $parameters Associative array of database columns and their data
	 */
	public function save($parameters = array())
	{
		$this->_set($parameters);
		
		if (isset($parameters['id']) AND $this->_exists($parameters['id']) === TRUE) { 
			$this->db->where('id', $parameters['id']);
			$this->db->update(SUBSCRIBER_DB_FORMS);
			return $this->db->affected_rows();
		} else {
			$this->db->insert(SUBSCRIBER_DB_FORMS);
			return $this->db->insert_id();
		}
	}
	
	/**
	 * 
	 * @param array $parameters Associative array of database columns and their data
	 */
	public function delete($parameters = array())
	{
		$this->_where($parameters);
		
		if (isset($parameters['id'])) {
			$this->db->where('id', $parameters['id']);
		} else {
			show_error("Error (Base Model): Can't delete entry if you're not specifying an ID.");
		}
		
		$this->db->delete(SUBSCRIBER_DB_FORMS);
	}
	
	// Private Functions ======================================================
	
	/**
	 * Checks to see if a row exists in the database
	 * @param number $feed_id Feed ID to check for in the database
	 */
	private function _exists($id)
	{
		return $this->db->where('id', $id)->count_all_results(SUBSCRIBER_DB_FORMS) > 0;
	}
	
	private function _where($parameters)
	{
		foreach ($parameters as $column => $value) 
		{
			if (in_array($column, $this->_fields))
			{
				$this->db->where($column, $value);
			}
		}
	}
	
	private function _set($parameters)
	{
		foreach ($parameters as $column => $value) 
		{
			if (in_array($column, $this->_fields))
			{
				if (is_array($value))
				{
					$this->db->set($column, serialize($value));
				} 
				else 
				{
					$this->db->set($column, $value);
				}
			}
		}
	}
}

// End File subscriber_forms_model.php
// File Source /system/expressionengine/third_party/addon/models/subscriber_forms_model.php