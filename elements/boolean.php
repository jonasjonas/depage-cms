<?php 

namespace depage\htmlform\elements;

use depage\htmlform\abstracts;

/**
 * HTML single checkbox input type.
 **/
class boolean extends abstracts\input {
    /**
     * @param $name input elements' name
     * @param $parameters array of input element parameters, HTML attributes, validator specs etc.
     * @param $formName name of the parent HTML form. Used to identify the element once it's rendered.
     **/
    public function __construct($name, $parameters, $formName) {
        parent::__construct($name, $parameters, $formName);
        
        // boolean-elements have values of type boolean
        $this->defaultValue = (isset($parameters['defaultValue'])) ? $parameters['defaultValue'] : (bool) false;
        $this->errorMessage = (isset($parameters['errorMessage'])) ? $parameters['errorMessage'] : 'Please check this box if you want to procede!';
    }

    /**
     * Renders element to HTML.
     *
     * @return string of HTML rendered element
     **/
    public function __toString() {
        $selected = ($this->htmlValue() === true) ? " checked=\"yes\"" : '';

        return "<p id=\"$this->formName-$this->name\" class=\"" . $this->htmlClasses() . "\">" .
            "<label>" .
                "<input type=\"checkbox\" name=\"$this->name\"" . $this->htmlInputAttributes() . " value=\"true\"$selected>" .
                "<span class=\"label\">" . $this->label . $this->htmlRequiredChar() . "</span>" .
            "</label>" .
            $this->htmlErrorMessage() .
        "</p>\n";
    }

    /**
     * Overrides inputClass::validate(). Checks if the value the current input
     * element holds is valid according to it's validator object. 
     * 
     * In case of boolean value has to be true if field is required.
     * 
     * @return $this->valid
     **/
    public function validate() {
        if (!$this->validated) {
            $this->validated = true;

            $this->valid = (($this->value !== null)
                && ($this->validator->validate($this->value) || $this->isEmpty())
                && ($this->value || !$this->required)
            );
        }

        return $this->valid;
    }

    /**
     * Sets the current input elements value. Converts it to boolean if
     * necessary.
     *
     * @param $newValue contains the new value
     * @return $this->value converted value
     **/
    public function setValue($newValue) {
        if (is_bool($newValue)) {
            $this->value = $newValue;
        } else if ($newValue === "true") {
            $this->value = true;
        } else {
            $this->value = false;
        }

        return $this->value;
    }

    /**
     * Converts value to element specific type. (in this case setValue does
     * all the work, so this is mainly for coherence)
     **/
    protected function typeCastValue() {
        $this->value = (bool) $this->value;
    }
}
