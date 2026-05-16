<?php

declare(strict_types=1);

namespace Webware\UserManager;

use Laminas\Permissions\Acl\ProprietaryInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\Permissions\Acl\Role\RoleInterface;
use Mezzio\Authentication\UserInterface as MezzioUserInterface;

interface UserInterface extends
    MezzioUserInterface,
    RoleInterface,
    ResourceInterface,
    ProprietaryInterface 
{
    public function isGuest(): bool;
}
