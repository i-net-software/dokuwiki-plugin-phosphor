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

class syntax_plugin_phosphor_phblock extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return array_merge(confToHash(dirname(__FILE__).'/info.txt'), array(
                'name' => 'PhosPhor - movie embedding as a block',
                ));
    }

    function getType(){ return 'container';}
    function getAllowedTypes() { return array('phitem'); }
    function getPType(){ return 'block';}

    /**
     * Where to sort in?
     */
    function getSort(){ return 301; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {       
      $this->Lexer->addEntryPattern('<phosphor>(?=.*?</phosphor>)',$mode,'plugin_phosphor_phblock');
      $this->Lexer->addEntryPattern('<phosphor .+?>(?=.*?</phosphor>)',$mode,'plugin_phosphor_phblock');
    }

    function postConnect() {
      $this->Lexer->addExitPattern('</phosphor.*?>', 'plugin_phosphor_phblock');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){

        switch ($state) {
            case DOKU_LEXER_ENTER:

                $option = array( 'class' => 'phosphor' );
                foreach ( explode(' ', substr($match, 10, -1)) as $item ) {
                    list($v,$n) = explode('=', $item, 2);
                    list($w1, $w2) = explode('x', $item, 2);

                    if ( empty($n) ) {
                        if ( !empty($w2) ) {
                            // Width+Height
                            $option['phwrapper_width'] = $w1;
                            $v = 'phitemlist_width';
                            $n = $w2;
                        } else {
                            $option['class'] .= ' ' . trim($v);
                            continue;
                        }
                    }

                    $option[$v] = trim($n);
                }

                return array('phosphor__start', $option, $pos);
                break;

            case DOKU_LEXER_EXIT:

                return array('phosphor__end', null, $pos + strlen($match));
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

            list($instr, $data, $pos) = $input;

            switch ( $instr ) {

                case 'phosphor__start' :

                    $renderer->doc .= '<div class="phblock' . (method_exists($renderer, "finishSectionEdit") ? ' ' . $renderer->startSectionEdit($pos, 'section', 'layeranimation') : "") . '">' . "\n";
                    $renderer->doc .= '<div class="phwrapper"' . (!empty($data['phitemlist_width'])?' style="width:' . hsc($data['phwrapper_width']) . '"':'') . '>';

                    $functions =& plugin_load('syntax', 'phosphor_phosphor' );
                    $renderer->doc .= $functions->backgroundContainer($renderer, $data);

                    $renderer->doc .= '</div>';
                    $renderer->doc .= '<div class="phitemlist"' . (!empty($data['phitemlist_width'])?' style="width:' . hsc($data['phitemlist_width']) . '"':'') . '>';

                    break;
                case 'phosphor__end' :

                    $renderer->doc .= '</div></div>' . "\n";
                    if ( method_exists($renderer, "finishSectionEdit") ) { $renderer->finishSectionEdit($pos); }

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
