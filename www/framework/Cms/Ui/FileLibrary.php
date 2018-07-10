<?php
/**
 * @file    framework/Cms/Ui/Project.php
 *
 * depage cms ui module
 *
 *
 * copyright (c) 2002-2014 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Cms\Ui;

use \Depage\Html\Html;

class FileLibrary extends Base
{
    // {{{ _init
    public function _init(array $importVariables = []) {
        parent::_init($importVariables);

        $this->projectName = $this->urlSubArgs[0];

        if (empty($this->projectName)) {
            throw new \Depage\Cms\Exceptions\Project("no project given");
        } else {
            $this->project = $this->getProject($this->projectName);
            $this->fs = \Depage\Fs\Fs::factory($this->project->getProjectPath() . "lib/");
        }
    }
    // }}}

    // {{{ index()
    function index() {
        return $this->manager();
    }
    // }}}
    // {{{ manager()
    function manager($path = "") {
        $this->syncLibraryTree();

        // construct template
        $hLib = new Html("projectLibrary.tpl", [
            'projectName' => $this->project->name,
            'tree' => $this->tree(),
            'files' => $this->files($path),
        ], $this->htmlOptions);

        $h = new Html([
            'content' => [
                $hLib,
            ],
        ]);

        return $h;
    }
    // }}}
    // {{{ tree()
    /**
     * @brief tree
     *
     * @param mixed
     * @return void
     **/
    public function tree()
    {
        $treeUrl = "project/{$this->projectName}/tree/files/";
        $uiTree = Tree::_factoryAndInit($this->conf, [
            'urlSubArgs' => [
                $this->projectName,
                "files",
            ],
            'urlPath' => $treeUrl,
            'pdo' => $this->pdo,
        ]);

        return $uiTree->tree();
    }
    // }}}
    // {{{ files()
    /**
     * @brief files
     *
     * @param mixed $path = "/"
     * @return void
     **/
    public function files($path = "")
    {
        $path = rawurldecode($path);
        $form = $this->upload($path);
        $uploadedFiles = $_SESSION['dpLibraryUploadedFiles'];
        $_SESSION['dpLibraryUploadedFiles'] = [];
        $files = $this->fs->lsFiles(trim($path . "/*", '/'));

        return new Html("fileListing.tpl", [
            'form' => $form,
            'uploadedFiles' => $uploadedFiles,
            'path' => $path,
            'fs' => $this->fs,
            'files' => $files,
            'project' => $this->project,
        ], $this->htmlOptions);
    }
    // }}}
    // {{{ upload()
    /**
     * @brief upload
     *
     * @param mixed
     * @return void
     **/
    protected function upload($path = "")
    {
        $targetPath = $this->project->getProjectPath() . "lib/" . $path;

        $form = new \Depage\Cms\Forms\Project\Upload("upload-to-lib", [
            'submitUrl' => DEPAGE_BASE . "project/{$this->project->name}/library/manager/" . rawurlencode($path) . "/",
            'project' => $this->project,
            'targetPath' => $path,
        ]);
        $form->process();
        if ($form->validate()) {
            $values = $form->getValues();

            if (!empty($values['file']) && is_dir($targetPath)) {
                $_SESSION['dpLibraryUploadedFiles'] = [];
                foreach ($values['file'] as $file) {
                    $filename = \Depage\Html\Html::getEscapedUrl($file['name']);
                    rename($file['tmp_name'], $targetPath . "/" . $filename);
                    $_SESSION['dpLibraryUploadedFiles'][] = $path . "/" . $filename;

                    $cachePath = $this->project->getProjectPath() . "lib/cache/";
                    if (is_dir($cachePath)) {
                        // remove thumbnails from cache inside of project if available
                        $cache = \Depage\Cache\Cache::factory("graphics", [
                            'cachepath' => $cachePath,
                        ]);
                        $cache->delete("lib/" . $path . "/" . $filename . ".*");
                    }

                    // remove thumbnails from global graphics cache
                    $cache = \Depage\Cache\Cache::factory("graphics");
                    $cache->delete("projects/" . $this->project->name . "/lib/" . $path . "/" . $filename . ".*");
                }

                $form->clearSession(false);
                die();
            }
        }

        return $form;
    }
    // }}}

    // {{{ syncLibraryTree()
    /**
     * @brief syncLibraryTree
     *
     * @param mixed
     * @return void
     **/
    protected function syncLibraryTree()
    {
        $xmldb = $this->project->getXmlDb();
        $doc = $xmldb->getDoc("files");
        if (!$doc) {
            $doc = $xmldb->createDoc('Depage\Cms\XmlDocTypes\Library', "files");

            $xml = new \Depage\Xml\Document();
            $xml->load(__DIR__ . "/../XmlDocTypes/LibraryXml/library.xml");

            $nodeId = $doc->save($xml);
        }
        $xml = $doc->getXml();

        $this->syncFolder($doc, $xml->documentElement);
    }
    // }}}
    // {{{ syncFolder()
    /**
     * @brief syncFolder
     *
     * @param mixed $path, $folderNode
     * @return void
     **/
    protected function syncFolder($doc, $folderNode, $path = "")
    {
        $pattern = trim($path . "/*", '/');
        $dirs = $this->fs->lsDir($pattern);
        array_walk($dirs, function(&$dir) {
            $dir = pathinfo($dir, \PATHINFO_FILENAME);

        });
        $dirsById = [];

        // check if folder exists
        foreach($folderNode->childNodes as $node) {
            $name = $node->getAttribute("name");
            $id = $doc->getNodeId($node);
            $index = array_search($name, $dirs);

            if ($index === false) {
                // folder does not exist anymore
                $doc->deleteNode($doc->getNodeId($node));
            } else {
                // folder exists
                array_splice($dirs, $index, 1);
                $dirsById[$id] = $name;
            }
        }

        // add unindexed folders
        foreach($dirs as $dir) {
            $parentId = $doc->getNodeId($folderNode);
            $id = $doc->addNodeByName("proj:folder", $parentId, -1, $dir);
            $dirsById[$id] = $dir;
        }

        // index next folder level
        foreach($dirsById as $id => $dir) {
            $xml = $doc->getSubdocByNodeId($id);

            $this->syncFolder($doc, $xml->documentElement, $dir);
        }
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */
