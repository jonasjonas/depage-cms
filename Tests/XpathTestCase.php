<?php

namespace Depage\XmlDb\Tests;

abstract class XpathTestCase extends DatabaseTestCase
{
    protected $xmldb;
    protected $cache;
    protected $doc;
    protected $testObject;

    // {{{ setUp
    protected function setUp()
    {
        parent::setUp();

        $this->cache = \Depage\Cache\Cache::factory('xmldb', array('disposition' => 'uncached'));
        $this->xmldb = new XmlDbTestClass($this->pdo->prefix . '_proj_test', $this->pdo, $this->cache, array(
            'root',
            'child',
        ));
        $this->doc = new DocumentTestClass($this->xmldb, 5);
    }
    // }}}

    // {{{ getNodeIdsByDomXpath
    protected function getNodeIdsByDomXpath($doc, $xpath)
    {
        $ids = array();

        $domXpath = new \DomXpath($doc->getXml());
        $list = $domXpath->query($xpath);
        foreach ($list as $item) {
            $ids[] = $item->attributes->getNamedItem('id')->nodeValue;
        }

        return $ids;
    }
    // }}}
    // {{{ assertCorrectXpathIds
    protected function assertCorrectXpathIds(array $expectedIds, $xpath, $domTest = true, $fallbackCalled = false)
    {
        if ($domTest) {
            $domXpathIds = $this->getNodeIdsByDomXpath($this->testObject, $xpath);
            sort($domXpathIds);
            $this->assertEquals($expectedIds, $domXpathIds, "Failed asserting that expected IDs match DOMXPath query node IDs. Is the test set up correctly for XPath query $xpath ?");
        }

        $actualIds = $this->testObject->getNodeIdsByXpath($xpath);
        sort($actualIds);
        $this->assertEquals($expectedIds, $actualIds, "Failed asserting that ID arrays match for XPath query $xpath");

        $this->assertSame($fallbackCalled, $this->xmldb->fallbackCall);
    }
    // }}}

