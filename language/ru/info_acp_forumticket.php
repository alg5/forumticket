<?php
/** 
*
* forumticket [Russian]
*
* @package forumticket
* @copyright (c) 2014 alg
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'ACP_FORUMTICKET'		                                => 'Форум для тикетов',
	'ACP_FORUMTICKET_SETTINGS_EXPLAIN'		=>'Данный форум будет использоваться, для тем-тикетов. <br />Каждая тема будет видна только автору темы и управляющей группе<br />По умолчанию управляющая группа - Администраторы',
	'ACP_FORUMTICKET_GROUP_APPROVAL'		=> 'Управляющая группа',
	'SORRY_AUTH_READ_TICKET'                        => 'Вы не авторизованы для чтения этого тикета.',
));
