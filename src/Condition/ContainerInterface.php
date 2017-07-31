<?php

namespace Zk2\SpsComponent\Condition;


use Doctrine\Common\Collections\ArrayCollection;

interface ContainerInterface
{
    const CONDITION_NAME = 'condition';

    const COLLECTION_NAME = 'collection';

    const ALLOWED_TYPES = [self::CONDITION_NAME, self::COLLECTION_NAME];

    const AND_OR_OPERATOR_NAME = 'andOrOperator';

    const OPERATOR_AND = 'AND';

    const OPERATOR_OR = 'OR';

    const AND_OR_OPERATORS = [self::OPERATOR_AND, self::OPERATOR_OR];

    /**
     * @param array $data
     * @param string $platform
     * @return self
     * @throws ContainerException
     */
    public static function create($data, $platform);

    /**
     * @return string
     */
    public function getType();

    /**
     * @return string
     */
    public function getAndOr();

    /**
     * @return Condition
     */
    public function getCondition();

    /**
     * @return ArrayCollection|self[]
     */
    public function getCollectionOfConditions();

    /**
     * @param Container $container
     * @return self
     */
    public function addToCollection(Container $container);
}