<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 23/02/2016
 * Time: 15:57
 */

namespace SimpleThings\Tests\EntityAudit\Fixtures\Issue;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Issue156Contact
 * @package SimpleThings\Tests\EntityAudit\Fixtures\Issue
 * @ORM\Entity()
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 */
class Issue156Contact
{
    /** @var int @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    protected $id;

    /**
     * @var ArrayCollection|Issue156ContactTelephoneNumber[]
     * ORM\OneToMany(targetEntity="Issue156ContactTelephoneNumber", mappedBy="contact")
     */
    protected $telephoneNumbers;

    public function __construct()
    {
        $this->telephoneNumbers = new ArrayCollection();
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Issue156ContactTelephoneNumber $telephoneNumber
     * @return $this
     */
    public function addTelephoneNumber(Issue156ContactTelephoneNumber $telephoneNumber)
    {
        if (!$this->telephoneNumbers->contains($telephoneNumber)) {
            $telephoneNumber->setContact($this);
            $this->telephoneNumbers[] = $telephoneNumber;
        }

        return $this;
    }

    /**
     * @param Issue156ContactTelephoneNumber $telephoneNumber
     * @return $this
     */
    public function removeTelephoneNumber(Issue156ContactTelephoneNumber $telephoneNumber)
    {
        $this->telephoneNumbers->removeElement($telephoneNumber);

        return $this;
    }

    /**
     * @return ArrayCollection|Issue156ContactTelephoneNumber[]
     */
    public function getTelephoneNumbers()
    {
        return $this->telephoneNumbers;
    }
}
