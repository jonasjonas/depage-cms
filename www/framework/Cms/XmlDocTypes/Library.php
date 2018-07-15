<?php

namespace Depage\Cms\XmlDocTypes;

// TODO configure

class Library extends Base {
    use Traits\UniqueNames;

    const XML_TEMPLATE_DIR = __DIR__ . '/LibraryXml/';

    // {{{ constructor
    public function __construct($xmlDb, $document) {
        parent::__construct($xmlDb, $document);

        $doctypePage = new \Depage\Cms\XmlDocTypes\Page($this->xmlDb, null);

        // list of elements that may created by a user
        $this->availableNodes = [
            'proj:folder' => (object) [
                'name' => _("Folder"),
                'newName' => _("Untitled"),
                'icon' => "",
            ],
        ];

        foreach ($this->availableNodes as $nodeName => &$node) {
            $node->nodeName = $nodeName;
        }

        // list of valid parents given by nodename
        $this->validParents = [
            'proj:folder' => [
                'proj:folder',
                'proj:library',
            ],
        ];
    }
    // }}}

    // {{{ onAddNode
    /**
     * On Add Node
     *
     * @param \DomNode $node
     * @param $target_id
     * @param $target_pos
     * @param $extras
     * @return null
     */
    public function onAddNode(\DomNode $node, $targetId, $targetPos, $extras) {
        if (isset($extras)) {
            $node->setAttribute("name", $extras);
        }
        $path = $this->getPathById($targetId, $node->getAttribute("name"));
        $this->fs()->mkdir($path);

        return true;
    }
    // }}}
    // {{{ name()
    /**
     * @brief name
     *
     * @param mixed $param
     * @return void
     **/
    public function onMoveNode($nodeId, $oldParentId)
    {
        $name = $this->document->getAttribute($nodeId, "name");
        $srcPath = $this->getPathById($oldParentId, $name);
        $targetPath = $this->getPathById($nodeId);

        if ($srcPath != $targetPath) {
            $this->fs()->mv($srcPath, $targetPath);
            $this->clearGraphicsCache($srcPath);
        }

        return true;
    }
    // }}}
    // {{{ onCopyNode
    /**
     * On Copy Node
     *
     * @param \DomElement $node
     * @param $target_id
     * @param $target_pos
     * @return null
     */
    public function onCopyNode($node_id, $copy_id)
    {
        // @todo disable copying folders?
        return true;
    }
    // }}}
    // {{{ onDeleteNode()
    /**
     * On Delete Node
     *
     * Deletes an xmlDb document by the given id.
     *
     * @param $doc_id
     * @return boolean
     */
    public function onDeleteNode($nodeId, $parentId)
    {
        $path = $this->getPathById($nodeId);
        if (empty($path)) {
            return true;
        }
        try {
            // @todo move to trash instead of deleting directly !important
            $this->fs()->rm($path);
            $this->clearGraphicsCache($path);
        } catch (\Exception $e) {
        }

        return true;
    }
    // }}}
    // {{{ onSetAttribute
    /**
     * On Delete Node
     *
     * @param $node_id
     * @param $parent_id
     * @return bool
     */
    public function onSetAttribute($nodeId, $attrName, $oldVal, $newVal) {
        parent::onSetAttribute($nodeId, $attrName, $oldVal, $newVal);

        if ($attrName == "name") {
            $parentId = $this->document->getParentIdById($nodeId);
            $srcPath = $this->getPathById($parentId, $oldVal);
            $targetPath = $this->getPathById($parentId, $newVal);

            $this->fs()->mv($srcPath, $targetPath);
            $this->clearGraphicsCache($srcPath);
        }

        return true;
    }
    // }}}

    // {{{ testDocument
    public function testDocument($node) {
        $changed = $this->testUniqueNames($node, "//proj:*");

        $xmlnav = new \Depage\Cms\XmlNav();

        // add parent url if $node is not root node
        list($xml, $node) = \Depage\Xml\Document::getDocAndNode($node);
        $xmlnav->addUrlAttributes($node, $this->getParentUrl($node));

        return $changed;
    }
    // }}}
    // {{{ testDocumentForHistory
    public function testDocumentForHistory($xml) {
        parent::testDocumentForHistory($xml);

        $xmlnav = new \Depage\Cms\XmlNav();
        $xmlnav->addUrlAttributes($xml);
    }
    // }}}

    // {{{ fs()
    /**
     * @brief fs
     *
     * @param mixed
     * @return void
     **/
    protected function fs()
    {
        return \Depage\Fs\Fs::factory($this->project->getProjectPath() . "lib/");
    }
    // }}}
    // {{{ clearGraphicsCache()
    /**
     * @brief clearGraphicsCache
     *
     * @param mixed $path
     * @return void
     **/
    protected function clearGraphicsCache($path)
    {
        $cachePath = $this->project->getProjectPath() . "lib/cache/";
        if (is_dir($cachePath)) {
            // remove thumbnails from cache inside of project if available
            $cache = \Depage\Cache\Cache::factory("graphics", [
                'cachepath' => $cachePath,
            ]);
            $cache->delete("lib/" . $path);
        }

        // remove thumbnails from global graphics cache
        $cache = \Depage\Cache\Cache::factory("graphics");
        $cache->delete("projects/" . $this->project->name . "/lib/" . $path);
    }
    // }}}
    // {{{ getPathById()
    /**
     * @brief getPathById
     *
     * @param mixed $
     * @return void
     **/
    protected function getPathById($nodeId, $added = "")
    {
        $url = $this->document->getAttribute($nodeId, 'name');
        if (!empty($added)) {
            $url .= "/$added";
        }

        while (($nodeId = $this->document->getParentIdById($nodeId)) != null) {
            $url = $this->document->getAttribute($nodeId, 'name') . "/" . $url;
        }

        return trim($url, '/');
    }
    // }}}
    // {{{ getParentUrl()
    /**
     * @brief getParentUrl
     *
     * @param mixed $
     * @return void
     **/
    protected function getParentUrl($node)
    {
        $url = "";

        $nodeId = (int) $node->getAttributeNS("http://cms.depagecms.net/ns/database", "id");
        $parentId = $this->document->getParentIdById($nodeId);
        $url = $this->getPathById($parentId);

        if (!empty($url)) {
            $url = "/$url/";
        }
        if ($node->nodeName == "proj:folder" && empty($url)) {
            $url = "/";
        }

        return $url;
    }
    // }}}

    // {{{ loadXmlTemplate()
    /**
     * Load XML Template
     *
     * @param $template
     * @return \DOMDocument
     */
    private function loadXmlTemplate($template) {
        $doc = new \DOMDocument();
        $doc->load(self::XML_TEMPLATE_DIR . $template);
        return $doc;
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */