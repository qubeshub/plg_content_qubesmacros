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
								<li><code>[[Group.Subgroups(group=mysubgroup1;mysubgroup2)</code> - Shows all subgroups with group short names "mysubgroup1" and "mysubgroup2".</li>
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

		// Database
		$this->_db = App::get('db');

		// Get details (filters)
		$this->subgroup = $this->_getGroup($args);

		// Parse arguments
		$this->base = rtrim(str_replace(PATH_ROOT, '', __DIR__));

		// Get subgroups
		$groups = $this->getSubgroups($this->group);
		if (count($groups) == 0) {
			$html = '<div>No subgroups matching criteria found.</div>';
		} else {
            $html = $this->renderSubgroups($groups);
        }

		// Return html
		return $html;
	}

	/**
	 * Get a list of subgroups of a group
	 *
	 * @param      object $group
	 * @return     array
	 */
	private function getSubgroups($group)
	{
		$query = "SELECT child as gid FROM `#__xgroups_groups` as gg ";
		
		if ($this->subgroup) {
			$query .= "INNER JOIN (SELECT gidNumber, cn FROM `#__xgroups`) G ON G.gidNumber = gg.child ";
		}
		
		$query .= "WHERE gg.parent=" . $group->get('gidNumber');

		if ($this->subgroup) {
			$query .= " AND G.cn IN (" . $this->subgroup . ")";
		}

		$this->_db->setQuery($query);

		$result = $this->_db->loadColumn();

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

	/**
	 * Get subgroup filters
	 *
	 * @param  	$args Macro Arguments
	 * @return 	mixed
	 */
	private function _getGroup(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/group=([\w;]*)/', $arg, $matches))
			{
				$group = implode(',', array_map(array($this->_db, 'quote'), explode(';', (isset($matches[1])) ? $matches[1] : '')));
				unset($args[$k]);
				return $group;
			}
		}

		return false;
	}
}
