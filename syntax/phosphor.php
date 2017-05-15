<?php
/**
 * popoutviewer Plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     i-net software <tools@inetsoftware.de>
 * @author     Gerry Weissbach <gweissbach@inetsoftware.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_phosphor_phosphor extends DokuWiki_Syntax_Plugin {

    private $headers = array();

    function getInfo(){
        return array_merge(confToHash(dirname(__FILE__).'/info.txt'), array(
                'name' => 'PhosPhor - movie embedding',
                'desc' => 'See: http://www.divergentmedia.com/support/documentation/Phosphor'
                ));
    }

    function getType() { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort() { return 1; }

    function connectTo($mode) {

        // {{phosphor> de:products:helpdesk:mobile:phosphor:openticketandshowimage:openticketandshowimage.jpg |background=de:products:helpdesk:mobile:phosphor:iphone-landscape-masked.png?600&background-inset-left=139px&background-inset-top=72px&content-frame-zoom=0.6699&background-overlay=de:products:helpdesk:mobile:phosphor:iphone-landscape-masked-overlay.png |}}
        // {{phosphor>$image$|$title$}}
        // params:
        //            class
        //            prepend
        $this->Lexer->addSpecialPattern('{{phosphor>[^}]+}}', $mode, 'plugin_phosphor_phosphor');
    }

    function handle($match, $state, $pos, Doku_Handler $handler) {

        $orig = substr($match, 11, -2);
        list($id, $background, $title) = explode('|', $orig, 3); // find ID/Params + Name Extension
        list($id, $param) = explode('?', $id, 2); // find ID + Params
        $paramlist = array_merge(explode('&', $param), explode('&', $background));

        $params = array();
        foreach($paramlist as $param)
        {
            list($n, $v) = explode('=', $param);
            $params[$n] = trim($v);
        }

        return array(trim($id), $title, $params, $orig);
    }

    function render($mode, Doku_Renderer $renderer, $data) {

        if ($mode == 'xhtml') {
            $this->phosphorContent($renderer, $data);
            return true;
        }

        return false;
    }

    function phosphorContent(&$renderer, $data, $returnOnly = false, $tag='img')
    {
        global $ID;

        list($id, $title, $params, $orig) = $data;
        if ( empty($id) ) { $exists = false; } else
        {
            $page   = resolve_id(getNS($ID),$id);
            $file   = mediaFN($page);
            $exists = @file_exists($file) && @is_file($file);
        }

        $scID = sectionID(noNs($id), $this->headers);
        $more = 'id="' . $scID . '"';
        $script = '';

        if ( $exists ) {
            // is Media

            $p1 = Doku_Handler_Parse_Media($orig);

            $p = array();
            $p['alt'] = $title;
            $params['class'] .= ' phosphor';
            $p['class'] = $params['class'];

/*            
            $p['class'] = 'phosphor';
            if ( !empty($params['class']) ) {
                unset($params['class']);
            }
*/            
            $p['title'] = $title;
            $p['id'] = 'anim_target_' . $scID;

            $p['imageArray'] = $this->getImageArray($id, $params['prepend']);

            $name = array_shift(explode('.', noNS($id), 2));
            $p['callback'] = 'phosphorCallback_' . $name;
            $p['json'] = $this->existingMediaFile(getNS($id), $name . '_animationdata.jsonp' );

            if ($p1['width']) $p['width'] = $p1['width'];
            if ($p1['height']) $p['height'] = $p1['height'];
            if ($p1['title'] && !$p['title']) { $p['title'] = $p1['title']; $p['alt'] = $p1['title']; }
            if ($p1['align']) $params['class'] .= ' media' . $p1['align'];
            if ($params['speed']) $p['speed'] = $params['speed'];
            if ($params['loop']) $p['looping'] = $params['loop'];

            $p2 = buildAttributes($p);
            $content = '<' . $tag . ' src="' . ml($id, array( 'w' => $p['width'], 'h' => $p['height'] ) ) . '" '.$p2;
            $content .= ($tag == 'img' ? '/>' : '></' . $tag . '>');

            if ( $returnOnly ) {
                return $content;
            }

            $this->backgroundContainer($renderer, $params, $content);
        }
    }

    function backgroundContainer(&$renderer, &$params, $content='')
    {
        if ( !empty($params['background']) ) {

            $background = Doku_Handler_Parse_Media($params['background']);

            $style = 'background-image:url(' . ml($background['src'], array( 'w' => $background['width'], 'h' => $background['height'] ) ) . ');';
            if ( !empty($background['width']) ) {

                if ( empty($background['height']) ) {
                     $info = @getimagesize(mediaFN($background['src'])); //get original size
                     $background['height'] = round(($background['width'] * $info[1]) / MAX(0.001, $info[0]));
                }

                $style .= 'background-size:' . $background['width'] . 'px ' . $background['height'] . 'px;';
                $style .= 'width:' . (!empty($params['width'])?$params['width']:$background['width'] . 'px') .'; height:' . $background['height'] . 'px;';
            }

            $style2 = 'padding-top:' . $params['background-inset-top'] . ';';
            $style2 .= 'padding-left:' . $params['background-inset-left'] . ';';

            $zoom = 'zoom="' . $params['content-frame-zoom'] . '"';
            $params['class'] .= ' hidden';

            if ( !empty($params['background-overlay']) ) {
                $overlay = 'overlay="' . ml($params['background-overlay'], array( 'w' => $background['width'], 'h' => $background['height'] ) ) . '"';
            }

            if ( !empty($params['loop']) && $params['loop'] == "true" ) {
                $loop = 'loop="true"';
            }
        }

        $renderer->doc .= '<span class="' . trim($params['class']) . '" style="' . $style . '" ' . (!empty($zoom)?$zoom:'') . ' ' . (!empty($overlay)?$overlay:'') . ' ' . (!empty($loop)?$loop:'') . ' >';
        $renderer->doc .= '<span style="' . $style2 . '" class="phcontent">';
        $renderer->doc .= $content;
        $renderer->doc .= '</span></span>';
    }

    function getImageArray($baseID, $prepend)
    {
        if ( empty($prepend) ) {
            $prepend = "_atlas";
        }

        $return = array();
        $ns = getNS($baseID);
        $baseID = noNS($baseID);
        $counter = 0;
        list($name, $ext) = explode('.', $baseID, 2);

        do {
            if ( !is_null($page = $this->existingMediaFile($ns, $name . $prepend. sprintf('%03u', $counter) . '.' . $ext)) ) {
                $counter ++;
                $return[] = $page;
            } else {
                break;
            }
        } while (1==1);

        return implode(',', $return);
    }

    function existingMediaFile($ns, $name)
    {
        $page = resolve_id($ns, $name);
        $file   = mediaFN($page);
        $exists = @file_exists($file) && @is_file($file);
        if ( $exists ) {
            return ml($page);
        }

        return null;
    }
}
// vim:ts=4:sw=4:et:enc=utf-8:
