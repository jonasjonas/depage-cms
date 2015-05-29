<?php

namespace Depage\XmlDb\Tests;

class MockDoctypeHandler
{
    public $isAllowedUnlink = true;
    public $isAllowedAdd = true;

    public function testDocument($xmlDoc)
    {
        return true;
    }
    public function onDeleteNode($id)
    {
    }
    public function onAddNode($id)
    {
    }
    public function isAllowedUnlink($id)
    {
        return $this->isAllowedUnlink;
    }
    public function isAllowedAdd($id)
    {
        return $this->isAllowedAdd;
    }
}

/* vim:set ft=php fenc=UTF-8 sw=4 sts=4 fdm=marker et : */
