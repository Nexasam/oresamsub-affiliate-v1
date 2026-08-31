<?php

it('presents the interactive network story in the resellgrid hero', function () {
    $hero = file_get_contents(resource_path('views/resellgrid/landing.blade.php'));

    expect($hero)
        ->toContain('Your VTU business shouldn’t stop at one website.')
        ->toContain('Turn it into a network.')
        ->toContain('data-hero-ecosystem')
        ->toContain('data-hero-event')
        ->toContain('<b>One</b> catalogue')
        ->toContain('<b>Multiple</b> brands')
        ->toContain('<b>Central</b> control');
});
