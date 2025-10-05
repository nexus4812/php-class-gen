<?php

declare(strict_types=1);

namespace App\Contracts\Query\User;

final readonly class FindUserByIdQuery
{
    public function __construct(
        public int $id,
    ) {
        // TODO: Add query parameters as needed
    }
}
