<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

namespace Plugins\Content\Formathtml\Macros;

use Plugins\Content\Formathtml\Macro;
use Hubzero\User\Group;
use Filesystem;
use FilesystemIterator;
use DirectoryIterator;


/**
 * macro class for displaying an image slider
 */
class Carousel extends Macro
{
	/**
	 * Returns description of macro, use, and accepted arguments
	 *
	 * @return     array
	 */
	public function description()
	{
		$txt = array();
		$txt['html']  = "<p>Creates a carousel of images.</p>";
		$txt['html'] .= '<p>Examples:</p>
						<ul>
							<li><code>[[Carousel(images=image1.jpg;image2.gif;image3.png)]]</code> - Display 3 images.</li>
							<li><code>[[Carousel(images=image1.jpg;imagedir)]]</code> - Display "image1.jpg" and all images in the "imagedir" directory.</li>
							<li><code>[[Carousel(images=imagedir, timeout=3000)]]</code> - Display all images in the imagedir directory, displaying each image for 3000 milliseconds.</li>
							<li><code>[[Carousel(images=imagedir, height=50%, width=50%)]]</code> - Display all images in the imagedir directory, scaling height and width by 50%.</li>
							<li><code>[[Carousel(images=imagedir, align=center)]]</code> - Center carousel. Other option includes <code>align=right</code>.</li>
							<li><code>[[Carousel(images=imagedir, float=right)]]</code> - Float carousel to the right. Other option includes <code>float=left</code>.</li>
						</ul>';

		return $txt['html'];
	}

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
		//get the args passed in
		$args = $this->getArgs();

		// args will be null if the macro is called without parenthesis.
		if (!$args)
		{
				return;
		}

		$images = $this->_getImages($args);
		$timeout = $this->_getTimeout($args);
		$height = $this->_getHeight($args);
		$width = $this->_getWidth($args);
		$align = $this->_getAlignment($args);
		$float = $this->_getFloat($args);

		//generate a unique id for the slider
		$id = uniqid();

		// null base url for now
		$base_url = '';

		// needed objects
		$db     = \App::get('db');
		$option = \Request::getCmd('option');
		$config = \Component::params($option);

		// define a base url
		switch ($option)
		{
			case 'com_groups':
				$cn = \Request::getString('cn');
				$group = Group::getInstance($cn);

				$base_url  = DS . trim($config->get('uploadpath', 'site/groups'), DS) . DS;
				$base_url .= $group->get('gidNumber') . DS . 'uploads';
			break;

			case 'com_resources':
				require_once \Component::path('com_resources') . '/models/entry.php';

				$row = \Components\Resources\Models\Entry::oneOrNew($this->pageid);

				$base_url  = DS . trim($config->get('uploadpath', 'site/resources'), DS) . DS;
				$base_url .= trim($row->relativePath(), DS) . DS . 'media';
			break;
		}


		//array for checked slides
		$final_slides = array();

		//check each passed in slide
		if ($images) {
			foreach ($images as $slide)
			{
				//check to see if image is external
				if (strpos($slide, 'http') === false)
				{
					$slide = trim($slide);

					//check if internal file actually exists
					if (is_file(PATH_APP . $base_url . DS . $slide))
					{
						$final_slides[] = 'app' . $base_url . DS . $slide;
					} else {
						//If a directory is taken as the input argument,it will get into the directory and render the images
						$path =  'app' . $base_url . DS . $slide;
						$imgpath = Filesystem::listContents($path, $filter = '.', $recursive = false, $full = false, $exclude = array('.svn', '.git', 'CVS', '.DS_Store', '__MACOSX'));
						foreach($imgpath as $img) {
							foreach($img as $key => $value) { 
								if($key==='path'){
									//Used to check if it's an image file
									if(preg_match("/\.(bmp|gif|jpg|jpe|jpeg|png)$/i",$value)) {
										$imgaddr = $path . $value;
										$final_slides[] = $imgaddr;
									}
								}
							}
						}
					}
				} else {
					$headers = get_headers($slide);
					if (strpos($headers[0], "OK") !== false)
					{
						$final_slides[] = $slide;
					}
				}
			}
		}

		// if ($height === 'auto') {
		// 	$height = array_reduce($final_slides, function($carry, $item) { return max(getimagesize($item)[1], $carry); });
		// }
		$html  = '';
		$html .= '<div class="wiki_slider' . ($align ? ' ' . $align : '') . ($float ? ' ' . $float : '') . '">';
		$html .= '<div id="slider_' . $id . '", style="height: ' . $height . ';width:' . $width . '">';
		if ($final_slides) {
			foreach ($final_slides as $fs)
			{
				$html .= '<img src="' . $fs . '" alt="" />';
			}
		} else {
			$html .= '<p class="wiki_slider_error">Carousel Macro Error: No images found.</p>';
		} 
		$html .= '</div>';
		$html .= '<div class="wiki_slider_pager" id="slider_' . $id . '_pager"></div>';
		$html .= '</div>';

		$base = rtrim(str_replace(PATH_ROOT, '', __DIR__));

		\Document::addStyleSheet($base . DS . 'assets' . DS . 'carousel' . DS . 'css' . DS . 'carousel.css');
		\Document::addScript($base . DS . 'assets' . DS . 'carousel' . DS . 'js' . DS . 'carousel.js');
		\Document::addScriptDeclaration('
			var $jQ = jQuery.noConflict();

			$jQ(setTimeout(function() {
				$jQ("#slider_' . $id . '").cycle({
					fx: \'scrollHorz\',
					timeout: ' . $timeout . ',
					pager: \'#slider_' . $id . '_pager\',
					fit: 1,
					containerResize: 1
				});
			}, 500));
		');

		return $html;
	}

	private function _getImages(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/images=([\S;]*)/', $arg, $matches))
			{
				$image = array_map('trim' , explode(';', (isset($matches[1])) ? $matches[1] : ''));
				unset($args[$k]);
				return $image;
			}
		}

		return false;
	}

	private function _getTimeout(&$args, $default = "3000")
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/timeout=([\w;]*)/', $arg, $matches))
			{
				$timeout = (isset($matches[1]) ? $matches[1] : '');
				unset($args[$k]);
				return $timeout;
			}
		}

		return $default;
	}


	private function _getHeight(&$args, $default = "auto")
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/height=([\S;]*)/', $arg, $matches))
			{
				$imgHeight = (isset($matches[1]) ? $matches[1] : '');
				unset($args[$k]);
				return $imgHeight;
			}
		}

		return $default;
	}

	private function _getWidth(&$args, $default = "auto")
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/width=([\S;]*)/', $arg, $matches))
			{
				$imgWidth = (isset($matches[1]) ? $matches[1] : '');
				unset($args[$k]);
				return $imgWidth;
			}
		}

		return $default;
	}
	
	private function _getAlignment(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/align=([\w;]*)/', $arg, $matches))
			{
				$align = (isset($matches[1]) ? $matches[1] : '');
				unset($args[$k]);
				return 'align-' . $align;
			}
		}

		return false;
	}
	
	private function _getFloat(&$args)
	{
		foreach ($args as $k => $arg)
		{
			if (preg_match('/float=([\w;]*)/', $arg, $matches))
			{
				$float = (isset($matches[1]) ? $matches[1] : '');
				unset($args[$k]);
				return 'float-' . $float;
			}
		}

		return false;
	}

}
