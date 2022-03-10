<?php
namespace Mezon;

/**
 * Class Filter
 *
 * @package Filter
 * @subpackage Filter
 * @author Dodonov A.A.
 * @version v.1.0 (2019/09/15)
 * @copyright Copyright (c) 2020, http://aeon.su
 */

/**
 * Class for compiling filter statement
 */
class Filter
{

    /**
     * Method returns simple operator
     *
     * @param array $item
     *            expression item with operator
     * @return string operator
     */
    protected static function getOperator(array $item): string
    {
        $item['op'] = strtolower($item['op']);

        $operators = array(
            '<',
            '>',
            '<=',
            '>=',
            '=',
            'like',
            '!=',
            'and',
            'or',
            'in'
        );

        if (in_array($item['op'], $operators)) {
            return $item['op'];
        }

        throw (new \Exception('Invalid operator ' . $item['op']));
    }

    /**
     * Method returns argument
     *
     * @param string|array|int|float $arg
     *            argument name orr value
     * @param mixed $op
     *            pperator
     * @return string argument
     */
    protected static function getArg($arg, $op = false): string
    {
        if (is_array($arg) && $op === 'in') {
            $result = '( ' . implode(' , ', $arg) . ' )';
        } elseif (is_array($arg)) {
            $result = '( ' . self::getStatement($arg) . ' )';
        } elseif (is_string($arg) && strpos($arg, '$') === 0) {
            $result = substr($arg, 1);
        } else {
            if (is_numeric($arg)) {
                $result = '' . $arg;
            } else {
                $result = "'" . $arg . "'";
            }
        }
        return $result;
    }

    /**
     * Method compiles statement
     *
     * @param array $item
     *            expression
     * @return string compiled expression
     */
    protected static function getStatement(array $item): string
    {
        $statement = self::getArg($item['arg1']);

        $statement .= ' ' . self::getOperator($item) . ' ';

        return $statement . self::getArg($item['arg2'], self::getOperator($item));
    }

    /**
     * Complex where compilation
     *
     * @param array $arr
     *            list of structured expressions
     * @param array $where
     *            list of compiled conditions
     * @return array new list of compiled conditons
     */
    protected static function compileWhere(array $arr, array $where): array
    {
        foreach ($arr as $item) {
            $where[] = self::getStatement($item);
        }

        return $where;
    }

    /**
     * Method adds where condition
     *
     * @param array $arr
     *            array of fields to be fetched
     * @param array $where
     *            conditions
     * @return array conditions
     */
    public static function addFilterConditionFromArr(array $arr, array $where): array
    {
        $firstElement = array_slice($arr, - 1);
        $firstElement = array_pop($firstElement);

        if (count($arr) && is_array($firstElement)) {
            return self::compileWhere($arr, $where);
        }

        // simple filter construction
        foreach ($arr as $field => $value) {
            if (is_numeric($value)) {
                $where[] = htmlspecialchars($field) . ' = ' . $value;
            } elseif ($value == 'null') {
                $where[] = htmlspecialchars($field) . ' IS NULL';
            } elseif ($value == 'not null') {
                $where[] = htmlspecialchars($field) . ' IS NOT NULL';
            } else {
                $where[] = htmlspecialchars($field) . ' LIKE "' . htmlspecialchars($value) . '"';
            }
        }

        return $where;
    }

    /**
     * Method adds where condition
     *
     * @param array $where
     *            conditions
     * @return array conditions
     */
    public static function addFilterCondition(array $where): array
    {
        if (! isset($_GET['filter'])) {
            return $where;
        }

        return self::addFilterConditionFromArr($_GET['filter'], $where);
    }
}
