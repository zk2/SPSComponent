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

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Container
 */
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
     *
     * @return self
     *
     * @throws ContainerException
     */
    public static function create($data)
    {
        if (!$data) {
            return new self(self::COLLECTION_NAME);
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
        $mainContainer = new self($type);
        if (isset($data[self::AND_OR_OPERATOR_NAME])) {
            $mainContainer->setAndOr($data[self::AND_OR_OPERATOR_NAME]);
        }
        if (self::COLLECTION_NAME === $type) {
            foreach ($data[$type] as $datum) {
                $mainContainer->addToCollection(self::create($datum));
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
     *
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
     *
     * @param string $type
     */
    private function __construct($type)
    {
        $this->type = (string) $type;
        if (self::COLLECTION_NAME === $type) {
            $this->collectionOfConditions = new ArrayCollection();
        } else {
            $this->condition = new Condition();
        }
    }

    /**
     * @param string $andOr
     *
     * @return self
     *
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
