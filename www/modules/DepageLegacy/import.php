<?php
/**
 * @file    framework/cms/ui_base.php
 *
 * base class for cms-ui modules
 *
 *
 * copyright (c) 2011-2012 Frank Hellenkamp [jonas@depagecms.net]
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace DepageLegacy;

class Import
{
    protected $pdo;
    protected $cache;
    protected $xmldb;

    protected $projectName;
    protected $pageIds = array();

    protected $xmlImport;
    protected $xmlSettings;
    protected $xmlNavigation;
    protected $docSettings;
    protected $docNavigation;

    // {{{ constructor
    public function __construct($name, $pdo, $cache)
    {
        $this->projectName = $name;

        $this->pdo = $pdo;
        $this->cache = $cache;
        $this->xmldb = new \depage\xmldb\xmldb("{$this->pdo->prefix}_proj_{$this->projectName}", $this->pdo, $this->cache);
    }
    // }}}
    // {{{ importProject()
    public function importProject($xmlFile)
    {
        $this->loadBackup($xmlFile);

        // @todo test why cleaning leads to constraint error
        $this->cleanDocs();

        $this->getDocs();

        $this->extractNavigation();
        $this->extractPagedata();
        $this->extractSettings();

        $this->saveDocs();

        var_dump($this->pageIds);
        return $this->pageIds;
        return $this->xmlNavigation;
        //return $this->xmlImport;
    }
    // }}}
    
    // {{{ loadBackup()
    public function loadBackup($xmlFile)
    {
        $this->xmlImport = new \depage\xml\Document();
        $this->xmlImport->load($xmlFile);
    }
    // }}}
    
    // {{{ cleanDocs()
    public function cleanDocs()
    {
        $docs = $this->xmldb->getDocuments();

        foreach ($docs as $name => $doc) {
            $this->xmldb->removeDoc($name);
        }
    }
    // }}}
    // {{{ getDocs()
    public function getDocs()
    {
        $this->docNavigation = $this->xmldb->getDoc("pages");
        if (!$this->docNavigation) {
            $this->docNavigation = $this->xmldb->createDoc("pages", "depage\\cms\\xmldoctypes\\pages");
        }

        $this->docSettings = $this->xmldb->getDoc("settings");
        if (!$this->docSettings) {
            // @todo update doctype
            $this->docSettings = $this->xmldb->createDoc("settings", "depage\\xmldb\\xmldoctypes\\base");
        }
    }
    // }}}
    // {{{ removeDbIds()
    public function removeDbIds($xml)
    {
        $xpath = new \DOMXPath($xml);
        $nodelist = $xpath->query("//@db:id");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);

            if ($node->nodeType == XML_ATTRIBUTE_NODE) {
                $node->parentNode->removeAttributeNode($node);
            } else {
                $node->parentNode->removeChild($node);
            }
        }
    }
    // }}}
    // {{{ saveDocs()
    public function saveDocs()
    {
        //$this->docNavigation->save($this->xmlNavigation);
        $this->docSettings->save($this->xmlSettings);
    }
    // }}}
    
    // {{{ extractNavigation()
    public function extractNavigation()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:pages_struct");

        // extract navigation tree
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlNavigation = new \depage\xml\Document();
            $node = $this->xmlNavigation->importNode($nodelist->item($i), true);
            $this->xmlNavigation->appendChild($node);
        }

        $xpath = new \DOMXPath($this->xmlNavigation);
        $nodelist = $xpath->query("//pg:*[@db:id]");

        // save old db:ids
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $node->setAttribute("db:oldid", $node->getAttribute("db:id"));
        }

        $this->docNavigation->save($this->xmlNavigation);

        $xpath = new \DOMXPath($this->xmlNavigation);
        $nodelist = $xpath->query("//pg:*[@db:id]");

        // save db:ids in pageIds
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $node = $nodelist->item($i);
            $this->pageIds[$node->getAttribute("db:oldid")] = $node->getAttribute("db:id");
        }
    }
    // }}}
    // {{{ extractPagedata()
    public function extractPagedata()
    {
        $xpath = new \DOMXPath($this->xmlNavigation);
        $xpathImport = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//*[@db:ref]");

        // loop through pages
        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $pageNode = $nodelist->item($i);
            $dataId = $pageNode->getAttribute("db:ref");
            $pagelist = $xpathImport->query("//*[@db:id = $dataId]");

            // save pagedata
            for ($j = $pagelist->length - 1; $j >= 0; $j--) {
                $xmlData = new \depage\xml\Document();

                $dataNode = $xmlData->importNode($pagelist->item($j), true);
                $xmlData->appendChild($dataNode);
                $docType = $pageNode->localName;
                $docName = '_' . $docType . '_' . sha1(uniqid(dechex(mt_rand(256, 4095))));

                $doc = $this->xmldb->createDoc($docName, "depage\\cms\\xmldoctypes\\$docType");
                $newId = $doc->save($xmlData);

                $pageNode->removeAttribute("db:ref");
                $pageNode->setAttribute("db:docref", $newId);
            }
        }
    }
    // }}}
    // {{{ extractSettings()
    public function extractSettings()
    {
        $xpath = new \DOMXPath($this->xmlImport);
        $nodelist = $xpath->query("//proj:settings");

        for ($i = $nodelist->length - 1; $i >= 0; $i--) {
            $this->xmlSettings = new \depage\xml\Document();
            $node = $this->xmlSettings->importNode($nodelist->item($i), true);
            $this->xmlSettings->appendChild($node);
        }

    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */