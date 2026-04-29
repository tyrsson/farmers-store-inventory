<?php

declare(strict_types=1);

namespace User\Command;

use CuyZ\Valinor\Mapper\Http\FromBody;
use Webware\CommandBus\Command\NamedCommandInterface;
use Webware\CommandBus\Command\NamedCommandTrait;

final readonly class SaveUserCommand implements NamedCommandInterface
{
    use NamedCommandTrait;

    /**
     * @param non-empty-string $firstName
     * @param non-empty-string $lastName
     * @param non-empty-string $email
     * @param non-empty-string $password
     * @param positive-int $storeId
     */
    public function __construct(
        #[FromBody] public string $firstName,
        #[FromBody] public string $lastName,
        #[FromBody] public string $email,
        #[FromBody] public string $password,
        #[FromBody] public int $storeId,
    ) {}
}
