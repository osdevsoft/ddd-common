<?php

namespace Osds\DDDCommon\Infrastructure\View;

use Osds\DDDCommon\Infrastructure\Tools;

class SmartyView extends AbstractView
{
    private $templateSystem;

    public function __construct($smarty)
    {
        $this->templateSystem = $smarty;
        $this->loadFilters();
    }

    public function render()
    {

        echo $this->templateSystem->render(
            $this->getTemplate() . '.twig',
            $this->getVariables()
        );
        exit;
    }

    private function loadFilters()
    {
        $this->templateSystem->addFilter(new \Twig_Filter(
            'bolder',
            function ($string) {
                return '<b>' . $string . '</b>';
            }
        ));
        $this->templateSystem->addFilter(new \Twig_Filter(
            'dump',
            function ($var) {
                dd($var);
            }
        ));
        $this->templateSystem->addFilter(new \Twig_Filter(
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

    }
}