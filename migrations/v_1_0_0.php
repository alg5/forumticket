<?php
/**
*
* @package forumticket
* @copyright (c) alg
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

namespace alg\forumticket\migrations;

class v_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['forumticket']) && version_compare($this->config['forumticket'], '1.0.*', '>=');
	}

	static public function depends_on()
	{
			return array('\phpbb\db\migration\data\v310\dev');
	}

	public function update_schema()
	{
		return 	array(
			'add_columns' => array(
				$this->table_prefix . 'forums' => array(
					'forum_type_ticket' => array('TINT:1', '0'),
					'group_id_approve_ticket' => array('UINT:8', '0'),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(
			'drop_columns'	=> array(
					$this->table_prefix . 'forums' => array(
							'forum_type_ticket',
							'group_id_approve_ticket',
				),
			),
		);

	}

	public function update_data()
	{
		return array(
		//	// Add configs
		// Current version
			array('config.add', array('forumticket', '1.0.0')),
		//	// Add ACP modules
		//	array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_FORUMTICKET')),

		//	array('module.add', array('acp', 'ACP_FORUMTICKET', array(
		//			'module_basename'	=> '\alg\forumticket\acp\acp_forumticket_module',
		//			'module_langname'	=> 'ACP_FORUMTICKET_SETTINGS',
		//			'module_mode'		=> 'forumticket',
		//			'module_auth'		=> 'ext_alg/forumticket && acl_a_board',
		//		))),
		);
	}
	public function revert_data()
	{
		return array(
			//// remove from ACP modules
			//array('if', array(
			//	array('module.exists', array('acp', 'ACP_FORUMTICKET', array(
			//		'module_basename'	=> '\alg\forumticket\acp\acp_forumticket_module',
			//		'module_langname'	=> 'ACP_FORUMTICKET_SETTINGS',
			//		'module_mode'		=> 'forumticket',
			//		'module_auth'		=> 'ext_alg/forumticket && acl_a_board',
			//		),
			//	)),
			//	array('module.remove', array('acp', 'ACP_ADMINNOTIFICATIONS', array(
			//		'module_basename'	=> '\alg\forumticket\acp\acp_forumticket_module',
			//		'module_langname'	=> 'ACP_ADMINNOTIFICATIONS_SETTINGS',
			//		'module_mode'		=> 'forumticket',
			//		'module_auth'		=> 'ext_alg/forumticket && acl_a_board',
			//		),
			//	)),
			//)),
			// Current version
			array('config.remove', array('forumticket')),
		);
	}

}