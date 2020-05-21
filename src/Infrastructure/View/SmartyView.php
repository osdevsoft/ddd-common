<?php

namespace Osds\DDDCommon\Infrastructure\View;

use Osds\DDDCommon\Infrastructure\Tools;
use Twig\TwigFilter;

class SmartyView extends AbstractView
{
    private $templateSystem;

    public function __construct($smarty)
    {
        $this->templateSystem = $smarty;
        $this->loadFilters();
    }


    public function createTemplate($templatePath, $templateName)
    {
        return $this->templateSystem->createTemplate($templatePath, $templateName);
    }

    public function render($return = false)
    {

        $template = $this->templateSystem->render(
            $this->getTemplate(),
            $this->getVariables()
        );
        if($return) {
            return $template;
        }
        die($template);
    }

    private function loadFilters()
    {
        $this->templateSystem->addFilter(new TwigFilter(
            'bolder',
            function ($string) {
                return '<b>' . $string . '</b>';
            }
        ));
        $this->templateSystem->addFilter(new TwigFilter(
            'dump',
            function ($var) {
                dd($var);
            }
        ));
        $this->templateSystem->addFilter(new TwigFilter(
            'getLocalizedValue',
            function ($field, $multiLanguageFields, $value, $language) {
                if(in_array($field, $multiLanguageFields)) {
                    #it's a multilanguage field
                    if(!is_array($value)) {
                        $multilanguage_value = @json_decode($value, true);
                    } else {
                        $multilanguage_value = $value;
                    }
                    if($multilanguage_value !== null) {
                        if(isset($multilanguage_value[$language])) {
                            return $multilanguage_value[$language];
                        } else {
                            return reset($multilanguage_value);
                        }
                    }
                }
                return $value;


            }
        ));
        $this->templateSystem->addFilter(new TwigFilter(
            'contains',
            function ($field, $string) {
                return strpos($field, $string);
            }
        ));
        $this->templateSystem->addFilter(new TwigFilter(
            'getFieldFromArray',
            function ($array, $field) {
                $wantedValues = [];
                foreach($array as $key => $value) {
                    $wantedValues[] = $value[$field];
                }
                return $wantedValues;
            }
        ));
        $this->templateSystem->addFilter(new TwigFilter(
            'in_array',
            function ($needle, $haystack) {
                return in_array($needle, $haystack);
            }
        ));

    }
}