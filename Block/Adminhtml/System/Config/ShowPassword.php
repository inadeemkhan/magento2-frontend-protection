<?php

namespace DevScripts\Password\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ShowPassword extends Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setType('password');
        $html = $element->getElementHtml();
        $html .= '<div style="margin-top:5px;">';
        $html .= '<input type="checkbox" id="' . $element->getHtmlId() . '_toggle" ';
        $html .= 'onclick="togglePasswordVisibility(this, \'' . $element->getHtmlId() . '\')" />';
        $html .= ' <label for="' . $element->getHtmlId() . '_toggle">Show Password</label>';
        $html .= '</div>';
        $html .= '<script>
            function togglePasswordVisibility(checkbox, inputId) {
                var input = document.getElementById(inputId);
                if (checkbox.checked) {
                    input.type = "text";
                } else {
                    input.type = "password";
                }
            }
        </script>';
        return $html;
    }
}