<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Plugins\Content\Formathtml\Macros;

require_once Plugin::path('content', 'formathtml') . DS . 'macros/group.php';

use Plugins\Content\Formathtml\Macro;
use Hubzero\User\Group;

/**
* Members Macro Base Class 
* Group events Macro
 */
class MemberCards extends Macro
{
	/**
	 * Allow macro in partial parsing?
	 *
	 * @var string
	 */
	public $allowPartial = true;

    /**
     * Returns description of macro, use, and accepted arguments
     *
     * @return  array
     */
    public function description()
    {
        $txt = array();
        $txt['html']  = '<p>Displays business cards of members.</p>';
        $txt['html'] .= '<p>Examples:</p>
							<ul>
                                <li><code>[[MemberCards()]]</code> - Shows all group members.</li>
								<li><code>[[MemberCards(group=anothergroup)]]</code> - Shows all group members from "anothergroup" <strong>Note: Macro can only specify one group at a time.</strong></li>
                                <li><code>[[MemberCards(role=role1;role2)]]</code> - Shows all members from the group with roles "role1" or "role2".</li>
                                <li><code>[[MemberCards(id=2;1;3)</code> - Shows group members with ids 2, 1, and 3.</li>
								<li><code>[[MemberCards(tag=tag1;tag2)]]</code> - Shows group members with "tag1" OR "tag2" in their profiles</li>
							</ul>';
        return $txt['html'];
    }

	/**
	 * Get macro args
	 * @return array of arguments
	 */
	protected function getArgs()
	{
		//get the args passed in
		return explode(',', $this->args);
	}

	/**
	 * Generate macro output
	 *
	 * @return     string
	 */
	public function render()
	{
		// Get args
		$args = $this->getArgs();

		// Parse arguments
		$this->group = $this->getGroup($args);
		$this->id = $this->getId($args);
		$this->role = $this->getRole($args);
		$this->tags = $this->getTags($args);
		$this->base = rtrim(str_replace(PATH_ROOT, '', __DIR__));

		// Array of filters
		$filters = array(
			'limit' => (count($args) == 1 && is_numeric($args[0])) ? $args[0] : 12
		);

		// Get members
		$members = $this->getGroupMembers($this->group, $filters);

		// Are we a group member
		$isMember = (in_array(\User::get('id'), $this->group->get('members'))) ? true : false;

		// Get the members plugin access for this group
		$memberAccess = \Hubzero\User\Group\Helper::getPluginAccess($this->group, 'members');

		// Make sure we can actually display for the current user
		if ($memberAccess == 'anyone'
			|| ($memberAccess == 'registered' && !User::isGuest())
			|| ($memberAccess == 'members' && $isMember))
		{
			$html = $this->renderMembers($this->group, $members);
		}
		else
		{
			$html = '';
		}

		// Return rendered events
		return $html;
	}

	/**
	 * Get group
	 * @param $args Macro Arguments
	 * @return mixed
	 */
	private function getGroup(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/group=([\w;]*)/', $arg, $matches))
			{
				// Currently limiting groups to one
				$group = trim(implode(',', array_map(array($this->_db, 'quote'), explode(';', (isset($matches[1])) ? $matches[1] : ''))), "'");
				unset($args[$k]);
				
				// Return the group object
				$group = Group::getInstance($group);
				return $group;
			}
		}
		
		// If group not defined, default to current group macro is being called in
		$cn = Request::getString('cn');
		$default = Group::getInstance($cn);
		return $default;
	}

	/**
	 * Get id
	 *
	 * @param  	$args Macro Arguments
	 * @return 	mixed
	 */
	private function getId(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/id=([\w;]*)/', $arg, $matches))
			{
				$id = array_map('intval', explode(';', (isset($matches[1])) ? $matches[1] : ''));
				unset($args[$k]);
				return $id;
			}
		}

		return false;
    }

