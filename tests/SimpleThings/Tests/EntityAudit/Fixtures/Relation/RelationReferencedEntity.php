<?php

namespace SimpleThings\Tests\EntityAudit\Fixtures\Relation;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({ "foobar" = "RelationFoobarEntity" })
 */
abstract class RelationReferencedEntity extends RelationAbstractEntityBase
{
    /** @ORM\Column(type="string") */
    protected $referencedField;

    /** @ORM\OneToOne(targetEntity="RelationOneToOneEntity", mappedBy="referencedEntity") */
    protected $oneToOne;

    public function getOneToOne()
    {
        return $this->oneToOne;
    }

    public function setOneToOne($oneToOne)
    {
        $this->oneToOne = $oneToOne;
    }

    public function getReferencedField()
    {
        return $this->referencedField;
    }

    public function setReferencedField($referencedField)
    {
        $this->referencedField = $referencedField;
    }
}
