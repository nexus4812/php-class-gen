<?php

declare(strict_types=1);

namespace App\Infrastructure\Query\User;

use App\Contracts\Query\User\FindUserByIdQuery;
use App\Contracts\Query\User\FindUserByIdQueryHandler;
use App\Contracts\Query\User\FindUserByIdResult;
use Illuminate\Database\ConnectionInterface;

final readonly class FindUserByIdQueryHandlerImplementation implements FindUserByIdQueryHandler
{
    public function __construct(
        private ConnectionInterface $connection,
    ) {
    }

    /**
     * @inheritdoc
     */
    public function handle(FindUserByIdQuery $query): FindUserByIdResult
    {
        // TODO: Implement query logic (SELECT)
        // Example for single record query:
        // $result = $this->connection->table('users')
        //     ->where('id', $query->id)
        //     ->first();
        //
        // if ($result === null) {
        //     throw new \RuntimeException('User not found');
        // }
        //
        // return new FindUserByIdResult(data: $result);

        // Example for collection query:
        // $results = $this->connection->table('users')
        //     ->where('status', 'active')
        //     ->get();
        //
        // return new FindActiveUsersResult(data: $results);

        throw new \RuntimeException('Not implemented');
    }
}
