<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class SingleRelationshipType extends Constraint
{
    public $message = 'Activity type can only be related to one type of entity. Conflicting relationships: {{ relationships }}.';
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}