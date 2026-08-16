<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;

test('every registered controller route resolves to a callable action', function () {
    foreach (Route::getRoutes() as $route) {
        $action = $route->getActionName();

        if ($action === 'Closure') {
            continue;
        }

        [$controllerClass, $method] = str_contains($action, '@')
            ? explode('@', $action, 2)
            : [$action, '__invoke'];

        expect(class_exists($controllerClass))
            ->toBeTrue("Route [{$route->uri()}] references missing controller [{$controllerClass}].");

        if (! class_exists($controllerClass)) {
            continue;
        }

        expect(is_callable([app($controllerClass), $method]))
            ->toBeTrue("Route [{$route->uri()}] references non-callable action [{$action}].");
    }
});

test('route names and method uri pairs are unique', function () {
    $routes = collect(Route::getRoutes());

    $duplicateNames = $routes
        ->pluck('action.as')
        ->filter()
        ->duplicates()
        ->values()
        ->all();

    $duplicateEndpoints = $routes
        ->map(fn ($route): string => implode('|', $route->methods()).' '.$route->uri())
        ->duplicates()
        ->values()
        ->all();

    expect($duplicateNames)->toBe([], 'Duplicate route names were registered.')
        ->and($duplicateEndpoints)->toBe([], 'Duplicate method and URI pairs were registered.');
});

test('every statically rendered inertia component has a page file', function () {
    $sourceFiles = Finder::create()
        ->files()
        ->name('*.php')
        ->in([base_path('routes'), app_path('Http/Controllers')]);

    $components = collect();

    foreach ($sourceFiles as $sourceFile) {
        preg_match_all(
            '/Inertia::render\(\s*[\'\"]([^\'\"]+)[\'\"]/',
            $sourceFile->getContents(),
            $matches,
        );

        $components->push(...$matches[1]);
    }

    $missingPages = $components
        ->unique()
        ->reject(fn (string $component): bool => file_exists(resource_path("js/Pages/{$component}.jsx")))
        ->values()
        ->all();

    expect($components)->not->toBeEmpty()
        ->and($missingPages)->toBe([], 'Inertia components without matching React page files were found.');
});