    /**
	 * Get role
	 *
	 * @param  	$args Macro Arguments
	 * @return 	mixed
	 */
	private function getRole(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/role=([\w;]*)/', $arg, $matches))
			{
				$role = explode(';', (isset($matches[1])) ? $matches[1] : '');
				unset($args[$k]);
				return $role;
			}
		}

		return false;
	}
	
	/**
	 * Search group roles
	 * 
	 * Borrowed from User/Group/Helper::search_roles due to bug
	 *
	 * @param   object  $group
	 * @param   string  $role
	 * @return  array
	 */
	public static function search_roles($group, $role = '')
	{
		if ($role == '')
		{
			return false;
		}

		$db =  \App::get('db');

		$query = "SELECT uidNumber FROM `#__xgroups_roles` as r, `#__xgroups_member_roles` as m WHERE r.name='" . $role . "' AND r.id=m.roleid AND r.gidNumber='" . $group->gidNumber . "'";

		$db->setQuery($query);

		$result = $db->loadColumn();

		$result = array_intersect($result, $group->members);

		if (count($result) > 0)
		{
			return $result;
		}
	}

	/**
	 * Get members by tag (uses OR for multiple tags)
	 *
	 * @param  	$args Macro Arguments
	 * @return 	mixed
	 */
	private function getTags(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/tag=([\w;*\s]*)/', $arg, $matches))
			{
				$tags = explode(';', (isset($matches[1])) ? $matches[1] : '');
				unset($args[$k]);
				return $tags;
			}
		}

		return false;
	}



	
	/**
	 * Search member tags
	 * 
	 * Borrowed from User/Group/Helper::search_roles due to bug
	 *
	 * @param   object  $group
	 * @param   string  $tag
	 * @return  array
	 */
	public static function search_tags($group, $tag = '')
	{
		if ($tag == '')
		{
			return false;
		}

		$db =  \App::get('db');

		$query = "SELECT uidNumber
			FROM `#__xgroups_members` as m
			INNER JOIN `#__tags_object` as o ON o.taggerid = m.uidNumber
			INNER JOIN `#__tags` as t ON t.id = o.tagid
			WHERE t.raw_tag ='" . $tag . "' AND o.tbl = 'xprofiles' AND m.gidNumber ='" .  $group->gidNumber . "'";
		$db->setQuery($query);

		$result = $db->loadColumn();

		$result = array_intersect($result, $group->members);

		if (count($result) > 0)
		{
			return $result;
		}
	}

	/**
	 * Get a list of events for a group
	 *
	 * @param      object $group
	 * @return     array
	 */
	private function getGroupMembers($group, $filters)
	{
		// Get members from group
		$members = $group->get('members');

		// Get group params
		$params = \Component::params("com_groups");
		$displaySystemUsers = $params->get('display_system_users', 'no');

		// Get this groups params
		$gparams = new \Hubzero\Config\Registry($group->get('params'));
		$displaySystemUsers = $gparams->get('display_system_users', $displaySystemUsers);

		// Filter is system users
		if ($displaySystemUsers == 'no')
		{
			$members = array_map(function($userid) {
				return ($userid < 1000) ? null : $userid;
			}, $members);
			$members = array_values(array_filter($members));
		}

		// Subset by roles
		if ($this->role) {
			$members_with_roles = array_unique( // User could have multiple roles
				call_user_func_array('array_merge',
					array_map(function($role) use ($group) {
						$ids = $this->search_roles($group, $role);
						return ($ids ? $ids : array());
					}, $this->role)
				)
			);
			// System users have been filtered out so need to intersect
			$members = array_intersect($members, $members_with_roles);
		}

		// Subset by tags
		if ($this->tags) {
			$members_with_tags = array_unique( // User could have multiple tags
				call_user_func_array('array_merge',
					array_map(function($tags) use ($group) {
						$ids = $this->search_tags($group, $tags);
						return ($ids ? $ids : array());
					}, $this->tags)
				)
			);
			// System users have been filtered out so need to intersect
			$members = array_intersect($members, $members_with_tags);
		}

		// Subset by id
		if ($this->id) {
			$members_with_ids = array_intersect($members, $this->id);
			// If role specified too, need union, otherwise want subset
			$members = ($this->role ? array_unique(array_merge($members, $members_with_ids)) : $members_with_ids);
			// If tags specified too, need union, otherwise want subset - not sure if needed
			$members = ($this->tags ? array_unique(array_merge($members, $members_with_ids)) : $members_with_ids);
		}

		// Limit members based on the filter
		$members = array_slice($members, 0, $filters['limit']);

		// Return members
		return $members;
	}

	/**
	 * Render the events
	 *
	 * @param   array  Array of group events
	 * @param   array  $members  Array of members
	 * @return  string
	 */
	private function renderMembers($group, $members)
	{
		$html = '<div class="member_browser">';
		if (count($members) > 0)
		{
			\Document::addStyleSheet($this->base . DS . 'assets' . DS . 'members' . DS . 'css' . DS . 'members.css');
			\Document::addScript($this->base . DS . 'assets' . DS . 'members' . DS . 'js' . DS . 'members.js');

			require_once Component::path('com_members') . DS . 'models' . DS . 'member.php';
			$profiles = \Components\Members\Models\Member::all()
				->including('profiles')
				->whereIn('id', $members)
				->order('surname', 'asc')
				->rows();
			
			foreach ($profiles as $profile)
			{
				$html .=    '<div class="member-card">';
				$html .=        '<div class="member-card-upper">';
				$html .=            '<div class="member-img">';
				$html .=                '<img src="' . $profile->picture(0, false) . '" alt="' . stripslashes($profile->get('name')) . '">';
				$html .=            '</div>';
				$html .=            '<div class="member-badges">';
				$html .=            '</div>';
				$html .=			'<div class="member-name">';
				$html .=                '<h2><a href="' . Route::url($profile->link()) . '">' . $profile->get('name') . '</a></h2>';
				$html .=                '<p>' . $profile->get('organization') . '</p>';
				$html .=			'</div>';
				$html .=        '</div>';
				$html .=        '<div class="member-card-lower">';
				
				//Check if bio needs to be truncated
				if (strlen($profile->get('bio')) > 75 || !empty($profile->get('tags')))
				{
				$html .=			'<button class="show-more icon-plus" aria-expanded="false">';
				$html .=				'<span class=" not-visible">Extended ' . stripslashes($profile->get('name')) . '&#39;s bio</span></button>';
				$html .=			'</button>';
				$html .=            '<div class="member-bio is-truncated">' . $profile->get('bio');
				//Check if user has tags
				if ($profile->get('tags') !== null)
				{
				$html .= 				'<div class="member-tags">';
				$html .=					'<img src="/core/assets/icons/tags.svg" aria-label="' . stripslashes($profile->get('name')) . '&#39;s' . '" tags">';
				$html .= 					$profile->get('tags');
				$html .=				'</div>';
				}
				$html .=			'</div>';
				} else {
				$html .=            '<div class="member-bio">' . $profile->get('bio') . '</div>'; 
				}
				
				$html .=            '<div class="member-links">';
				$html .=                '<a href="' . Route::url($profile->link()) . '" class="member-profile">';
				$html .=                    '<img src="core/assets/icons/user.svg" alt="' . stripslashes($profile->get('name')) . '&#39;s Profile" aria-hidden="true">';
				$html .=                    'Profile';
				$html .=                '</a>';

				//Check if user has a website
					if ($profile->get('url') !== null)
					{
				$html .=                '<a href="' . (!empty(parse_url($profile->get('url'))['scheme']) ? $profile->get('url') : 'http://' . ltrim($profile->get('url'), '/')) . '" class="member-website">';
				$html .=                    '<img src="core/assets/icons/earth.svg" alt="' . stripslashes($profile->get('name')) . '&#39;s Website" aria-hidden="true">';
				$html .=                    'Website';
				$html .=                '</a>';
								}
				$html .=            '</div>';
				$html .=        '</div>';
				$html .=    '</div>';
			}
		}
		$html .= '</div><!-- /.member_browser -->';

		return $html;
	}
}
