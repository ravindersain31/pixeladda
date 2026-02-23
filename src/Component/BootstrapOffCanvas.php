<?php

namespace App\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    template: 'components/bootstrap_off_canvas.html.twig',
)]
class BootstrapOffCanvas
{
    public ?string $id = null;
}
