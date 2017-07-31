<?php

namespace Tests\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CountryLanguage
 *
 * @ORM\Table(
 *     name="country_language",
 *     indexes={
 *         @ORM\Index(name="country_language_lang_idx", columns={"lang"})
 *     }
 * )
 * @ORM\Entity()
 */
class CountryLanguage
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
     * @ORM\Column(name="lang",  type="string", length=32)
     */
    private $lang;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_official",  type="boolean", options={"default": 0})
     */
    private $isOfficial;

    /**
     * @var float
     *
     * @ORM\Column(name="percentage",  type="decimal", precision=4, scale=1, options={"default": "0.0"})
     */
    private $percentage;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country", inversedBy="languages", cascade={"persist"})
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
     * Set lang
     *
     * @param string $lang
     *
     * @return CountryLanguage
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * Set isOfficial
     *
     * @param boolean $isOfficial
     *
     * @return CountryLanguage
     */
    public function setIsOfficial($isOfficial)
    {
        $this->isOfficial = $isOfficial;

        return $this;
    }

    /**
     * Get isOfficial
     *
     * @return boolean
     */
    public function getIsOfficial()
    {
        return $this->isOfficial;
    }

    /**
     * Set percentage
     *
     * @param string $percentage
     *
     * @return CountryLanguage
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * Get percentage
     *
     * @return string
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Set country
     *
     * @param Country $country
     *
     * @return CountryLanguage
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
