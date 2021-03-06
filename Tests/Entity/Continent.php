<?php
/**
 * This file is part of the SpsComponent package.
 *
 * (c) Evgeniy Budanov <budanov.ua@gmail.comm> 2017.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Continent
 *
 * @ORM\Table(
 *     name="continent",
 *     indexes={
 *         @ORM\Index(name="continent_name_idx", columns={"name"})
 *     }
 * )
 * @ORM\Entity()
 */
class Continent
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
     * @ORM\OneToMany(targetEntity="Country", mappedBy="continent", cascade={"persist"}, indexBy="id")
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
     * @return Continent
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
     * @return Continent
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
