<?php

namespace Template\DefaultTemplate;

use Template\EzpzTmpl;
use Template\Model\Context;
use Template\Model\Tmpl;
use Template\Util\CompilerUtil;

class CompileLiteral
{
    /**
     * @param Context  $context
     * @param \DOMText $node
     */
    public static function process(\DOMText &$node, Context $context, Tmpl $tmpl, EzpzTmpl $engine)
    {
        if (isset($node->data))
        {
            $data = trim($node->data);

            if (strlen($data))
            {
                if (substr($data, 0, 2) === ApiAttrs::TAG_EZPZ_OPEN && $data[strlen($data)-1] === ApiAttrs::TAG_EZPZ_CLOSE)
                {
                    $data = str_replace(array(ApiAttrs::TAG_EZPZ_OPEN, ApiAttrs::TAG_EZPZ_CLOSE), '', $data);
                    $first = $data[0];
                    $last = $data[strlen($data)-1];

                    $parts = explode('@', trim($data));
                    if (sizeof($parts) > 1) {
                        $data = trim($parts[0]);
                        $matches = array();
                        preg_match('/context=\'(.*)\'/', $parts[1], $matches);
                        if (!empty($matches) && isset($matches[1])) {
                            if ($context->has($data)) {
                                $node->data = $context->get($data);
                            }
                            else {
                                $node->data = ApiAttrs::TAG_HB_OPEN . '{' . $data . '}' . ApiAttrs::TAG_HB_CLOSE;
                            }
                        }
                        else if (strpos($parts[1], 'i18n')) {
                            $parts[1] = preg_replace('/i18n(.*),\s+locale=/', '', trim($parts[1]));
                            $parts[1]=trim($parts[1], '\'\s');
                            $data = 'i18n.'.$parts[1].'.'.str_replace(array('\'', ' '), '', $parts[0]);
                            $val = CompilerUtil::getVarValue($context, explode('.', $data));
                            $node->data = $val;
                        }
                    }
                    else if (($first === "'" && $last === "'") || ($first === '"' && $last === '"') ||
                        is_bool($data) || is_numeric($data) || $data === 'true' || $data === 'false'
                    ) {
                        $node->data = ApiAttrs::TAG_HB_OPEN . ('echo ' . $data) . ApiAttrs::TAG_HB_CLOSE;
                    }
                    else if ($first === "[" && $last === ']') {
                        $node->data = ApiAttrs::TAG_HB_OPEN . ('echo "' . str_replace(array('[',']'), '', $data).'"') . ApiAttrs::TAG_HB_CLOSE;
                    }
                    else if ($context->has($data)) {
                        $node->data = $context->get($data);
                    }
                    else {
                        $data = str_replace(
                            array(ApiAttrs::EZPZ_LIST_ITEM),
                            array(ApiAttrs::HB_LIST_ITEM),
                            $data
                        );
                        $node->data = ApiAttrs::TAG_HB_OPEN . $data . ApiAttrs::TAG_HB_CLOSE;
                    }
                }
            }
        }
    }
}