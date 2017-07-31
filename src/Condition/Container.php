<?php

namespace Zk2\SpsComponent\Condition;


use Doctrine\Common\Collections\ArrayCollection;

class Container implements ContainerInterface
{
    /**
     * @var string
     */
    private $andOr;

    /**
     * @var string
     */
    private $type;

    /**
     * @var Condition
     */
    private $condition;

    /**
     * @var ArrayCollection|self[]
     */
    private $collectionOfConditions;

    /**
     * @param array $data
     * @param string $platform
     * @return self
     * @throws ContainerException
     */
    public static function create($data, $platform)
    {
        if (!$data) {

            return new self(self::COLLECTION_NAME, $platform);
        }

        $type = null;
        if (isset($data[self::COLLECTION_NAME])) {
            $type = self::COLLECTION_NAME;
        } elseif (isset($data[self::CONDITION_NAME])) {
            $type = self::CONDITION_NAME;
        }
        if (null === $type) {
            throw new ContainerException(
                sprintf('Invalid container type. Use %s', implode(' or ', self::ALLOWED_TYPES))
            );
        }
        if (!is_array($data[$type])) {
            throw new ContainerException(sprintf('Parameter "%s" must be array', $type));
        }
        $mainContainer = new self($type, $platform);
        if (isset($data[self::AND_OR_OPERATOR_NAME])) {
            $mainContainer->setAndOr($data[self::AND_OR_OPERATOR_NAME]);
        }
        if (self::COLLECTION_NAME === $type) {
            foreach ($data[$type] as $datum) {
                $mainContainer->addToCollection(self::create($datum, $platform));
            }
        } elseif (self::CONDITION_NAME === $type) {
            $mainContainer->getCondition()->setData($data[$type]);
        }

        return $mainContainer;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getAndOr()
    {
        return $this->andOr;
    }

    /**
     * @return ArrayCollection|Container[]
     */
    public function getCollectionOfConditions()
    {
        return $this->collectionOfConditions;
    }

    /**
     * @param Container $container
     * @return self
     */
    public function addToCollection(Container $container)
    {
        if (!$this->collectionOfConditions->contains($container)) {
            $this->collectionOfConditions[] = $container;
        }

        return $this;
    }

    /**
     * @return Condition
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Instance constructor.
     * @param string $type
     * @param string $platform
     */
    private function __construct($type, $platform)
    {
        $this->type = $type;
        if (self::COLLECTION_NAME == $type) {
            $this->collectionOfConditions = new ArrayCollection();
        } else {
            $this->condition = new Condition($platform);
        }
    }

    /**
     * @param string $andOr
     * @return self
     * @throws ContainerException
     */
    private function setAndOr($andOr)
    {
        if ($andOr = strtoupper($andOr)) {
            if (!in_array($andOr, self::AND_OR_OPERATORS)) {
                throw new ContainerException(
                    sprintf('Invalid operator. Use %s', implode(' or ', self::AND_OR_OPERATORS))
                );
            }
            $this->andOr = $andOr;
        }

        return $this;
    }
}