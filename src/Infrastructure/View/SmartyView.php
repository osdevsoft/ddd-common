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

    }
}