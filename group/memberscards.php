<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright 2005-2019 HUBzero Foundation, LLC.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Plugins\Content\Formathtml\Macros\Group;

require_once Plugin::path('content', 'formathtml') . DS . 'macros/group.php';

use Plugins\Content\Formathtml\Macros\GroupMacro;

/**
 * Group events Macro
 */
class MembersCards extends GroupMacro
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
                                <li><code>[[Group.MembersCards()]]</code> - Shows all group members.</li>
                                <li><code>[[Group.MembersCards(role=role1;role2)]]</code> - Shows all members from the group with roles "role1" or "role2".</li>
                                <li><code>[[Group.MembersCards(id=2;1;3)</code> - Shows group members with ids 2, 1, and 3.</li>
							</ul>';
        return $txt['html'];
    }

	/**
	 * Generate macro output
	 *
	 * @return     string
	 */
	public function render()
	{
		// Check if we can render
		if (!parent::canRender())
		{
			return \Lang::txt('[This macro is designed for Groups only]');
		}

		// Get args
		$args = $this->getArgs();

		// Parse arguments
		$this->id = $this->getId($args);
		$this->role = $this->getRole($args);
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

		// Subset by id
		if ($this->id) {
			$members_with_ids = array_intersect($members, $this->id);
			// If role specified too, need union, otherwise want subset
			$members = ($this->role ? array_unique(array_merge($members, $members_with_ids)) : $members_with_ids);
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
			\Document::addStyleSheet($this->base . DS . '../assets' . DS . 'members' . DS . 'css' . DS . 'members.css');

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
				$html .=            '<div class="member-bio">' . $profile->get('bio') . '</div>';
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
