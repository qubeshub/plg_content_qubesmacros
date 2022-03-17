<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Plugins\Content\Formathtml\Macros\Group;

require_once Plugin::path('content', 'formathtml') . DS . 'macros/group.php';

use Plugins\Content\Formathtml\Macros\GroupMacro;
use Components\Groups\Models\Orm\Field;

/**
 * Group events Macro
 */
class WelcomeMessage extends GroupMacro
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
        $txt['html']  = '<p>Displays new member welcome message.</p>';
        $txt['html'] .= '<p>Examples:</p>
							<ul>
                                <li><code>[[Group.WelcomeMessage()]]</code> - Embeds new member welcome message.</li>
                                <li><code>[[Group.WelcomeMessage(access=private)]]</code> - Makes embedded welcome message private to group members (default is public).</li>
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

        $html = '';

		// Parse and validate arguments

        // Are we a group member?
        $isMember = (in_array(\User::get('id'), $this->group->get('members'))) ? true : false;

        // Access
		$this->access = $this->getAccess($args);
        if (!in_array($this->access, array("public", "private"))) {
            $html .= '<h3 style="color:red;">Macro error: Access must be set to "public" or "private"</h3>';
            return $html;
        }

        // Get and check welcome
        $this->welcome_message = $this->getWelcomeMessage();
        if (!$this->welcome_message || (($this->access == 'private') && !$isMember)) {
            return '';
        }

        // Begin building html
        $html .= '<div class="welcome-message" style="display:inline-block;">';
        $html .= $this->welcome_message;
        $html .= '</div>';

		// Return rendered events
		return $html;
	}

	/**
	 * Get access
	 *
	 * @param  	$args Macro Arguments
	 * @return 	mixed
	 */
	private function getAccess(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/access=([\w;]*)/', $arg, $matches))
			{
				$access = explode(';', (isset($matches[1])) ? $matches[1] : '');
				unset($args[$k]);
				return $access[0];
			}
		}

		return "public";
    }

    /**
     * Is the welcome message set and non-empty?
     */
    private function getWelcomeMessage() {
        $show_welcome_message = Field::oneByName('show_welcome_message')->collectGroupAnswers($this->group->get('gidNumber'));
        $welcome_message = Field::oneByName('welcome_message')->collectGroupAnswers($this->group->get('gidNumber'));
        return ($show_welcome_message && !empty($welcome_message) ? $welcome_message : false);
    }
}
