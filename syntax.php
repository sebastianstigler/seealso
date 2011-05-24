<?php
/**
 * DokuWiki Plugin seealso (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Sebastian Stigler <a@b.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_seealso extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 306;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\{\{see_also.*?\}\}',$mode,'plugin_seealso');
//        $this->Lexer->addEntryPattern('<FIXME>',$mode,'plugin_seealso');
    }

//    public function postConnect() {
//        $this->Lexer->addExitPattern('</FIXME>','plugin_seealso');
//    }

    public function handle($match, $state, $pos, &$handler){
        global $ID;

        $match = substr($match, 10, -2); // strip {{see_also from start and }} from end
        list($match, $flags) = explode('&', $match, 2);
        $flags = explode('&', $flags);
        list($ns, $tag) = explode('?', $match);

        if (!$tag) {
            $tag = $ns;
            $ns   = '';
        }

        if (($ns == '*') || ($ns == ':')) $ns = '';
        elseif ($ns == '.') $ns = getNS($ID);
        else $ns = cleanID($ns);
        $tags=p_get_metadata($ID, 'subject');
        if (is_array($tags)) {
            $tag=implode(" ",p_get_metadata($ID, 'subject'));
        } else {
            $tag='';
        }
        return array($ns, trim($tag), $flags);

    }

    public function render($mode, &$renderer, $data) {
        list($ns, $tag, $flags) = $data;

        global $ID;

        $tags=p_get_metadata($ID, 'subject');
        if (is_array($tags)) {
            $tag=implode(" ",p_get_metadata($ID, 'subject'));
        } else {
            $tag='';
        }#

        if ($my =& plugin_load('helper', 'tag')) $pagesx = $my->getTopic($ns, '', $tag);
        foreach ($pagesx as $page) {
            if ($page['id'] != $ID) { // strips the current page from the array
                $pages[]=$page;
            }
        }
       // if (!$pages) return true; // nothing to display

        if ($mode == 'xhtml') {

            // prevent caching to ensure content is always fresh
            $renderer->info['cache'] = false;
            // let Pagelist Plugin do the work for us
            if (plugin_isdisabled('pagelist')
                    || (!$pagelist = plugin_load('helper', 'pagelist'))) {
                msg($this->getLang('missing_pagelistplugin'), -1);
                return false;
            }
            $pagelist->setFlags($flags);
            $pagelist->startList();
            foreach ($pages as $page) {
                $pagelist->addPage($page);
            }
            $title = $this->getLang('tag_see_also');
            $hid = $this->_addToTOC($title, 2, $renderer);

            $renderer->doc .= '<div class="see_also">'.DOKU_LF;
            $renderer->doc .= DOKU_TAB.'<h2 >'.DOKU_LF;
            $renderer->doc .= DOKU_TAB.DOKU_TAB.'<a id="'.$hid.'" name="'.$hid.'">'.$this->getLang('tag_see_also').'</a>'.DOKU_LF;
            $renderer->doc .= DOKU_TAB.'</h2>'.DOKU_LF;
            $renderer->doc .= $pagelist->finishList();
            $renderer->doc .= '</div>'.DOKU_LF;
            return true;

        // for metadata renderer
        } elseif ($mode == 'metadata') {
            foreach ($pages as $page) {
                $renderer->meta['relation']['references'][$page['id']] = true;
            }

            return true;
        }
        return false;

    }
    /**
     * Adds a TOC item
     * form plugin:info
     */
    function _addToTOC($text, $level, &$renderer){
        global $conf;

        if (($level >= $conf['toptoclevel']) && ($level <= $conf['maxtoclevel'])){
            $hid  = $renderer->_headerToLink($text, 'true');
            $renderer->toc[] = array(
                'hid'   => $hid,
                'title' => $text,
                'type'  => 'ul',
                'level' => $level - $conf['toptoclevel'] + 1
            );
        }
        return $hid;
    }


}

// vim:ts=4:sw=4:et:
