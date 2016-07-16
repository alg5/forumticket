<?php
/**
*
* @package forumticket
* @copyright (c) 2016 alg
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
 */

namespace alg\forumticket\event;

/**
* Event listener
*/
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	const GUESTS = 1;
	const ADMINISTRATORS = 5;
	const BOTS = 6;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string phpBB root path  */
	protected $root_path;

	/** @var string PHP file extension */
	protected $php_ext;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var string phpbb_user_group table */
	protected $user_group_table;
	/**
	* Constructor
	*
	* @param \phpbb\config\config $config
	* @param \phpbb\db\driver\driver_interface $db
	* @param \phpbb\auth\auth $auth
	* @param \phpbb\template\template $template
	* @param \phpbb\user $user
	* @param string $root_path
	* @param \phpbb\request\request $request
	* @param \phpbb\pagination $pagination
	* @param string $user_group_table
	* @access public
	*/
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\template\template $template, \phpbb\user $user, $phpbb_root_path, $php_ext, \phpbb\request\request_interface $request, \phpbb\pagination $pagination, $user_group_table)
	{
		$this->config = $config;
		$this->db = $db;
		$this->auth = $auth;
		$this->template = $template;
		$this->user = $user;
		$this->phpbb_root_path = $phpbb_root_path;
		$this->php_ext = $php_ext;
		$this->request = $request;
		$this->pagination =  $pagination;
		$this->user_group_table = $user_group_table;

		$this->forumticket = false;
		$this->group_id_approve = 0;

		$this->exclude_forum_topics_details = array();	//for forumlist

	}

	static public function getSubscribedEvents()
	{
		return array(
			//acp
			'core.acp_manage_forums_request_data'			=> 'acp_manage_forums_request_data',
			'core.acp_manage_forums_display_form'			=> 'acp_manage_forums_display_form',

			//viewtopic
			'core.viewtopic_modify_page_title'						=> 'viewtopic_modify_page_title',

			//viewforum
			'core.viewforum_get_topic_data'		=> 'viewforum_get_topic_data',
			'core.viewforum_modify_topics_data'		=> 'viewforum_modify_topics_data',

			'core.display_forums_modify_row'		=> 'display_forums_modify_row',
			'core.display_forums_modify_forum_rows'		=> 'display_forums_modify_forum_rows',

			//search
			'core.search_get_topic_data'		=> 'search_get_topic_data',
			'core.search_get_posts_data'		=> 'search_get_posts_data',
			'core.search_results_modify_search_title'		=> 'search_results_modify_search_title',

			 //feed (for future version phpbb)
			'core.feed_sql'		=> 'feed_sql',

			//livesearch  (ext)
			'alg.livesearch.sql_livesearch_topics'		=> 'sql_livesearch_topics',

			'alg.livesearch.sql_livesearch_usertopics'		=> 'sql_livesearch_usertopics',
			'alg.livesearch.modify_tpl_ary_livesearch_usertopics_matches'		=> 'modify_tpl_ary_livesearch_usertopics_matches',

			'alg.livesearch.sql_livesearch_userposts'		=> 'sql_livesearch_userposts',
			'alg.livesearch.modify_tpl_ary_livesearch_userposts_matches'		=> 'modify_tpl_ary_livesearch_userposts_matches',

			//lasttopics  (ext)
			'alg.lasttopics.sql_latest_general_topics'		=> 'sql_latest_general_topics',

			//similartopics  (ext)
			'vse.similartopics.get_topic_data'		=> 'similartopics_get_topic_data',

			//topfive  (ext)
			'rmcgirr83.topfive.sql_pull_topics_data'		=> 'sql_pull_topics_data',

			//recenttopics  (ext)
			'paybas.recenttopics.sql_pull_topics_list'		=> 'sql_pull_topics_list',

		);
	}
	#region ACP functions
	public function acp_manage_forums_request_data($event)
	{
		$forum_data = $event['forum_data'];
		$forum_id = $this->request->variable('forum_type_ticket', false);
		$group_id= $this->request->variable('hGroupId', listener::ADMINISTRATORS);
		$forum_data += array(
			'forum_type_ticket'				=>  $forum_id,
			'group_id_approve_ticket'   =>  $group_id,
		);
		$event['forum_data'] = $forum_data;
	}
	public function acp_manage_forums_display_form($event)
	{
		$template_data = $event['template_data'];
		$forum_data = $event['forum_data'];

		$forum_id_src = isset($forum_data['forum_type_ticket']) ? $forum_data['forum_type_ticket'] : 0;
		$group_id_src = isset($forum_data['group_id_approve_ticket']) ? $forum_data['group_id_approve_ticket'] : 0;

		$group_id = listener::ADMINISTRATORS;
		$group_name = $this->user->lang['ADMINISTRATORS'];

		if ($group_id_src && $group_id_src != $group_id)
		{
			$group_id = $forum_data['group_id_approve_ticket'];
			include_once($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
			$group_name = get_group_name($group_id);
		}
		$exclude_ids[] = listener::GUESTS;
		$exclude_ids[] = listener::BOTS;

		$forum_data = $event['forum_data'];
		$template_data += array(
			'S_GROUP_OPTIONS'		=> group_select_options(false, $exclude_ids, false), // Show groups
			'S_GROUP_APPROVAL_ID'		=> $group_id,
			'S_GROUP_APPROVAL_NAME'		=>$group_name,
			'S_FORUMTICKET_CHECKED'		=>(bool) $forum_id_src,
		);

		$event['template_data'] = $template_data;
	}

	#endregion

	#region viewtopic
	public function viewtopic_modify_page_title($event)
	{
		$topic_data = $event['topic_data'];
		if (!$topic_data['forum_type_ticket'] )
		{
			return;
		}
		$t_read =  $topic_data['topic_type'] != POST_NORMAL || ($topic_data['topic_poster'] == $this->user->data['user_id'] ) || group_memberships($topic_data['group_id_approve_ticket'], $this->user->data['user_id'], true);
		if (!$t_read)
		{
			$this->user->add_lang_ext('alg/forumticket', 'info_acp_forumticket');
			trigger_error('SORRY_AUTH_READ_TICKET');
		}
	}

	#endregion

	#region viewforum
	public function viewforum_get_topic_data($event)
	{
		$forum_data = $event['forum_data'];
		$this->forumticket = (int) $forum_data['forum_type_ticket'];
		$this->group_id_approve = $forum_data['group_id_approve_ticket'] ;
	}
	public function viewforum_modify_topics_data($event)
	{
		if (!$this->forumticket )
		{
			return;
		}
		include_once($this->phpbb_root_path .  'includes/functions_user.' . $this->php_ext);
		$rowset = $event['rowset'];
		$topic_list = $event['topic_list'];
		$total_topic_count = $event['total_topic_count'];

		foreach ($rowset as $key => $row)
		{
			$t_read =  $row['topic_type'] != POST_NORMAL || ($row['topic_poster'] == $this->user->data['user_id'] ) || group_memberships($this->group_id_approve, $this->user->data['user_id'], true);
			if (!$t_read)
			{
				$key_topic_list = array_search($key, $topic_list);
				unset($topic_list[$key_topic_list]);
				unset($rowset[$key]);
				$total_topic_count--;
			}

		}
		if ($total_topic_count < $event['total_topic_count'])
		{
			$start = $this->request->variable('start', 0);
			$this->template->assign_vars(array(
				'TOTAL_TOPICS'	=> $this->user->lang('VIEW_FORUM_TOPICS', (int) $total_topic_count),
				'PAGE_NUMBER'			=>  $total_topic_count == 0 ?  0 : $this->pagination->on_page($total_topic_count, $this->config['topics_per_page'], $start),
			));
		$event['total_topic_count'] =  $total_topic_count;
		$event['topic_list'] =  $topic_list;
		$event['$rowset'] =  $rowset;

		}
	}

	public function display_forums_modify_row($event)
	{
		$row = $event['row'];
		include_once($this->phpbb_root_path .  'includes/functions_user.' . $this->php_ext);
		if (isset($row['forum_type_ticket']) && $row['forum_type_ticket'] && isset($row['group_id_approve_ticket']) && ! group_memberships($row['group_id_approve_ticket'], $this->user->data['user_id'], true))
		{
			$ex_tid_ary = $this->get_topics_excluded();
			if (!sizeof($ex_tid_ary))
			{
				return;
			}
			$sql = "SELECT topic_last_post_id, topic_last_poster_id, topic_last_poster_name, topic_last_poster_colour, topic_last_post_subject, topic_last_post_time ".
						" , (select count(post_id) from phpbb_posts p where p.topic_id=t.topic_id) post_count " .
						" FROM " . TOPICS_TABLE . " t" .
						" WHERE forum_id=" . $row['forum_id'] .
						" AND topic_status <> " . ITEM_MOVED .
						" AND topic_visibility = " . ITEM_APPROVED .
						" AND " . $this->db->sql_in_set('topic_id', $ex_tid_ary, true) .
						" ORDER BY topic_last_post_time desc";
			$result = $this->db -> sql_query($sql);
			$count = 0;
			$t_ary = array();
			$topics = 0;
			$posts = 0;

			while ($trow = $this->db->sql_fetchrow($result))
			{
				if ($this->auth->acl_get('f_read', $row['forum_id']))
				{
					$t_ary[] = $trow;
					$topics++;
					$posts += $trow['post_count'];
				}
			}
			//$this->exclude_forum_topics_details
			if (!sizeof($t_ary))
			{
				$row['forum_last_post_id'] = 0;
				$row['forum_last_poster_id'] = 0;
				$row['forum_last_post_subject'] = '';
				$row['forum_last_post_time'] = 0;
				$row['forum_last_poster_name'] = '';
				$row['forum_last_poster_colour'] = '';
			}
			else
			{
				$trow = $t_ary[0];
			   $row['forum_last_post_id'] = $trow['topic_last_post_id'];
				$row['forum_last_poster_id'] = $trow['topic_last_poster_id'];
				$row['forum_last_post_subject'] = $trow['topic_last_post_subject'];
				$row['forum_last_post_time'] = $trow['topic_last_post_time'];
				$row['forum_last_poster_name'] = $trow['topic_last_poster_name'];
				$row['forum_last_poster_colour'] = $trow['topic_last_poster_colour'];
			}
			$event['row'] = $row;
			$this->exclude_forum_topics_details[$row['forum_id']] = array('topics' =>$topics, 'posts' =>$posts);
		}
	}
	public function display_forums_modify_forum_rows($event)
	{
		$parent_id = $event['parent_id'];
		$forum_rows = $event['forum_rows'];
		if (isset($this->exclude_forum_topics_details[$parent_id]))
		{
			$forum_rows[$parent_id]['forum_topics'] = $this->exclude_forum_topics_details[$parent_id]['topics'];
			$forum_rows[$parent_id]['forum_posts'] = $this->exclude_forum_topics_details[$parent_id]['posts'];
			$event['forum_rows'] = $forum_rows;
		}
	}
	#endregion

	#region search

	public function search_get_topic_data($event)
	{
		$total_match_count = $event['total_match_count'];
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			$where = $event['sql_where'];
			$where .= ' AND ' . $this->db->sql_in_set('t.topic_id', $ex_tid_ary, true);
			$event['sql_where'] = $where;
		}
		$this->total_match_count = $total_match_count - sizeof($ex_tid_ary);
	}
	public function search_get_posts_data($event)
	{
		$total_match_count = $event['total_match_count'];
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			$this->update_sql_array($ex_tid_ary, $event);
		}
		$this->total_match_count = $total_match_count - sizeof($ex_tid_ary);
	}
	public function search_results_modify_search_title($event)
	{
		$total_match_count = $event['total_match_count'];
		if (isset($this->total_match_count) && $this->total_match_count != $total_match_count)
		{
			$total_matches_limit = 1000;
			$total_match_count = $this->total_match_count;
			$found_more_search_matches = false;
			if ($total_match_count > $total_matches_limit)
			{
				$found_more_search_matches = true;
				$total_match_count = $total_matches_limit;
			}
			if ($found_more_search_matches)
			{
				$l_search_matches = $this->user->lang('FOUND_MORE_SEARCH_MATCHES', (int) $total_match_count);
			}
			else
			{
				$l_search_matches = $this->user->lang('FOUND_SEARCH_MATCHES', (int) $total_match_count);
			}
			$start = $event['start'];

			$this->template->assign_vars(array(
				'SEARCH_MATCHES'	=> $l_search_matches,
				'TOTAL_MATCHES'	=> $this->total_match_count,
				'PAGE_NUMBER'			=>  $total_match_count == 0 ?  0 : $this->pagination->on_page($total_match_count, $this->config['topics_per_page'], $start),
			));
		 $event['total_match_count'] =  $total_match_count;
		}
	}
	#endregion

	#region feed
	public function feed_sql($event)
	{
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			$sql_array = $event['sql_array'];
			$where = $sql_array['WHERE'];
			$texclude = $this->db->sql_in_set('t.topic_id', $ex_tid_ary, true);
			$where .= ' AND ' . $this->db->sql_in_set('p.topic_id', $ex_tid_ary, true);
			 $sql_array['WHERE'] = $where;
			 $event['sql_array'] = $sql_array;
		}
	}

	#endregion

	#region livesearch
	function sql_livesearch_topics($event)
	{
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			$this->update_sql_array($ex_tid_ary, $event);
		}
	}

	public function sql_livesearch_usertopics($event)
	{
		$total_match_count = $event['total_match_count'];
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			$this->update_sql_array($ex_tid_ary, $event);
		}
		$this->total_match_count = $total_match_count - sizeof($ex_tid_ary);
	}
	public function modify_tpl_ary_livesearch_usertopics_matches($event)
	{
		$total_match_count = $event['total_count'];
		if (isset($this->total_match_count) && $this->total_match_count != $total_match_count)
		{
			$start = $event['start'];
			$tpl_ary = $event['tpl_ary'];
			$total_match_count = $this->total_match_count;
			$search_matches = $tpl_ary['SEARCH_MATCHES'];
			$search_matches = $total_match_count == 0 ? '' : $this->user->lang('FOUND_SEARCH_MATCHES', $total_match_count);
			$page_number = $total_match_count == 0 ?  0 : $this->pagination->on_page($total_match_count, $this->config['topics_per_page'], $start);
			$tpl_ary['SEARCH_MATCHES'] = $search_matches;
			$tpl_ary['PAGE_NUMBER'] = $page_number;
			$event['tpl_ary'] = $tpl_ary;
			$event['total_match_count'] =  $total_match_count;
		}
	}

	public function sql_livesearch_userposts($event)
	{
		$total_match_count = $event['total_match_count'];
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			 $this->update_sql_array($ex_tid_ary, $event);
		}
		$this->total_match_count = $total_match_count - sizeof($ex_tid_ary);
	}
	public function modify_tpl_ary_livesearch_userposts_matches($event)
	{
		$total_match_count = $event['total_count'];
		if (isset($this->total_match_count) && $this->total_match_count != $total_match_count)
		{
			$start = $event['start'];
			$tpl_ary = $event['tpl_ary'];
			$total_match_count = $this->total_match_count;
			$search_matches = $tpl_ary['SEARCH_MATCHES'];
			$search_matches = $total_match_count == 0 ? '' : $this->user->lang('FOUND_SEARCH_MATCHES', $total_match_count);
			$page_number = $total_match_count == 0 ?  0 : $this->pagination->on_page($total_match_count, $this->config['topics_per_page'], $start);
			$tpl_ary['SEARCH_MATCHES'] = $search_matches;
			$tpl_ary['PAGE_NUMBER'] = $page_number;
			$event['tpl_ary'] = $tpl_ary;
			$event['total_match_count'] =  $total_match_count;
		}
	}

	#endregion

	#region lasttopics
	public function sql_latest_general_topics($event)
	{
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			 $this->update_sql_array($ex_tid_ary, $event);
		}
	}

	#endregion

	#region similartopics
	function similartopics_get_topic_data($event)
	{
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			$this->update_sql_array($ex_tid_ary, $event);
	   }
	}

	#endregion

	#region topfive
	function sql_pull_topics_data($event)
	{
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			 $this->update_sql_array($ex_tid_ary, $event);
		}
	}

	#endregion

	#region recenttopics
	function sql_pull_topics_list($event)
	{
		$ex_tid_ary = $this->get_topics_excluded();
		if (sizeof($ex_tid_ary))
		{
			 $this->update_sql_array($ex_tid_ary, $event);
		}
	}

	#endregion

	#region private functions
	private function get_topics_excluded()
	{
		$sql = "SELECT  t.topic_id FROM " . TOPICS_TABLE .
					" t join " . FORUMS_TABLE . " f on f.forum_id=t.forum_id" .
					" WHERE f.forum_type_ticket=1 " .
					" AND topic_type=" . POST_NORMAL .
					" AND " . $this->user->data['user_id'] . "<>topic_poster" .
					" AND (SELECT count(g.user_id) FROM " . $this->user_group_table. " g WHERE g.group_id= f.group_id_approve_ticket AND g.user_id=" . $this->user->data['user_id'] . ")=0";
		$result = $this->db -> sql_query($sql);
		$ex_tid_ary = array();
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ex_tid_ary[] = $row['topic_id'];
		}
		return $ex_tid_ary;
	}
	private function update_sql_array($ex_tid_ary, &$event)
	{
		$sql_array = $event['sql_array'];
		$where = $sql_array['WHERE'];
		$texclude = $this->db->sql_in_set('t.topic_id', $ex_tid_ary, true);
		$where .= ' AND ' . $this->db->sql_in_set('t.topic_id', $ex_tid_ary, true);
		$sql_array['WHERE'] = $where;
		$event['sql_array'] = $sql_array;
	}

	#endregion

}
