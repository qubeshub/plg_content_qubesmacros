<?php

/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Plugins\Content\Formathtml\Macros;

use Plugins\Content\Formathtml\Macro;
use Hubzero\User\Group;

/**
 * Publications Macro Base Class
 * Extends basic macro class
 */
class Members extends Macro
{
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
								<li><code>[[Members()]]</code> - Shows all members.</li>
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

    public function _getCardView()
    {
        \Document::addStyleSheet($this->base . DS . 'assets' . DS . 'members' . DS . 'css' . DS . 'members.css');

        $html =     '<div class="member-card">';
        $html .=        '<div class="member-card-upper">';
        $html .=            '<div class="member-img">';
        $html .=                '<img src="/community/groups/webdev/File:/uploads/awiens.jpg" alt="Member Image">';
        $html .=                '<h2>Meowy McMeowster</h2>';
        $html .=                '<p>Cattery Institution of Cat Stuff</p>';
        $html .=            '</div>';
        $html .=            '<div class="member-badges">';
        $html .=            '</div>';
        $html .=        '</div>';
        $html .=        '<div class="member-card-lower">';
        $html .=            '<p class="member-bio">';
        $html .=            '</p>';
        $html .=            '<div class="member-links">';
        $html .=                '<a href="#" class="member-profile">';
        $html .=                    '<img src="core/assets/icons/user.svg" alt="Profile">';
        $html .=                    'Profile';
        $html .=                '</a>';
        $html .=                '<a href="#" class="member-website">';
        $html .=                    '<img src="core/assets/icons/earth.svg" alt="Website">';
        $html .=                    'Website';
        $html .=                '</a>';
        $html .=            '</div>';
        $html .=        '</div>';
        $html .=    '</div>';

        return $html;
    }
}