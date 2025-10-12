<?php

declare(strict_types=1);

namespace App\Contracts\Query\User;

use Illuminate\Container\Attributes\Bind;

#[Bind(FindUserByIdQueryHandlerImplementation::class)]
interface FindUserByIdQueryHandler
{
    /**
     * Execute the query and return the result
     */
    function handle(FindUserByIdQuery $query): FindUserByIdResult;
}
