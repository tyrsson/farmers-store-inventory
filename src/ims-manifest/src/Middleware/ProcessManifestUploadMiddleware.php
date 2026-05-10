<?php

declare(strict_types=1);

/**
 * This file is part of the Webware Farmers Store Inventory package.
 *
 * Copyright (c) 2026 Joey Smith <jsmith@webinertia.net>
 * and contributors.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ims\Manifest\Middleware;

use Axleus\Message\SystemMessengerInterface;
use DateTimeImmutable;
use Ims\Manifest\Csv\ManifestCsvParser;
use Ims\Manifest\Repository\ManifestRepositoryInterface;
use Mezzio\Authentication\UserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Webware\Acl\Admin\WriteResult;
use Webware\Core\HttpMethodProcessorTrait;
use Webware\UserManager\Entity\User;

use function file_exists;
use function is_string;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const UPLOAD_ERR_OK;

final class ProcessManifestUploadMiddleware implements MiddlewareInterface
{
    use HttpMethodProcessorTrait;

    public function __construct(
        private readonly ManifestRepositoryInterface $manifests,
        private readonly ManifestCsvParser $parser,
    ) {}

    public function processPost(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        /** @var UserInterface&User $user */
        $user = $request->getAttribute(UserInterface::class);

        /** @var SystemMessengerInterface|null $messenger */
        $messenger = $request->getAttribute(SystemMessengerInterface::class);

        $uploadedFiles = $request->getUploadedFiles();
        $file          = $uploadedFiles['manifest_csv'] ?? null;

        if (! $file instanceof UploadedFileInterface || $file->getError() !== UPLOAD_ERR_OK) {
            $messenger?->danger('No file was uploaded or the upload failed. Please try again.');
            return $handler->handle(
                $request->withAttribute(WriteResult::Failure->value, true)
            );
        }

        // Optional received date override supplied by the user
        $body            = $request->getParsedBody();
        $receivedDateRaw = is_string($body['received_date'] ?? null) ? $body['received_date'] : '';
        $receivedDate    = $receivedDateRaw !== ''
            ? DateTimeImmutable::createFromFormat('Y-m-d', $receivedDateRaw) ?: null
            : null;

        $tmpPath = sprintf('%s/%s.csv', sys_get_temp_dir(), uniqid('manifest_', true));
        $success = false;
        $manifestId = null;

        try {
            $file->moveTo($tmpPath);
            $parsed = $this->parser->parse($tmpPath, $receivedDate);

            if ($parsed->items === []) {
                $messenger?->danger(
                    'The CSV contained no importable items. '
                    . 'Check that the file is a DC truck manifest and is not empty.'
                );
                return $handler->handle(
                    $request->withAttribute(WriteResult::Failure->value, true)
                );
            }

            $manifestId = $this->manifests->insertFromCsv($parsed, $user->id);
            $messenger?->success(
                sprintf('Manifest imported — %d items added.', count($parsed->items))
            );
            $success = true;
        } catch (RuntimeException $e) {
            $messenger?->danger($e->getMessage());
        } finally {
            if (file_exists($tmpPath)) {
                unlink($tmpPath);
            }
        }

        return $handler->handle(
            $request
                ->withAttribute(WriteResult::Success->value, $success)
                ->withAttribute('manifest_id', $manifestId)
        );
    }
}
