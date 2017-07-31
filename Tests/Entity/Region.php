<?php

namespace Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Region
 *
 * @ORM\Table(
 *     name="region",
 *     indexes={
 *         @ORM\Index(name="region_name_idx", columns={"name"})
 *     }
 * )
 * @ORM\Entity()
 */
class Region
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name",  type="string", length=255)
     */
    private $name;

    /**
     * @var Country[]
     *
     * @ORM\OneToMany(targetEntity="Country", mappedBy="region", cascade={"persist"}, indexBy="id")
     */
    private $countries;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countries = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Region
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add country
     *
     * @param Country $country
     *
     * @return Region
     */
    public function addCountry(Country $country)
    {
        $this->countries[] = $country;

        return $this;
    }

    /**
     * Remove country
     *
     * @param Country $country
     */
    public function removeCountry(Country $country)
    {
        $this->countries->removeElement($country);
    }

    /**
     * Get countries
     *
     * @return Country[]
     */
    public function getCountries()
    {
        return $this->countries;
    }
}
