<?php

namespace Depage\Cms\XmlDocTypes;

class Colors extends \Depage\XmlDb\XmlDocTypes\Base {

    // {{{ constructor
    public function __construct($xmldb, $docId) {
        parent::__construct($xmldb, $docId);

        // list of elements that may created by a user
        $this->availableNodes = array(
            'proj:colorscheme' => (object) array(
                'name' => _("Colorscheme"),
                'new' => _("(Untitled Colorscheme)"),
                'icon' => "",
                'attributes' => array(),
            ),
            'color' => (object) array(
                'name' => _("Color"),
                'new' => _("New Color"),
                'icon' => "",
                'attributes' => array(),
            ),
        );

        // list of valid parents given by nodename
        $this->validParents = array(
            'proj:colorscheme' => array(
                'proj:colorschemes',
            ),
            'color' => array(
                'proj:colorscheme',
            ),
        );
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        return $this->testColors($node);
    }
    // }}}
    // {{{ testColors
    public function testColors($node) {
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);

        $changed = false;

        // get all colornames
        $colorNames = array();
        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("/proj:colorschemes/proj:colorscheme[@name != 'tree_name_color_global']/color", $node);

        if ($nodelist->length > 0) {
            // search for colors used in document
            for ($i = 0; $i < $nodelist->length; $i++) {
                $colorNames[$nodelist->item($i)->getAttribute("name")] = true;
            }
        }
        $colorNames = array_keys($colorNames);
        sort($colorNames);

        // test colorschemes if all color names are present
        $nodelist = $xpath->query("/proj:colorschemes/proj:colorscheme[@name != 'tree_name_color_global']", $node);

        if ($nodelist->length > 0) {
            for ($i = 0; $i < $nodelist->length; $i++) {
                $colorscheme = $nodelist->item($i);

                if ($colorscheme->childNodes->length != count($colorNames)) {
                    $changed = true;

                    $this->fixColorscheme($xml, $colorscheme, $colorNames);
                }
            }
        }

        return $changed;
    }
    // }}}
    // {{{ fixColors()
    /**
     * @brief fixColors
     *
     * @param mixed $node, $colorNames
     * @return void
     **/
    protected function fixColorscheme($xml, $node, $colorNames)
    {
        $tempNode = $xml->createElement('temp_node');
        $node->insertBefore($tempNode, $node->firstChild);

        foreach($colorNames as $color) {
            $xpath = new \DOMXPath($xml);
            $nodelist = $xpath->query("/color[@name = '$color']", $node);

            if ($nodelist->length > 0) {
                // move current colornode
                $colorNode = $nodelist->item(0);
            } else {
                // add new colornode
                $colorNode = $xml->createElement("color");
                $colorNode->setAttribute("name", $color);
                $colorNode->setAttribute("value", "#000000");
            }

            $node->insertBefore($colorNode, $tempNode);
        }

        // remove other nodes that are not in colorlist
        while ($tempNode->nextSibling != null) {
            $node->removeChild($tempNode->nextSibling);
        }

        $node->removeChild($tempNode);
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */