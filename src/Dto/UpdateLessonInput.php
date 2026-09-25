<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final readonly class UpdateLessonInput
{
    public function __construct(
        #[Assert\NotBlank(
            allowNull: true,
            message: 'Title is required',
            normalizer: 'trim',
        )]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Title cannot be longer than {{ limit }} characters',
        )]
        public ?string $title = null,

        #[Assert\Positive(
            message: 'Position must be greater than zero',
        )]
        public ?int $position = null,
    ) {
    }

    #[Assert\Callback]
    public function validateAtLeastOneField(
        ExecutionContextInterface $context,
    ): void {
        if ($this->title === null && $this->position === null) {
            $context
                ->buildViolation('At least one field is required')
                ->addViolation();
        }
    }
}
