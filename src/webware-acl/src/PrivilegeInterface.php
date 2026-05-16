<?php

declare(strict_types=1);

namespace Webware\Acl;

interface PrivilegeInterface
{
    public final const string READ   = 'read';
    public final const string CREATE = 'create';
    public final const string UPDATE = 'update';
    public final const string DELETE = 'delete';

    public function getPrivilegeId(): string;
}
