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

use Doctrine\ORM\Mapping as ORM;

/**
 * City
 *
 * @ORM\Table(
 *     name="city",
 *     indexes={
 *         @ORM\Index(name="city_name_idx", columns={"name"}),
 *         @ORM\Index(name="city_population_idx", columns={"population"})
 *     }
 * )
 * @ORM\Entity()
 */
class City
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
     * @var string
     *
     * @ORM\Column(name="district",  type="string", length=255)
     */
    private $district;

    /**
     * @var int
     *
     * @ORM\Column(name="population",  type="integer", options={"default": 0})
     */
    private $population;

    /**
     * @var string
     *
     * @ORM\Column(name="last_date",  type="datetime")
     */
    private $lastDate;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country", inversedBy="cities", cascade={"persist"})
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $country;

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
     * @return City
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
     * Set district
     *
     * @param string $district
     *
     * @return City
     */
    public function setDistrict($district)
    {
        $this->district = $district;

        return $this;
    }

    /**
     * Get district
     *
     * @return string
     */
    public function getDistrict()
    {
        return $this->district;
    }

    /**
     * Set population
     *
     * @param integer $population
     *
     * @return City
     */
    public function setPopulation($population)
    {
        $this->population = $population;

        return $this;
    }

    /**
     * Get population
     *
     * @return integer
     */
    public function getPopulation()
    {
        return $this->population;
    }

    /**
     * @return string
     */
    public function getLastDate()
    {
        return $this->lastDate;
    }

    /**
     * @param string $lastDate
     *
     * @return City
     */
    public function setLastDate($lastDate)
    {
        $this->lastDate = $lastDate;

        return $this;
    }

    /**
     * Set country
     *
     * @param Country $country
     *
     * @return City
     */
    public function setCountry(Country $country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return Country
     */
    public function getCountry()
    {
        return $this->country;
    }
}
