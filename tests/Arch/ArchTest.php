<?php

test('Dont use dd or dump')
    ->expect(['dd', 'dump'])
    ->not->toBeUsed();
