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
                    $parsedLanguage = json_decode($value, true);
                    if($parsedLanguage !== null) {
                        if(isset($parsedLanguage[$language])) {
                            return $parsedLanguage[$language];
                        } else {
                            return reset($parsedLanguage);
                        }
                    }
                }
                return $value;


            }
        ));

    }
}