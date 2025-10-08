<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class EndDateGreaterThanStartDate extends Constraint
{
    public string $message = 'The end date must be after the start date.';
    public string $startDateField = 'startDate';
    public string $endDateField = 'endDate';

    public function __construct(
        ?string $startDateField = null,
        ?string $endDateField = null,
        ?string $message = null,
    ) {
        parent::__construct();

        $this->startDateField = $startDateField ?? $this->startDateField;
        $this->endDateField = $endDateField ?? $this->endDateField;
        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
