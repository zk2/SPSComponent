<?php

namespace Zk2\SpsComponent\Condition;


use Zk2\SpsComponent\Doctrine\FullTextSearchFunction;

interface ConditionInterface
{
    const EQUALS = '=';
    const NOT_EQUALS = '!=';
    const GREATER_THAN = '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const LESS_THAN = '<';
    const LESS_THAN_OR_EQUAL = '<=';
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const LIKE = 'LIKE';
    const NOT_LIKE = 'NOT LIKE';
    const INSTANCE_OF = 'INSTANCE OF';
    const NOT_INSTANCE_OF = 'NOT INSTANCE OF';
    //const LTREE = '~';
    const MATCHES = '';
    const NOT_MATCHES = '';
    const BETWEEN = 'BETWEEN';
    const NOT_BETWEEN = 'NOT BETWEEN';

    const TOKEN_EQUALS = 'equals';
    const TOKEN_NOT_EQUALS = 'notEquals';
    const TOKEN_GREATER_THAN = 'greaterThan';
    const TOKEN_GREATER_THAN_OR_EQUAL = 'greaterThanOrEqual';
    const TOKEN_LESS_THAN = 'lessThan';
    const TOKEN_LESS_THAN_OR_EQUAL = 'lessThanOrEqual';
    const TOKEN_IS_NULL = 'isNull';
    const TOKEN_IS_NOT_NULL = 'isNotNull';
    const TOKEN_IN = 'in';
    const TOKEN_NOT_IN = 'notIn';
    const TOKEN_BEGINS_WITH = 'beginsWith';
    const TOKEN_ENDS_WITH = 'endsWith';
    const TOKEN_CONTAINS = 'contains';
    const TOKEN_NOT_BEGINS_WITH = 'notBeginsWith';
    const TOKEN_NOT_ENDS_WITH = 'notEndsWith';
    const TOKEN_NOT_CONTAINS = 'notContains';
    const TOKEN_INSTANCE_OF = 'instanceOf';
    const TOKEN_NOT_INSTANCE_OF = 'notInstanceOf';
    const TOKEN_MATCHES = 'matches';
    const TOKEN_NOT_MATCHES = 'notMatches';
    const TOKEN_BETWEEN = 'between';
    const TOKEN_NOT_BETWEEN = 'notBetween';

    const COMPARISON_OPERATORS = [
        self::TOKEN_EQUALS => self::EQUALS,
        self::TOKEN_NOT_EQUALS => self::NOT_EQUALS,
        self::TOKEN_GREATER_THAN => self::GREATER_THAN,
        self::TOKEN_GREATER_THAN_OR_EQUAL => self::GREATER_THAN_OR_EQUAL,
        self::TOKEN_LESS_THAN => self::LESS_THAN,
        self::TOKEN_LESS_THAN_OR_EQUAL => self::LESS_THAN_OR_EQUAL,
        self::TOKEN_IS_NULL => self::IS_NULL,
        self::TOKEN_IS_NOT_NULL => self::IS_NOT_NULL,
        self::TOKEN_IN => self::IN,
        self::TOKEN_NOT_IN => self::NOT_IN,
        self::TOKEN_BEGINS_WITH => self::LIKE,
        self::TOKEN_ENDS_WITH => self::LIKE,
        self::TOKEN_CONTAINS => self::LIKE,
        self::TOKEN_NOT_BEGINS_WITH => self::NOT_LIKE,
        self::TOKEN_NOT_ENDS_WITH => self::NOT_LIKE,
        self::TOKEN_NOT_CONTAINS => self::NOT_LIKE,
        self::TOKEN_INSTANCE_OF => self::INSTANCE_OF,
        self::TOKEN_NOT_INSTANCE_OF => self::NOT_INSTANCE_OF,
        self::TOKEN_MATCHES => self::MATCHES,
        self::TOKEN_NOT_MATCHES => self::NOT_MATCHES,
        self::TOKEN_BETWEEN => self::BETWEEN,
        self::TOKEN_NOT_BETWEEN => self::NOT_BETWEEN,
    ];

    const COMPARISON_OPERATOR_NAME = 'comparisonOperator';

    const FUNCTION_OPERATOR_NAME = 'function';

    const PROPERTY_OPERATOR_NAME = 'property';

    const TYPE_OPERATOR_NAME = 'type';

    const VALUE_OPERATOR_NAME = 'value';

    const SEARCH_MODE_NAME = 'searchMode';

    const FULL_TEXT_SEARCH = 'FULL_TEXT_SEARCH';

    const CUSTOM_FUNCTIONS = [
        self::TOKEN_MATCHES => [
            'name' => self::FULL_TEXT_SEARCH,
            'class' => FullTextSearchFunction::class,
            'type' => 'string',
        ],
        self::TOKEN_NOT_MATCHES => [
            'name' => self::FULL_TEXT_SEARCH,
            'class' => FullTextSearchFunction::class,
            'type' => 'string',
        ],
    ];

    /**
     * @return string
     */
    public function getProperty();

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getComparisonOperator();

    /**
     * @param int $number
     * @return void
     */
    public function reconfigureParameters($number);

    /**
     * @return array
     */
    public function getParameters();

    /**
     * @return array|string
     */
    public function getFunction();

    /**
     * @param array $data
     * @throws ContainerException
     */
    public function setData(array $data);

    /**
     * @return string
     * @throws ContainerException
     */
    public function buildCondition();

    /**
     * @return array|null
     */
    public function getCustomFunction();
}