<?php
/**
 * @file    document.php
 *
 * @author    Frank Hellenkamp [jonas@depagecms.net]
 */

namespace depage\xml;

/**
 * @brief DOMDocument
 *
 * Serializable subclass of DOMDocument with helper methods especially
 * for html-content, and for removing up unwanted tags from html.
 */
class Document extends \DOMDocument implements \Serializable
{
    public $content_type = "text/xml";
    public $charset = "UTF-8";

    // {{{ constructor()
    /**
     * @brief   htmldom class constructor
     *
     * @param   $version (string)
     * @param   $encoding (string) 
     *
     * @return  (depage::htmlform::abstracts::htmldom) htmlDOM
     **/
    public function __construct($version = null, $encoding = null) {
        if (is_null($version)) {
            $version = "1.0";
        }
        if (is_null($encoding)) {
            $encoding = "UTF-8";
        }
        parent::__construct($version, $encoding);
    }
    // }}}
    // {{{ serialize()
    /**
     * @brief   serializes htmldom into string
     *
     * @return  (string) xml-content saved by saveXML()
     **/
    public function serialize(){
        return $this->saveXML();
    }
    // }}}
    // {{{ unserialize()
    /**
     * @brief   unserializes htmldom-objects
     *
     * @param   $serialized (string)
     *
     * @return  (void)
     **/
    public function unserialize($serialized) {
        $this->loadXML($serialized);
    }
    // }}}
    
    // {{{ __toString()
    /**
     * @brief   unserializes htmldom-objects
     *
     * @param   $serialized (string)
     *
     * @return  (void)
     **/
    public function __toString() {
        return $this->saveXML();
    }
    // }}}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker et : */