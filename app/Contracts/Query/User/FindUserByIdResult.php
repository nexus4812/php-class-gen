<?php

declare(strict_types=1);

namespace App\Contracts\Query\User;

final readonly class FindUserByIdResult
{
    /**
     * Result constructor with data
     */
    public function __construct(
        /** Query result data */
        public mixed $data,
    ) {
        // TODO: Add result properties as needed
    }
}
