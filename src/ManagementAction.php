<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit;

enum ManagementAction: string
{
    case Read = 'read';
    case Update = 'update';
}
