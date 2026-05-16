<?php

declare(strict_types=1);

namespace Ims\Store\Acl;

use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;

interface StoreOwnedResourceInterface extends 
    ProprietaryInterface,
    ResourceInterface
{}
