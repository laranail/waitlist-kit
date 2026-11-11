<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Auth;

/**
 * The backend's Gate abilities. `view` opens it; `manage` changes campaigns, blocks, themes, pages and integrations;
 * `export` downloads lists; `erase` removes people for good. An application that defines any of these keeps its own
 * definition: the kit only fills in the ones it finds missing.
 */
final class Abilities
{
    public const string VIEW = 'laranail-waitlist-kit.view';

    public const string MANAGE = 'laranail-waitlist-kit.manage';

    public const string EXPORT = 'laranail-waitlist-kit.export';

    public const string ERASE = 'laranail-waitlist-kit.erase';

    public const array ALL = [self::VIEW, self::MANAGE, self::EXPORT, self::ERASE];
}
