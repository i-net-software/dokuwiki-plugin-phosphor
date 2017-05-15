<?php
/**
 * Imageflow Plugin
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     i-net software <tools@inetsoftware.de>
 * @author     Gerry Weissbach <gweissbach@inetsoftware.de>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_phosphor_phitem extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return array_merge(confToHash(dirname(__FILE__).'/info.txt'), array(
                'name' => 'PhosPhor - Block Item',
        ));
    }

    function getType(){ return 'phitem';}
    function getAllowedTypes() { return array('container','substition','protected','disabled','formatting','paragraphs'); }
    function getPType(){ return 'block';}

    /**
     * Where to sort in?
     */
    function getSort(){ return 301; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {       
      $this->Lexer->addEntryPattern('<item>(?=.*?</item>)',$mode,'plugin_phosphor_phitem');
      $this->Lexer->addEntryPattern('<item .+?>(?=.*?</item>)',$mode,'plugin_phosphor_phitem');
    }

    function postConnect() {
      $this->Lexer->addExitPattern('</item.*?>', 'plugin_phosphor_phitem');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
            case DOKU_LEXER_ENTER:

                list($id, $title) = explode('|', substr($match, 6, -1), 2); // find ID/Params + Name Extension
                list($id, $paramlist) = explode('?', $id, 2); // find ID + Params

                $params = array();
                foreach(explode('&', $paramlist) as $param)
                {
                    list($n, $v) = explode('=', $param);
                    $params[$n] = trim($v);
                }

                return array('item__start', array($id, $title, $params));
                break;

            case DOKU_LEXER_UNMATCHED:

                $handler->_addCall('cdata',array($match), $pos);
                return false;
                break;
            case DOKU_LEXER_EXIT:

                return array('item__end', null);
            break;
        }       
        return false;
    }

    /**
    * Create output
    */
    function render($mode, Doku_Renderer $renderer, $input) {
        global $conf;
        if($mode == 'xhtml'){

            $renderer->nocache();

            list($instr, $data) = $input;

            switch ( $instr ) {

                case 'item__start' :

                    list($id, $title, $params) = $data;
                    $renderer->doc .= '<div class="phitem">' . "\n";
                    if ( $title ) {
                        $renderer->doc .= '<p class="phhead">' . hsc($title) . '</p>';
                    }

                    $functions =& plugin_load('syntax', 'phosphor_phosphor' );
                    $renderer->doc .= $functions->phosphorContent($renderer, $data, true, 'phosphor');

                    break;
                case 'item__end' :

                    $renderer->doc .= '</div>' . "\n";

                    break;
                default :
                    return false;
            }
            return true;
        }
        return false;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
