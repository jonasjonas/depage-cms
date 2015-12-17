<?php

namespace Depage\Cms\XmlDocTypes\Traits;

trait MultipleLanguages
{
    // {{{ testNodeLanguages
    protected function testNodeLanguages($node) {
        $languages = array();
        // get languages from settings
        $settings = $this->xmldb->getDoc("settings");
        $nodes = $settings->getNodeIdsByXpath("//proj:language");
        foreach ($nodes as $nodeId) {
            $attr = $settings->getAttributes($nodeId);
            $languages[] = $attr['shortname'];
        }

        return self::updateLangNodes($node, $languages);
    }
    // }}}
    // {{{ updateLangNodes()
    /**
     * @brief updateLangNodes
     *
     * @param mixed $
     * @return void
     **/
    public static function updateLangNodes($node, $languages)
    {
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $changed = false;
        $actual_languages = array();
        $temp_nodes = array();


        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("./descendant-or-self::node()[@lang]", $node);

        if ($nodelist->length > 0) {
            // search for languages used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                $lang_attr = $nodelist->item($i)->getAttribute('lang');
                if ($lang_attr == "") {
                    $lang_attr = "_new_language";
                    $nodelist->item($i)->setAttribute('lang', $lang_attr);
                }
                if (!in_array($lang_attr, $actual_languages)) {
                    $actual_languages[] = $lang_attr;
                }
            }

            // get the difference of languages
            $langdiff = array_merge(array_diff($languages, $actual_languages), array_diff($actual_languages, $languages));

            if (count($langdiff) > 0) {
                $first_lang = $nodelist->item(0)->getAttribute('lang');

                // add temporary nodes as markers to insert new nodes
                foreach ($nodelist as $node) {
                    $parent_node = $node->parentNode;
                    if ($node->getAttribute('lang') == $first_lang || $node->getAttribute('lang') == "_new_language") {
                        $temp_node = $xml->createElement('temp_node');
                        $parent_node->insertBefore($temp_node, $node);
                        $temp_nodes[] = $temp_node;
                    }
                }
                for ($i = 0; $i < count($temp_nodes); $i++) {
                    $lang_nodes = array();
                    $temp_node = $temp_nodes[$i];
                    $sibl_node = $temp_node->nextSibling;

                    // search for siblings with lang-nodes
                    while ($sibl_node && $sibl_node->nodeType == \XML_ELEMENT_NODE && $sibl_node->hasAttribute("lang")) {
                        $lang = $sibl_node->getAttribute('lang');
                        if ($lang != "_new_language") {
                            $lang_nodes[$lang] = $sibl_node;
                        } else {
                            $lang_nodes[] = $sibl_node;
                        }
                        $sibl_node = $sibl_node->nextSibling;
                    }
                    $parent_node = $temp_nodes[$i]->parentNode;
                    for ($j = 0; $j < count($languages); $j++) {
                        if (isset($lang_nodes[$languages[$j]])) {
                            // move lang-node before temporary node, so we have the same order
                            // the language settings
                            $temp_node = $lang_nodes[$languages[$j]]->cloneNode(true);
                            $parent_node->insertBefore($temp_node, $temp_nodes[$i]);
                            $temp_node->setAttribute('lang', $languages[$j]);
                        } else {
                            // add new languages by copying existing lang-node
                            if (count($lang_nodes) > 0) {
                                reset($lang_nodes);
                                $lang_node = current($lang_nodes);

                                $temp_node = $lang_node->cloneNode(true);
                                $parent_node->insertBefore($temp_node, $temp_nodes[$i]);
                                \Depage\XmlDb\Document::removeNodeAttr($temp_node, "http://cms.depagecms.net/ns/database", "db");
                                $temp_node->setAttribute('lang', $languages[$j]);
                            }
                        }
                    }
                    // remove temporary and unused nodes
                    $parent_node->removeChild($temp_nodes[$i]);
                    foreach ($lang_nodes as $lang_node) {
                        $lang_node->parentNode->removeChild($lang_node);
                    }
                }
                $changed = true;
            }
        }

        return $changed;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */