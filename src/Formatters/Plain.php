<?php

namespace Gendiff\Formatters\Plain;

use Exception;
use Funct\Collection;

use function Gendiff\Formatters\formatBooleanValue;

use const Gendiff\Formatters\END_OF_LINE;

function format(array $data): string
{
    $iter = function ($node, $prefix = '') use (&$iter) {
        $fullNodeKey = $prefix . $node['key'];
        switch ($node['type']) {
            case 'complex':
                return array_map(fn($item) => $iter($item, "{$fullNodeKey}."), $node['children']);
            case 'added':
                return sprintf(
                    "Property '%s' was added with value: %s",
                    $fullNodeKey,
                    formatValue($node['value'])
                );
            case 'removed':
                return sprintf(
                    "Property '%s' was removed",
                    $fullNodeKey
                );
            case 'changed':
                return sprintf(
                    "Property '%s' was changed. From %s to %s",
                    $fullNodeKey,
                    formatValue($node['old']),
                    formatValue($node['value'])
                );
            case 'kept':
                return [];
            default:
                throw new Exception("Unknown node type: '{$node['type']}'");
        }
    };
    $lines = Collection\flattenAll(array_map(fn($node) => $iter($node), $data));
    return implode(END_OF_LINE, $lines);
}

function formatValue($value): string
{
    $valueType = gettype($value);
    $addQuotes = fn($value) => sprintf("'%s'", $value);
    switch ($valueType) {
        case 'boolean':
            return $addQuotes(formatBooleanValue($value));
        case 'object':
        case 'array':
            return $addQuotes('complex value');
        case 'NULL':
            return 'null';
        case 'integer':
        case 'double':
        case 'string':
            return $addQuotes((string)$value);
        default:
            throw new Exception("Unsupported value type '{$valueType}'");
    }
}
