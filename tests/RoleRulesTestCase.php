<?php

declare(strict_types=1);

namespace ArtisanToolbox\Permit\Tests;

use Illuminate\Foundation\Application;
use ReflectionProperty;

abstract class RoleRulesTestCase extends AuthorizationTestCase
{
    /** @param Application $app */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app->useAppPath(__DIR__.'/Fixtures/RoleRulesApp');
        (new ReflectionProperty(Application::class, 'namespace'))->setValue($app, __NAMESPACE__.'\\Fixtures\\RoleRulesApp\\');
    }
}
