<?php

declare(strict_types=1);

namespace User\Admin\RequestHandler;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use User\Repository\UserRepositoryInterface;

final class ToggleUserActiveHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id   = (int) $request->getAttribute('id');
        $user = $this->users->findById($id);

        if ($user === null) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        $this->users->update($id, [
            'store_id' => $user->storeId,
            'role_id'  => $user->roleId,
            'name'     => $user->name,
            'email'    => $user->email,
            'active'   => $user->active ? 0 : 1,
        ]);

        return new JsonResponse(['active' => ! $user->active]);
    }
}
