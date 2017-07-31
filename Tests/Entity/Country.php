<?php

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
 *         @ORM\Index(name="country_indep_year_idx", columns={"indep_year"}),
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
     * @ORM\Column(name="code",  type="string", length=3)
     */
    private $code;

    /**
     * @var float
     *
     * @ORM\Column(name="surface_area",  type="decimal", precision=10, scale=2)
     */
    private $surfaceArea;

    /**
     * @var integer
     *
     * @ORM\Column(name="indep_year",  type="smallint", nullable=true)
     */
    private $indepYear;

    /**
     * @var integer
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
     * @var float
     *
     * @ORM\Column(name="gnp",  type="decimal", nullable=true, precision=10, scale=2)
     */
    private $gnp;

    /**
     * @var float
     *
     * @ORM\Column(name="gnp_old",  type="decimal", nullable=true, precision=10, scale=2)
     */
    private $gnpOld;

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
     * @var string
     *
     * @ORM\Column(name="head_of_state", nullable=true,  type="string", length=64)
     */
    private $headOfState;

    /**
     * @var City
     *
     * @ORM\ManyToOne(targetEntity="City", cascade={"persist"})
     * @ORM\JoinColumn(name="capital_city_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $capital;

    /**
     * @var string
     *
     * @ORM\Column(name="code2", nullable=true,  type="string", length=2)
     */
    private $code2;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_date",  type="datetime")
     */
    private $lastDate;

    /**
     * @var string
     *
     * @ORM\Column(name="flag",  type="string", nullable=true)
     */
    private $flag;

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
     * Set indepYear
     *
     * @param integer $indepYear
     *
     * @return Country
     */
    public function setIndepYear($indepYear)
    {
        $this->indepYear = $indepYear;

        return $this;
    }

    /**
     * Get indepYear
     *
     * @return integer
     */
    public function getIndepYear()
    {
        return $this->indepYear;
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
     * Set gnp
     *
     * @param string $gnp
     *
     * @return Country
     */
    public function setGnp($gnp)
    {
        $this->gnp = $gnp;

        return $this;
    }

    /**
     * Get gnp
     *
     * @return string
     */
    public function getGnp()
    {
        return $this->gnp;
    }

    /**
     * Set gnpOld
     *
     * @param string $gnpOld
     *
     * @return Country
     */
    public function setGnpOld($gnpOld)
    {
        $this->gnpOld = $gnpOld;

        return $this;
    }

    /**
     * Get gnpOld
     *
     * @return string
     */
    public function getGnpOld()
    {
        return $this->gnpOld;
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
     * Set headOfState
     *
     * @param string $headOfState
     *
     * @return Country
     */
    public function setHeadOfState($headOfState)
    {
        $this->headOfState = $headOfState;

        return $this;
    }

    /**
     * Get headOfState
     *
     * @return string
     */
    public function getHeadOfState()
    {
        return $this->headOfState;
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
     * Set code2
     *
     * @param string $code2
     *
     * @return Country
     */
    public function setCode2($code2)
    {
        $this->code2 = $code2;

        return $this;
    }

    /**
     * Get code2
     *
     * @return string
     */
    public function getCode2()
    {
        return $this->code2;
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
     * @return string
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * @param string $flag
     *
     * @return Country
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;

        return $this;
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

    public function getCountCities()
    {
        return $this->cities->count();
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'continent' => $this->continent->getName(),
            'region' => $this->region->getName(),
            'name' => $this->name,
            'code' => $this->code,
            'code2' => $this->code2,
            'surface_area' => $this->surfaceArea,
            'indep_year' => $this->indepYear,
            'population' => $this->population,
            'life_expectancy' => $this->lifeExpectancy,
            'gnp' => $this->gnp,
            'gnp_old' => $this->gnpOld,
            'local_name' => $this->localName,
            'government_form' => $this->governmentForm,
            'head_of_state' => $this->headOfState,
            'capital' => $this->capital ? $this->capital->getName() : null,
            'last_date' => $this->lastDate ? $this->lastDate->format('c') : null,
        ];
    }
}
