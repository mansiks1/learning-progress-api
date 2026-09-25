<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateCourseInput
{
    public function __construct(
        #[Assert\NotBlank(
            message: 'Title is required',
            normalizer: 'trim',
        )]
        #[Assert\Length(
            max: 255,
            maxMessage: 'Title cannot be longer than {{ limit }} characters',
        )]
        public string $title,
    ) {
    }
}
