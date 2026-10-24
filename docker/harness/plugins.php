<?php

/*
 * The omnifund/* providers the harness installs beside the core: their tests'
 * namespace, and what their tests need besides (require-dev).
 */
return [
    'github-sponsors' => ['Omnifund\\GitHubSponsors\\Tests\\'],
    'opencollective' => ['Omnifund\\OpenCollective\\Tests\\'],
    'liberapay' => ['Omnifund\\Liberapay\\Tests\\'],
];