    // {{{ testNoResult
    public function testNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/nonode');
    }
    // }}}

    // {{{ testGetSubDocByXpathByName
    public function testGetSubDocByXpathByName()
    {
        $subDoc = $this->testObject->getSubDocByXpath('//pg:folder');

        $expected = '<pg:folder xmlns:db="http://cms.depagecms.net/ns/database" xmlns:dpg="http://www.depagecms.net/ns/depage" xmlns:edit="http://www.depagecms.net/ns/edit" xmlns:pg="http://www.depagecms.net/ns/page" xmlns:sec="http://www.depagecms.net/ns/section" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" name="F1.3" db:id="13" db:lastchange="2016-02-03 16:09:05" db:lastchangeUid=""/>';

        $this->assertXmlStringEqualsXmlString($expected, $subDoc);
    }
    // }}}
    // {{{ testGetSubDocByXpathNone
    public function testGetSubDocByXpathNone()
    {
        $this->assertFalse($this->testObject->getSubDocByXpath('//iamnosubdoc'));
    }
    // }}}

    // {{{ testNameChild
    public function testNameChild()
    {
        $this->assertCorrectXpathIds(array(2), '/dpg:pages/pg:page');
    }
    // }}}
    // {{{ testNameChildAndPositionShort
    public function testNameChildAndPositionShort()
    {
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[3]');
    }
    // }}}
    // {{{ testNameChildAndPositionShortNoResult
    public function testNameChildAndPositionShortNoResult()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page[2]');
    }
    // }}}
    // {{{ testNameChildAndPositionShortMultiple
    public function testNameChildAndPositionShortMultiple()
    {
        $this->assertCorrectXpathIds(array(12), '/dpg:pages/pg:page[1]/pg:page[2]');
    }
    // }}}
    // {{{ testNameChildAndPositionParsing
    public function testNameChildAndPositionParsing()
    {
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[position() = 3]');
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[position()= 3]');
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[position() =3]');
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[position()=3]');
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[position()   =   3]');
        $this->assertCorrectXpathIds(array(11), '/dpg:pages/pg:page/pg:page/pg:page[  position()   =   3  ]');
    }
    // }}}
    // {{{ testNameChildAndPosition
    public function testNameChildAndPosition()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page/pg:page[position() = 13]');
    }
    // }}}
    // {{{ testNameChildAndPositionNot
    public function testNameChildAndPositionNot()
    {
        $this->assertCorrectXpathIds(array(7, 8, 11), '/dpg:pages/pg:page/pg:page/pg:page[position() != 13]');
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[position() != 3]');
    }
    // }}}
    // {{{ testNameChildAndPositionLessThan
    public function testNameChildAndPositionLessThan()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page/pg:page/pg:page[position() < 1]');
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[position() < 3]');
    }
    // }}}
    // {{{ testNameChildAndPositionGreaterThan
    public function testNameChildAndPositionGreaterThan()
    {
        $this->assertCorrectXpathIds(array(), '/dpg:pages/pg:page/pg:page/pg:page[position() > 3]');
        $this->assertCorrectXpathIds(array(8, 11), '/dpg:pages/pg:page/pg:page/pg:page[position() > 1]');
    }
    // }}}
    // {{{ testNameChildAndPositionLessThanOrEqualTo
    public function testNameChildAndPositionLessThanOrEqualTo()
    {
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[position() <= 2]', false);
    }
    // }}}
    // {{{ testNameChildAndPositionGreaterThanOrEqualTo
    public function testNameChildAndPositionGreaterThanOrEqualTo()
    {
        $this->assertCorrectXpathIds(array(8, 11), '/dpg:pages/pg:page/pg:page/pg:page[position() >= 2]', false);
    }
    // }}}

    // {{{ testNameAndAttribute
    public function testNameAndAttribute()
    {
        $this->assertCorrectXpathIds(array(7, 8, 11), '/dpg:pages/pg:page/pg:page/pg:page[@name]');
    }
    // }}}
    // {{{ testNameAndAttributeValue
    public function testNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(8), '/dpg:pages/pg:page/pg:page/pg:page[@name = \'P1.1.2\']');
    }
    // }}}
    // {{{ testNameAndAttributeAndOperatorValue
    public function testNameAndAttributeAndOperatorValue()
    {
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[@file_type = \'html\']');
        $this->assertCorrectXpathIds(array(7), '/dpg:pages/pg:page/pg:page/pg:page[@file_type = \'html\' and @multilang = \'true\']');
        $this->assertCorrectXpathIds(array(7), '/dpg:pages/pg:page/pg:page/pg:page[@file_type = \'html\' AND @multilang = \'true\']');
    }
    // }}}
    // {{{ testNameAndAttributeOrOperatorValue
    public function testNameAndAttributeOrOperatorValue()
    {
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[@file_type = \'html\']');
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[@file_type = \'html\' or @multilang = \'true\']');
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/pg:page[@file_type = \'html\' OR @multilang = \'true\']');
    }
    // }}}

    // {{{ testWildcardAndAttributeValue
    public function testWildcardAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/*[@file_type = \'html\']');
    }
    // }}}
    // {{{ testWildcardNsAndAttributeValue
    public function testWildcardNsAndAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath >= 2.0
        $this->assertCorrectXpathIds(array(7, 8), '/dpg:pages/pg:page/pg:page/*:page[@file_type = \'html\']', false);
    }
    // }}}
    // {{{ testWildcardNameAndAttributeValue
    public function testWildcardNameAndAttributeValue()
    {
        $this->assertCorrectXpathIds(array(6, 9), '/dpg:pages/pg:page/pg:*[@name = \'Subpage\']');
    }
    // }}}

    // {{{ testAllName
    public function testAllName()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8), '//pg:page');
    }
    // }}}
    // {{{ testAllNameMultiple
    public function testAllNameMultiple()
    {
        $this->assertCorrectXpathIds(array(9), '//pg:page/pg:folder');
    }
    // }}}
    // {{{ testAllNameAttribute
    public function testAllNameAttribute()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8), '//pg:page[@name]');
    }
    // }}}
    // {{{ testAllNameAttributeValue
    public function testAllNameAttributeValue()
    {
        $this->assertCorrectXpathIds(array(8), '//pg:page[@name = \'bla blub\']');
    }
    // }}}
    // {{{ testAllNameAttributeNoResult
    public function testAllNameAttributeNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@unknown]');
    }
    // }}}
    // {{{ testAllNameAttributeValueNoResult
    public function testAllNameAttributeValueNoResult()
    {
        $this->assertCorrectXpathIds(array(), '//pg:page[@name = \'unknown\']');
    }
    // }}}
    // {{{ testAllNameAndPosition
    public function testAllNameAndPosition()
    {
        $this->assertCorrectXpathIds(array(8), '//pg:page[3]');
    }
    // }}}
    // {{{ testAllNameAndPositionMultiple
    public function testAllNameAndPositionMultiple()
    {
        $this->assertCorrectXpathIds(array(2, 6), '//pg:page[1]');
    }
    // }}}

    // {{{ testAllWildCardNs
    public function testAllWildCardNs()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIds(array(2, 6, 7, 8), '//*:page', false);
    }
    // }}}
    // {{{ testAllWildCardName
    public function testAllWildCardName()
    {
        $this->assertCorrectXpathIds(array(2, 6, 7, 8, 9), '//pg:*');
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValue
    public function testAllWildCardAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(6), '//*[@db:id = \'6\']', false);
    }
    // }}}
    // {{{ testAllWildCardNsAndIdAttributeValue
    public function testAllWildCardNsAndIdAttributeValue()
    {
        // can't be verified by DOMXpath (XPath 1.0). Namespace wildcards are XPath => 2.0
        $this->assertCorrectXpathIds(array(6), '//*:page[@db:id = \'6\']', false);
    }
    // }}}
    // {{{ testAllWildCardNameAndIdAttributeValue
    public function testAllWildCardNameAndIdAttributeValue()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(6), '//pg:*[@db:id = \'6\']', false);
    }
    // }}}
    // {{{ testAllWildCardAndIdAttributeValueNoResult
    public function testAllWildCardAndIdAttributeValueNoResult()
    {
        // no domxpath, ids are subject to database domain
        $this->assertCorrectXpathIds(array(), '//*[@db:id = \'20\']', false);
    }
    // }}}

    // {{{ testAllLast
    public function testAllLast()
    {
        $this->assertCorrectXpathIds(array(2, 8), '//pg:page[last()]', true, true);
    }
    // }}}
    // {{{ testAllLessThan
    public function testAllLessThan()
    {
        $this->assertCorrectXpathIds(array(2, 6), '//pg:page[@db:dataid < \'5\']', true, true);
    }
    // }}}
    // {{{ testArbitraryDescendants
    public function testArbitraryDescendants()
    {
        $this->assertCorrectXpathIds(array(6, 7, 8), '/dpg:pages//pg:page/pg:page', true, true);
    }
    // }}}
}
