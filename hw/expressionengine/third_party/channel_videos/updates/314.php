<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ChannelVideosUpdate_314
{

	/**
	 * Constructor
	 *
	 * @access public
	 *
	 * Calls the parent constructor
	 */
	public function __construct()
	{
		$this->EE =& get_instance();

		// Load dbforge
		$this->EE->load->dbforge();
	}

	// ********************************************************************************* //

	public function do_update()
	{
		//----------------------------------------
        // EXP_MODULES
        // The settings column, Ellislab should have put this one in long ago.
        // No need for a seperate preferences table for each module.
        //----------------------------------------
        if ($this->EE->db->field_exists('settings', 'modules') == false) {
            $this->EE->dbforge->add_column('modules', array('settings' => array('type' => 'TEXT') ) );
        }
	}

	// ********************************************************************************* //

}

/* End of file 200.php */
/* Location: ./system/expressionengine/third_party/channel_videos/updates/200.php */
