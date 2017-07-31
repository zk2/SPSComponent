<?php

namespace Zk2\SpsComponent\Condition;


class Condition implements ConditionInterface
{
    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var string
     */
    private $platform;

    /**
     * @var string
     */
    private $fullTextSearchMode;

    /**
     * @var array
     */
    private $data = [
        self::PROPERTY_OPERATOR_NAME => null,
        self::TYPE_OPERATOR_NAME => null,
        self::COMPARISON_OPERATOR_NAME => null,
        self::VALUE_OPERATOR_NAME => null,
        self::FUNCTION_OPERATOR_NAME => null,
    ];

    /**
     * Condition constructor.
     * @param string $platform
     */
    public function __construct($platform)
    {
        $this->platform = $platform;
    }

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
        return self::COMPARISON_OPERATORS[$this->data[self::COMPARISON_OPERATOR_NAME]];
    }

    /**
     * @param int $number
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
     * @return array|string
     */
    public function getFunction()
    {
        return $this->data[self::FUNCTION_OPERATOR_NAME];
    }

    /**
     * @param array $data
     * @throws ContainerException
     */
    public function setData(array $data)
    {
        $baseParameterName = null;
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->data)) {
                throw new ContainerException(sprintf('Property %s not exists in %s', $key, self::class));
            } elseif (self::COMPARISON_OPERATOR_NAME === $key and !isset(self::COMPARISON_OPERATORS[$value])) {
                throw new ContainerException(sprintf('Comparison operator "%s" not supported', $value));
            }

            if (self::PROPERTY_OPERATOR_NAME === $key) {
                $baseParameterName = ':'.str_replace(['(', ')', '.'], ['', '', '_'], $value);
            }
            $this->data[$key] = $value;
        }

        if (in_array($data[self::COMPARISON_OPERATOR_NAME], [self::TOKEN_BETWEEN, self::TOKEN_NOT_BETWEEN])) {
            $value = $data[self::VALUE_OPERATOR_NAME];
            if (!is_array($value)) {
                throw new ContainerException('The value must be an array');
            } elseif (2 !== count($value)) {
                throw new ContainerException('The value must contain an array of two elements');
            }
            $i = 0;
            foreach ($data[self::VALUE_OPERATOR_NAME] as $datum) {
                $this->parameters[$baseParameterName.'_'.$i] = $datum;
                $i++;
            }
        } else {
            $this->parameters[$baseParameterName] = $data[self::VALUE_OPERATOR_NAME];
        }

        if (in_array($data[self::COMPARISON_OPERATOR_NAME], [self::TOKEN_MATCHES, self::TOKEN_NOT_MATCHES])) {
            if (isset($data[self::SEARCH_MODE_NAME])) {
                $this->fullTextSearchMode = $data[self::SEARCH_MODE_NAME];
            } else {
                switch ($this->platform) {
                    case 'postgresql':
                        $this->fullTextSearchMode = 'english';
                        break;
                    case 'mysql':
                        $this->fullTextSearchMode = 'IN NATURAL MODE';
                        break;
                }
            }
        }
    }

    /**
     * @return string
     * @throws ContainerException
     */
    public function buildCondition()
    {
        if (in_array($this->data[self::COMPARISON_OPERATOR_NAME], [self::TOKEN_IS_NULL, self::TOKEN_IS_NOT_NULL])) {

            return sprintf('%s %s', $this->getProperty(), $this->getComparisonOperator());
        }

        if (null === $this->data[self::VALUE_OPERATOR_NAME]) {

            return '';
        }

        $property = $this->getProperty();
        $operator = $this->data[self::COMPARISON_OPERATOR_NAME];

        if ($function = $this->getFunction()) {
            if (!is_array($function)) {
                $function = [$function];
            }
            $functionName = array_shift($function);
            $parameters = $function ? ','.implode(',', $function) : null;

            $property = sprintf('%s(%s%s)', $functionName, $this->getProperty(), $parameters);
        }

        if (1 === count($this->parameters)) {
            $format = '%s %s %s';
            $parameter = key($this->parameters);

            switch ($operator) {
                case self::TOKEN_IN:
                case self::TOKEN_NOT_IN:
                    $format = '%s %s(%s)';
                    break;
                case self::TOKEN_BEGINS_WITH:
                case self::TOKEN_NOT_BEGINS_WITH:
                    $this->parameters[$parameter] = $this->parameters[$parameter].'%';
                    break;
                case self::TOKEN_ENDS_WITH:
                case self::TOKEN_NOT_ENDS_WITH:
                    $this->parameters[$parameter] = '%'.$this->parameters[$parameter];
                    break;
                case self::TOKEN_CONTAINS:
                case self::TOKEN_NOT_CONTAINS:
                    $this->parameters[$parameter] = '%'.$this->parameters[$parameter].'%';
                    break;
                case self::TOKEN_MATCHES:
                case self::TOKEN_NOT_MATCHES:
                    if ('postgresql' == $this->platform) {
                        $format =
                            self::FULL_TEXT_SEARCH
                            ."(%s,%s%s, '".$this->fullTextSearchMode."') = "
                            .($operator == self::TOKEN_MATCHES ? 'TRUE' : 'FALSE');
                    } elseif ('mysql' == $this->platform) {
                        $format =
                            self::FULL_TEXT_SEARCH
                            ."(%s,%s%s '".$this->fullTextSearchMode."') != 0";
                    } else {
                        $this->parameters[$parameter] = '%'.$this->parameters[$parameter].'%';
                        $this->data[self::COMPARISON_OPERATOR_NAME] = self::TOKEN_CONTAINS;
                    }
                    break;
            }

            return sprintf($format, $property, $this->getComparisonOperator(), $parameter);

        } elseif (in_array($operator, [self::TOKEN_BETWEEN, self::TOKEN_NOT_BETWEEN])) {
            return sprintf(
                '%s %s %s',
                $property,
                $this->getComparisonOperator(),
                implode(' AND ', array_keys($this->parameters))
            );
        }

        return '';
    }

    /**
     * @return array|null
     */
    public function getCustomFunction()
    {
        if (isset(self::CUSTOM_FUNCTIONS[$this->data[self::COMPARISON_OPERATOR_NAME]])) {

            return self::CUSTOM_FUNCTIONS[$this->data[self::COMPARISON_OPERATOR_NAME]];
        }

        return null;
    }
}