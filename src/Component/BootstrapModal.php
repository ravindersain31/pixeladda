<?php

namespace App\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(
    template: 'components/bootstrap_modal.html.twig',
)]
class BootstrapModal
{
    public ?string $id = null;
}
