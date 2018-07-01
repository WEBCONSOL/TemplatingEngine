<?php

namespace GX2CMS\TemplateEngine\Util;

final class RegexConstants
{
    private function __construct(){}

    const LITERAL = '/\${(.[^}]*)}/';
    const CONTEXT = '/(.*)@(.*)context=\'(.*)\'/';
    const I18N = '/(.*)@(.*)i18n(.*)locale=\'(.*)\'/';
    const TERNARY_VAR = '/\${(.[^}]*)\?(.[^}]*)\:(.[^}]*)}/';
    const TERNARY_VAL = '/(.[^\?]*)\?(.[^\:]*)\:(.*)/';
    const WHITESPACE = '/[\s+\r\n\t]/';
}