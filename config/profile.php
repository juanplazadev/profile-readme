<?php

declare(strict_types=1);

/*
 * Everything personal about the profile lives here. Edit freely, then run:
 *   docker compose run --rm profile render   (redraw from saved data)
 *   docker compose run --rm profile          (fetch + render + readme)
 *
 * Seeded from github.com/juanplazadev and juanplaza.dev. Lines marked TODO are guesses.
 */

use JuanPlaza\Profile\Config\Header;
use JuanPlaza\Profile\Config\Link;
use JuanPlaza\Profile\Config\LinkIcon;
use JuanPlaza\Profile\Config\ProfileConfig;
use JuanPlaza\Profile\Config\Project;
use JuanPlaza\Profile\Config\StackCategory;
use JuanPlaza\Profile\Svg\Color;

return new ProfileConfig(
    githubUser: 'juanplazadev',
    devUser: null,                 // set to your dev.to username to add DEV tiles and the writing section
    header: new Header(
        name: 'JUAN PLAZA',
        barLeft: 'SYS://JUANPLAZA.DEV // NODE:JUAN',
        barRight: 'ONLINE · ALL SYSTEMS NOMINAL',
        lines: [
            'software engineer · 8+ years · laravel / react',
            'production systems end to end: defense, healthcare, logistics',
            'HIPAA · SOX · FIPS 140-3 — writing at ',
        ],
        highlight: 'JUANPLAZA.DEV',
        title: 'Juan Plaza',
        description: 'Software engineer, 8+ years. Laravel and React. I own production systems end to end — defense, healthcare, logistics. HIPAA, SOX, FIPS 140-3.',
    ),           // an int adds a "hackathon wins" row to the stats panel

    links: [
        new Link(LinkIcon::GitHub, 'GitHub', '@juanplazadev', 'https://github.com/juanplazadev', 'github'),
        new Link(LinkIcon::LinkedIn, 'LinkedIn', 'in/juan-plaza', 'https://www.linkedin.com/in/juan-plaza-59a6a9296', 'linkedin'),
        new Link(LinkIcon::Website, 'Website', 'juanplaza.dev', 'https://juanplaza.dev', 'website'),
        new Link(LinkIcon::Email, 'Email', 'juan@juanplaza.dev', 'mailto:juan@juanplaza.dev', 'email'),
    ],

    // 880 must divide evenly by the count: 4, 5, 8 or 10 buttons
    projects: [
        new Project(
            slug: 'check-in-v2',
            name: 'Check-In',
            url: 'https://github.com/juanplazadev/check-in-v2',
            tag: 'LIVE DEMO',
            tagColor: Color::Green,
            stack: 'Laravel · Inertia + React · PostgreSQL',
            desc: 'Appointment scheduling and driver check-in for multi-site operations, on Redis and Horizon queues.',
        ),
        new Project(
            slug: 'juanplazadev',
            name: 'juanplaza.dev',
            url: 'https://github.com/juanplazadev/juanplazadev',
            tag: 'PORTFOLIO',
            tagColor: Color::Magenta,
            stack: 'Laravel 13 · Octane · React 19',
            desc: 'Personal site on FrankenPHP: Inertia v3 + React 19 with SSR, plus a first-party operations console.',
        ),
        new Project(
            slug: 'phinx',
            name: 'Phinx DB2 Adapter',
            url: 'https://github.com/juanplazadev/phinx/tree/feature/db2-adapter',
            tag: 'OPEN SOURCE',
            tagColor: Color::Green,
            stack: 'PHP · pdo_odbc · IBM i',
            desc: 'IBM DB2 for i (AS400/iSeries) migration adapter for cakephp/phinx, over pdo_odbc.',
        ),
        new Project(
            slug: 'discord-show-reminder',
            name: 'Show Reminder',
            url: 'https://github.com/juanplazadev/discord-show-reminder',
            tag: 'SIDE PROJECT',            // TODO
            tagColor: Color::Magenta,
            stack: 'PHP · Discord',          // TODO
            desc: 'A Discord bot that reminds your server when a show is about to air.',   // TODO: guessed, the repo has no description
        ),
    ],

    // two per row; descriptions wrap at 43 characters, three lines max
    stack: [
        new StackCategory('languages', ['PHP', 'TypeScript', 'JavaScript', 'Python', 'SQL']),
        new StackCategory('frameworks', ['Laravel', 'Inertia', 'React', 'Octane']),
        new StackCategory('databases', ['PostgreSQL', 'MySQL', 'Redis', 'IBM DB2']),
        new StackCategory('testing', ['Pest', 'PHPUnit', 'Playwright']),
        new StackCategory('compliance', ['HIPAA', 'SOX', 'FIPS 140-3']),
    ],

    hackathonWins: null,

    userAgent: 'juanplazadev-profile-updater',
);
