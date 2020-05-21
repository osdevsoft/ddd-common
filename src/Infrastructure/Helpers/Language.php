<?php

namespace Osds\DDDCommon\Infrastructure\Helpers;

class Language
{

    public static function isMultilanguageField($field, $languages)
    {
        return
            is_array($field)
            && array_keys($field) === $languages
            ;
    }

}