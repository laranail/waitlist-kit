<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Enums;

/** The sections a page is built from. Every theme renders every one (its own view or the kit's base). */
enum BlockType: string
{
    case Hero = 'hero';
    case Form = 'form';
    case Features = 'features';
    case Countdown = 'countdown';
    case Faq = 'faq';
    case SocialProof = 'social_proof';
    case Contact = 'contact';
    case Text = 'text';
    case Footer = 'footer';
}
