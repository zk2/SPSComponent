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
 * Country
 *
 * @ORM\Table(
 *     name="country",
 *     indexes={
 *         @ORM\Index(name="country_name_idx", columns={"name"}),
 *         @ORM\Index(name="country_code_idx", columns={"code"}),
 *         @ORM\Index(name="country_population_idx", columns={"population"})
 *     }
 * )
 * @ORM\Entity()
 */
class Country
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
     * @ORM\Column(name="code",  type="string")
     */
    private $code;

    /**
     * @var float
     *
     * @ORM\Column(name="surface_area",  type="decimal", precision=10, scale=2)
     */
    private $surfaceArea;

    /**
     * @var int
     *
     * @ORM\Column(name="population",  type="integer", options={"default": 0})
     */
    private $population;

    /**
     * @var float
     *
     * @ORM\Column(name="life_expectancy",  type="decimal", precision=3, scale=1)
     */
    private $lifeExpectancy;

    /**
     * @var string
     *
     * @ORM\Column(name="local_name", nullable=true,  type="string", length=64)
     */
    private $localName;

    /**
     * @var string
     *
     * @ORM\Column(name="government_form", nullable=true,  type="string", length=64)
     */
    private $governmentForm;

    /**
     * @var City
     *
     * @ORM\ManyToOne(targetEntity="City", cascade={"persist"})
     * @ORM\JoinColumn(name="capital_city_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $capital;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_date", type="datetime")
     */
    private $lastDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_green",  type="boolean", options={"default": false})
     */
    private $isGreen;

    /**
     * @var string
     *
     * @ORM\Column(name="fts",  type="tsvector", nullable=true, length=255)
     */
    private $fts;

    /**
     * @var Continent
     *
     * @ORM\ManyToOne(targetEntity="Continent", inversedBy="countries", cascade={"persist"})
     * @ORM\JoinColumn(name="continent_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $continent;

    /**
     * @var Region
     *
     * @ORM\ManyToOne(targetEntity="Region", inversedBy="countries", cascade={"persist"})
     * @ORM\JoinColumn(name="region_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    private $region;

    /**
     * @var City[]
     *
     * @ORM\OneToMany(targetEntity="City", mappedBy="country", cascade={"persist"})
     */
    private $cities;

    /**
     * @var CountryLanguage[]
     *
     * @ORM\OneToMany(targetEntity="CountryLanguage", mappedBy="country", cascade={"persist"})
     */
    private $languages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cities = new ArrayCollection();
        $this->languages = new ArrayCollection();
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
     * @return Country
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
     * Set code
     *
     * @param string $code
     *
     * @return Country
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set surfaceArea
     *
     * @param string $surfaceArea
     *
     * @return Country
     */
    public function setSurfaceArea($surfaceArea)
    {
        $this->surfaceArea = $surfaceArea;

        return $this;
    }

    /**
     * Get surfaceArea
     *
     * @return string
     */
    public function getSurfaceArea()
    {
        return $this->surfaceArea;
    }

    /**
     * Set population
     *
     * @param integer $population
     *
     * @return Country
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
     * Set lifeExpectancy
     *
     * @param string $lifeExpectancy
     *
     * @return Country
     */
    public function setLifeExpectancy($lifeExpectancy)
    {
        $this->lifeExpectancy = $lifeExpectancy;

        return $this;
    }

    /**
     * Get lifeExpectancy
     *
     * @return string
     */
    public function getLifeExpectancy()
    {
        return $this->lifeExpectancy;
    }

    /**
     * Set localName
     *
     * @param string $localName
     *
     * @return Country
     */
    public function setLocalName($localName)
    {
        $this->localName = $localName;

        return $this;
    }

    /**
     * Get localName
     *
     * @return string
     */
    public function getLocalName()
    {
        return $this->localName;
    }

    /**
     * Set governmentForm
     *
     * @param string $governmentForm
     *
     * @return Country
     */
    public function setGovernmentForm($governmentForm)
    {
        $this->governmentForm = $governmentForm;

        return $this;
    }

    /**
     * Get governmentForm
     *
     * @return string
     */
    public function getGovernmentForm()
    {
        return $this->governmentForm;
    }

    /**
     * Set capital
     *
     * @param City $capital
     *
     * @return Country
     */
    public function setCapital(City $capital)
    {
        $this->capital = $capital;

        return $this;
    }

    /**
     * Get capital
     *
     * @return City
     */
    public function getCapital()
    {
        return $this->capital;
    }

    /**
     * @return \DateTime
     */
    public function getLastDate()
    {
        return $this->lastDate;
    }

    /**
     * @param string $lastDate
     *
     * @return Country
     */
    public function setLastDate($lastDate)
    {
        $this->lastDate = $lastDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isGreen()
    {
        return $this->isGreen;
    }

    /**
     * @param bool $isGreen
     *
     * @return Country
     */
    public function setIsGreen($isGreen)
    {
        $this->isGreen = $isGreen;

        return $this;
    }

    /**
     * Set fts
     *
     * @param string $fts
     *
     * @return Country
     */
    public function setFts($fts)
    {
        $this->fts = $fts;

        return $this;
    }

    /**
     * Get fts
     *
     * @return string
     */
    public function getFts()
    {
        return $this->fts;
    }

    /**
     * Set continent
     *
     * @param Continent $continent
     *
     * @return Country
     */
    public function setContinent(Continent $continent)
    {
        $this->continent = $continent;

        return $this;
    }

    /**
     * Get continent
     *
     * @return Continent
     */
    public function getContinent()
    {
        return $this->continent;
    }

    /**
     * Set region
     *
     * @param Region $region
     *
     * @return Country
     */
    public function setRegion(Region $region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return Region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Add city
     *
     * @param City $city
     *
     * @return Country
     */
    public function addCity(City $city)
    {
        $this->cities[] = $city;

        return $this;
    }

    /**
     * Remove city
     *
     * @param City $city
     */
    public function removeCity(City $city)
    {
        $this->cities->removeElement($city);
    }

    /**
     * Get cities
     *
     * @return City[]
     */
    public function getCities()
    {
        return $this->cities;
    }

    /**
     * Add language
     *
     * @param CountryLanguage $language
     *
     * @return Country
     */
    public function addLanguage(CountryLanguage $language)
    {
        $this->languages[] = $language;

        return $this;
    }

    /**
     * Remove language
     *
     * @param CountryLanguage $language
     */
    public function removeLanguage(CountryLanguage $language)
    {
        $this->languages->removeElement($language);
    }

    /**
     * Get languages
     *
     * @return CountryLanguage[]
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * @return int
     */
    public function getCountCities()
    {
        return $this->cities->count();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'              => $this->id,
            'continent'       => $this->continent->getName(),
            'region'          => $this->region->getName(),
            'name'            => $this->name,
            'code'            => $this->code,
            'surface_area'    => $this->surfaceArea,
            'population'      => $this->population,
            'life_expectancy' => $this->lifeExpectancy,
            'local_name'      => $this->localName,
            'government_form' => $this->governmentForm,
            'capital'         => $this->capital ? $this->capital->getName() : null,
            'last_date'       => $this->lastDate ? $this->lastDate->format('c') : null,
        ];
    }
}
