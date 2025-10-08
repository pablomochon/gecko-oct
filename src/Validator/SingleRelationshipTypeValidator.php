<?php

namespace App\Validator\Constraints;

use App\Entity\ActivityType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SingleRelationshipTypeValidator extends ConstraintValidator
{
    public function validate($activityType, Constraint $constraint)
    {
        if (!$constraint instanceof SingleRelationshipType) {
            throw new UnexpectedTypeException($constraint, SingleRelationshipType::class);
        }

        if (!$activityType instanceof ActivityType) {
            return;
        }

        // Contar cuántos tipos de relaciones tiene
        $conflictingRelationships = [];

        if ($activityType->getNetworkDevices()->count() > 0) {
            $conflictingRelationships[] = 'NetworkDevice';
        }
        
        if ($activityType->getNetworkVirtualSystems()->count() > 0) {
            $conflictingRelationships[] = 'NetworkVirtualSystem';
        }
        
        if ($activityType->getNetworkInterfaces()->count() > 0) {
            $conflictingRelationships[] = 'NetworkInterface';
        }
        
        if (count($conflictingRelationships) > 1) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ relationships }}', implode(', ', $conflictingRelationships))
                ->addViolation();
        }
    }
}