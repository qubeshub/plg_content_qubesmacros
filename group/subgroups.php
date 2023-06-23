<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Plugins\Content\Formathtml\Macros\Group;

require_once Plugin::path('content', 'formathtml') . DS . 'macros/group.php';

use Plugins\Content\Formathtml\Macros\GroupMacro;

/**
 * Group events Macro
 */
class Subgroups extends GroupMacro
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
        $txt['html']  = '<p>Displays subgroups.</p>';
        $txt['html'] .= '<p>Examples:</p>
							<ul>
                                <li><code>[[Group.Subgroups()]]</code> - Shows all subgroups.</li>
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
		$this->base = rtrim(str_replace(PATH_ROOT, '', __DIR__));

		// Get subgroups
		$groups = $this->getSubgroups($this->group);
		if (count($groups) == 0) {
			$html = '<div>No subgroups found.</div>';
		} else {
            $html = $this->renderSubgroups($groups);
        }

		// Return html
		return $html;
	}

	/**
	 * Get a list of events for a group
	 *
	 * @param      object $group
	 * @return     array
	 */
	private function getSubgroups($group)
	{
        $db =  \App::get('db');

		$query = "SELECT child as gid FROM `#__xgroups_groups` as gg WHERE gg.parent=" . $group->get('gidNumber');

		$db->setQuery($query);

		$result = $db->loadColumn();

        # Use array_map to getInstance from Hubzero/User/Group from gid
        return array_map(function($gid) {
            return $this->group->getInstance((int) $gid);
        }, $result);
	}

	/**
	 * Render the events
	 *
	 * @param   array  Array of group events
	 * @param   array  $members  Array of members
	 * @return  string
	 */
	private function renderSubgroups($groups)
	{
		$html = '<div class="groups-container">';

		foreach ($groups as $group)
		{
			// Call app/components/com_groups/site/views/groups/tmpl/_group.php view
            $view = new \Hubzero\Component\View(array(
                'base_path' => Component::path('com_groups') . DS . 'site',
                'name'      => 'groups',
                'layout'    => '_group'
            ));	
            $view->group = $group;
            $view->option = $this->option;
            $html .= $view->loadTemplate();
        }
        $html .= '</div>';

		return $html;
	}
}
