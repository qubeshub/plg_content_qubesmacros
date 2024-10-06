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
class Mailinglist extends GroupMacro
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
        $txt['html']  = '<p>Displays mailing list email sign up field.</p>';
        $txt['html'] .= '<p>Examples:</p>
							<ul>
								<li><code>[[Group.MailingList(list=Mailing List)]]</code> - Shows sign up form for "Mailing List".</li>
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
		$this->base = rtrim(str_replace(PATH_ROOT, '', __DIR__));

		// Get details (filters)
		$this->list = $this->_getMailingList($args);
        
        if ($this->getSubscriptionInfo() === false) {
            $html = '<div>Mailing list not found.</div>';
        } else {
            $html = $this->renderSignUp();
        }

		// Return html
		return $html;
	}

	/**
	 * Get subscriber info
	 *
	 * @param      object $group
	 * @return     array
	 */
	private function getSubscriptionInfo()
	{
        // Get mailing list details that we are wanting users to sign up for
		$sql = "SELECT * FROM `#__newsletter_mailinglists` WHERE deleted=0 AND private=0 AND name LIKE '%" . $this->list . "%'";
		$this->_db->setQuery($sql);
		$this->mailinglist = $this->_db->loadObject();

        if (!is_object($this->mailinglist))
        {
            return false;
        } else {
            // Get mailing list subscription if not guest
            $this->subscription   = null;
            $this->subscriptionId = null;
            if (!User::isGuest())
            {
                $sql = "SELECT * FROM `#__newsletter_mailinglist_emails` WHERE mid=" . $this->_db->quote($this->mailinglist->id) . " AND email=" . $this->_db->quote(User::get('email'));
                $this->_db->setQuery($sql);
                $this->subscription = $this->_db->loadObject();
            }

            // If we are unsubscribed...
            if (is_object($this->subscription) && $this->subscription->status == 'unsubscribed')
            {
                $this->subscriptionId = $this->subscription->id;
                $this->subscription   = null;
            }

            return true;
        }
	}

	/**
	 * Render the sign up form
	 *
	 * @return  string
	 */
	private function renderSignUp()
	{
        \Document::addStyleSheet($this->base . DS . '../assets' . DS . 'mailinglist' . DS . 'css' . DS . 'mailinglist.css');
        \Document::addScript($this->base . DS . '../assets' . DS . 'mailinglist' . DS . 'js' . DS . 'mailinglist.js');

        $token = Session::getFormToken();

        $html = '<div style="text-align:center;" class="form">';
	    $html .= '<form class="mailinglist-signup" action="' . Route::url('index.php?option=com_newsletter') . '" method="post">';
		if (is_object($this->subscription)) {
			$html .= '<span>' . Lang::txt('You are already subscribed to this mailing list. <br><a href="%s">Manage your subscriptions.</a>', Route::url('index.php?option=com_newsletter&task=subscribe')) . '</span>';
        } else {
            $html .= '<div>';
			// $html .= '<label for="email">';
			$html .= '<input type="text" name="email_' . $token . '" id="email" value="' . User::get('email') . '" placeholder="email address" data-invalid="In order to sign up, you must enter a valid email address." />';
			// $html .= '</label>';

			$html .= '<label for="hp1_' . $token .'" id="hp1">';
			$html .= 'Honey Pot:<span class="optional">Please leave blank.</span>';
			$html .= '<input type="text" name="hp1" id="hp1_' . $token . '" value="" />';
			$html .= '</label>';

			$html .= '<input class="btn" type="submit" value="Sign Up!" id="sign-up-submit" />';

			$html .= '<input type="hidden" name="list_' . $token . '" value="' . $this->mailinglist->id . '" />';
			$html .= '<input type="hidden" name="option" value="com_newsletter" />';
			$html .= '<input type="hidden" name="controller" value="mailinglists" />';
			$html .= '<input type="hidden" name="subscriptionid" value="' . $this->subscriptionId . '" />';
			$html .= '<input type="hidden" name="task" value="dosinglesubscribe" />';
			$html .= '<input type="hidden" name="return" value="' . base64_encode($_SERVER['REQUEST_URI']) . '">';

			$html .= Html::input('token');
        }
	    $html .= '</form>';
        $html .= '</div>';

		return $html;
	}

	/**
	 * Get mailing list
	 *
	 * @param  	$args Macro Arguments
	 * @return 	mixed
	 */
	private function _getMailingList(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/list=([\w ]*)/', $arg, $matches))
			{
                $list = (isset($matches[1])) ? $matches[1] : '';
				unset($args[$k]);
				return $list;
			}
		}

		return false;
	}
}
