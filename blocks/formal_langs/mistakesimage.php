<?php
// This file is part of Formal Languages block - https://bitbucket.org/oasychev/moodle-plugins/
//
// Formal Languages block is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Formal Languages block is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Formal Languages block.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mistake image generator, used in poasquestion to show student error
 *
 * @package    blocks
 * @subpackage formal_langs
 * @copyright  2011 Sychev Oleg, Mamontov Dmitry
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/textimagerenderer.php');
require_once(dirname(__FILE__) . '/classes/mistakesimage/defines.php');
require_once(dirname(__FILE__) . '/classes/mistakesimage/abstract_label.php');
require_once(dirname(__FILE__) . '/classes/mistakesimage/empty_label.php');
require_once(dirname(__FILE__) . '/classes/mistakesimage/lexeme_label.php');

/** Main class, which should be used to create and output image
 */
class block_formal_langs_image_generator
{
   /**
    * Returns created default image
    * @param array $size of width height
    * @return array of <image, array of palette>
    */
   public static function create_default_image($size) {
       // Create image
       $sizex = $size[0] + 2 * FRAME_SPACE;
       $sizey = $size[1] + 2 * FRAME_SPACE;
       $im = imagecreatetruecolor($sizex, $sizey);

       // Fill palette
       $palette = array();
       $palette['white'] = imagecolorallocate($im, 255, 255, 255);
       $palette['black'] = imagecolorallocate($im, 0, 0, 0);
       $palette['red']   = imagecolorallocate($im, 255, 0, 0);

       // Set image background to white
       imagefill($im,0,0,$palette['white']);

       // Draw a rectangle frame
       imagesetthickness($im, FRAME_THICKNESS);
       imageline($im, 0, 0, $sizex - 1, 0, $palette['black']);
       imageline($im, $sizex - 1, 0, $sizex - 1, $sizey - 1, $palette['black']);
       imageline($im, $sizex - 1, $sizey - 1, 0, $sizey - 1, $palette['black']);
       imageline($im, 0, $sizey - 1, 0, 0, $palette['black']);

       return array($im, $palette);
   }

}
