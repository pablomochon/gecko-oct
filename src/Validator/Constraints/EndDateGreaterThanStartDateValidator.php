<?php

namespace App\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class EndDateGreaterThanStartDateValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EndDateGreaterThanStartDate) {
            throw new UnexpectedTypeException($constraint, EndDateGreaterThanStartDate::class);
        }

        if (null === $value) {
            return;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $startDate = $propertyAccessor->getValue($value, $constraint->startDateField);
        $endDate = $propertyAccessor->getValue($value, $constraint->endDateField);

        // Skip validation if either date is null
        if (null === $startDate || null === $endDate) {
            return;
        }

        // Ensure both are DateTimeInterface objects
        if (!$startDate instanceof \DateTimeInterface || !$endDate instanceof \DateTimeInterface) {
            return;
        }

        if ($endDate <= $startDate) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}