<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zk2\SpsComponent\Condition;

/**
 * Class Condition
 */
class Condition implements ConditionInterface
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var array
     */
    private $data = [
        self::PROPERTY_OPERATOR_NAME   => null,
        self::TYPE_OPERATOR_NAME       => null,
        self::COMPARISON_OPERATOR_NAME => null,
        self::VALUE_OPERATOR_NAME      => null,
        self::SQL_FUNCTION_OPERATOR_NAME   => [
            self::SQL_FUNCTION_OPERATOR_AGGREGATE_NAME  => false,
            self::FUNCTION_OPERATOR_DEFINITION_NAME => null,
        ],
        self::PHP_FUNCTION_OPERATOR_NAME   => [
            self::FUNCTION_OPERATOR_DEFINITION_NAME => null,
        ],
    ];

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->data[self::PROPERTY_OPERATOR_NAME];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->data[self::TYPE_OPERATOR_NAME];
    }

    /**
     * @return string
     */
    public function getComparisonOperator()
    {
        return isset(self::COMPARISON_OPERATORS[$this->data[self::COMPARISON_OPERATOR_NAME]])
            ? self::COMPARISON_OPERATORS[$this->data[self::COMPARISON_OPERATOR_NAME]]
            : null;
    }

    /**
     * @param int $number
     *
     * @return void
     */
    public function reconfigureParameters($number)
    {
        $params = $this->parameters;
        $this->parameters = [];
        foreach ($params as $paramName => $paramValue) {
            $this->parameters[$paramName.$number] = $paramValue;
        }
    }

    /**
     * @return array
     */
    public function getSqlFunction()
    {
        return $this->data[self::SQL_FUNCTION_OPERATOR_NAME];
    }

    /**
     * @return bool
     */
    public function isAggregateFunction()
    {
        return $this->getSqlFunction() && $this->getSqlFunction()[self::SQL_FUNCTION_OPERATOR_AGGREGATE_NAME];
    }

    /**
     * @param array $data
     *
     * @throws ContainerException
     */
    public function setData(array $data)
    {
        $phpFunction = $data[self::PHP_FUNCTION_OPERATOR_NAME][self::FUNCTION_OPERATOR_DEFINITION_NAME] ?? null;
        if ($phpFunction) {
            if (!function_exists($phpFunction)) {
                throw new ContainerException(sprintf('PHP function "%s" not exists', $phpFunction));
            }
            try {
                $data[self::VALUE_OPERATOR_NAME] = call_user_func($phpFunction, $data[self::VALUE_OPERATOR_NAME]);
            } catch (\Exception $e) {
                throw new ContainerException($e->getMessage());
            }
        }
        $baseParameterName = null;
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->data)) {
                throw new ContainerException(sprintf('Property "%s" not exists in "%s"', $key, self::class));
            }

            if (self::SQL_FUNCTION_OPERATOR_NAME === $key && $value) {
                if (!isset($value[self::FUNCTION_OPERATOR_DEFINITION_NAME])) {
                    throw new ContainerException('Function was not defined');
                }
                $value = array_merge($this->data[self::SQL_FUNCTION_OPERATOR_NAME], $value);
            }

            if (self::COMPARISON_OPERATOR_NAME === $key) {
                if (!$value && !isset($data[self::SQL_FUNCTION_OPERATOR_NAME][self::FUNCTION_OPERATOR_DEFINITION_NAME])) {
                    throw new ContainerException('Comparison operator was not defined and Function was not defined');
                }
                if ($value && !isset(self::COMPARISON_OPERATORS[$value])) {
                    throw new ContainerException(sprintf('Comparison operator "%s" not supported', $value));
                }
            }

            if (self::PROPERTY_OPERATOR_NAME === $key) {
                $baseParameterName = ':'.str_replace(['(', ')', ',', ':', '.'], ['', '', '_', '_', '_'], $value);
            }
            $this->data[$key] = $value;
        }

        $value = $data[self::VALUE_OPERATOR_NAME];

        if (in_array($value, ['', null, []], true)) {
            return;
        }

        if (in_array($data[self::COMPARISON_OPERATOR_NAME], [self::TOKEN_BETWEEN, self::TOKEN_NOT_BETWEEN])) {
            $value = $data[self::VALUE_OPERATOR_NAME];
            if (null !== $value) {
                if (!is_array($value)) {
                    throw new ContainerException('The value must be an array');
                }
                if (2 !== count($value)) {
                    throw new ContainerException('The value must contain an array of two elements');
                }
                $i = 0;
                foreach ($data[self::VALUE_OPERATOR_NAME] as $datum) {
                    $this->parameters[$baseParameterName.'_'.$i] = $datum;
                    $i++;
                }
            }
        } else {
            $this->parameters[$baseParameterName] = $data[self::VALUE_OPERATOR_NAME];
        }
    }

    /**
     * @return string
     */
    public function buildCondition()
    {
        if (in_array($this->data[self::COMPARISON_OPERATOR_NAME], [self::TOKEN_IS_NULL, self::TOKEN_IS_NOT_NULL])) {
            $this->parameters = [];

            return sprintf('%s %s', $this->getProperty(), $this->getComparisonOperator());
        }

        if (null === $this->data[self::VALUE_OPERATOR_NAME]) {
            return '';
        }

        $this->prepareValues();

        switch ($this->data[self::COMPARISON_OPERATOR_NAME]) {
            case self::TOKEN_BETWEEN:
            case self::TOKEN_NOT_BETWEEN:
                $parametersByString = implode(array_keys($this->parameters), ' AND ');
                break;
            default:
                $parametersByString = key($this->parameters);
        }

        if ($this->getSqlFunction()[self::FUNCTION_OPERATOR_DEFINITION_NAME]) {
            return $this->getSqlFunctionDefinition($parametersByString);
        }

        return sprintf('%s %s', $this->getProperty(), $this->prepareOperatorAndParameter($parametersByString));
    }

    /**
     * @param string $parameterName
     * @param string $prefix
     *
     * @return string|null
     */
    public function getSqlFunctionDefinition($parameterName, $prefix = '')
    {
        if (!$definition = $this->getSqlFunction()[self::FUNCTION_OPERATOR_DEFINITION_NAME]) {
            return null;
        }
        $definition = str_replace(
            ['{property}', '{value}'],
            [$prefix.$this->getProperty(), $parameterName],
            $definition
        );
        if ($this->getComparisonOperator()) {
            $definition .= $this->prepareOperatorAndParameter($parameterName);
        }

        return $definition;
    }

    /**
     * @param string $parameterName
     *
     * @return string
     */
    private function prepareOperatorAndParameter($parameterName)
    {
        switch ($this->data[self::COMPARISON_OPERATOR_NAME]) {
            case self::TOKEN_IN:
            case self::TOKEN_NOT_IN:
                return sprintf(' %s(%s)', $this->getComparisonOperator(), $parameterName);
            default:
                return sprintf(' %s %s', $this->getComparisonOperator(), $parameterName);
        }
    }

    /**
     * @return array|null
     */
    private function prepareValues()
    {
        switch ($this->data[self::COMPARISON_OPERATOR_NAME]) {
            case self::TOKEN_BEGINS_WITH:
            case self::TOKEN_NOT_BEGINS_WITH:
                return $this->parameters = array_map(
                    function ($val) {
                        return $val.'%';
                    },
                    $this->parameters
                );
            case self::TOKEN_ENDS_WITH:
            case self::TOKEN_NOT_ENDS_WITH:
                return $this->parameters = array_map(
                    function ($val) {
                        return '%'.$val;
                    },
                    $this->parameters
                );
            case self::TOKEN_CONTAINS:
            case self::TOKEN_NOT_CONTAINS:
                return $this->parameters = array_map(
                    function ($val) {
                        return '%'.$val.'%';
                    },
                    $this->parameters
                );
            default:
                return null;
        }
    }
}
