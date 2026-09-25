<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Every view a Blade file names must exist.
 *
 * A missing @include target or anonymous component is not a degraded render - it
 * is a fatal ViewException, so the whole page 500s. Both instances found when
 * this test was written had exactly that effect on /faculty_dashboard:
 *
 *   <x-menu.material_management />   referenced by admin/layouts/sidebar/material,
 *                                   which faculty/layouts/sidebar includes. The
 *                                   component was added on the unmerged branch
 *                                   main_ui_new and never reached main.
 *   @include('admin.layouts.aside')  in faculty/layouts/master. That view was
 *                                   deleted in 21682a447, which folded its 764
 *                                   lines into admin/layouts/header (+744) and
 *                                   did not update this caller.
 *
 * Both are the same shape: a view was moved or never merged, and the reference to
 * it was left behind on a page nobody opened. Neither is visible until the page is
 * requested, and neither shows up in a diff of the branch that broke it.
 *
 * @includeIf is excluded on purpose - its whole contract is that the view may be
 * absent.
 */
class BladeViewReferencesResolveTest extends TestCase
{
    /**
     * An anonymous component tag. Segments may be snake_case or kebab-case - both
     * are in use here (<x-menu.material_management />, <x-menu.fc-sidebar />) -
     * so a hyphen must be allowed, or every kebab-named tag fails to match and is
     * never examined at all.
     */
    private const COMPONENT_TAG = '/<x-([a-z0-9_-]+(?:\.[a-z0-9_-]+)*)[\s\/>]/i';

    /** Blade's own tags, which are not components with a view file. */
    private const BUILT_IN_TAGS = ['slot', 'dynamic-component'];

    public function test_every_view_named_by_a_blade_file_exists(): void
    {
        $pattern = '/@(include|includeWhen|includeUnless|includeFirst|extends|component)\s*\(\s*[\'"]([A-Za-z0-9_.\-\/]+)[\'"]/';

        // Prove both detectors fire before trusting them to find nothing, on every
        // naming form the view tree uses.
        $this->assertSame(
            1,
            preg_match($pattern, "@include('admin.layouts.aside')"),
            'the view-reference detector no longer matches a known @include'
        );

        foreach ([
            '<x-menu.material_management />' => 'menu.material_management',
            '<x-menu.fc-sidebar />' => 'menu.fc-sidebar',
            '<x-datatable-chrome />' => 'datatable-chrome',
            '<x-data-table.table>' => 'data-table.table',
            '<x-breadcrum title="x">' => 'breadcrum',
        ] as $tag => $expected) {
            $this->assertSame(1, preg_match(self::COMPONENT_TAG, $tag, $m), 'the component detector does not match '.$tag);
            $this->assertSame($expected, $m[1], 'the component detector misreads the name in '.$tag);
        }

        // Alpine attributes are not tags and must not be read as components.
        $this->assertSame(0, preg_match(self::COMPONENT_TAG, '<div x-data="{ open: false }" x-show="open">'));

        $missing = [];

        foreach ($this->bladeFiles() as $path) {
            $source = preg_replace('/\{\{--.*?--\}\}/s', '', file_get_contents($path)) ?? '';
            $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

            preg_match_all($pattern, $source, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                if (! $this->viewExists($match[2])) {
                    $missing[] = sprintf('%s  ->  @%s(\'%s\')', $relative, $match[1], $match[2]);
                }
            }

            // Anonymous components under resources/views/components.
            preg_match_all(self::COMPONENT_TAG, $source, $components);

            foreach (array_unique($components[1]) as $component) {
                if (in_array(strtolower($component), self::BUILT_IN_TAGS, true)) {
                    continue;
                }

                if (! $this->componentExists($component)) {
                    $missing[] = sprintf('%s  ->  <x-%s />', $relative, $component);
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            "These Blade files name a view or component that does not exist. Each one is a fatal\n"
            ."ViewException - the whole page 500s - and it only shows when that page is opened:\n  "
            .implode("\n  ", $missing)
        );
    }

    private function viewExists(string $name): bool
    {
        $path = resource_path('views'.DIRECTORY_SEPARATOR.str_replace('.', DIRECTORY_SEPARATOR, $name));

        return is_file($path.'.blade.php') || is_file($path.'.php');
    }

    /**
     * Anonymous components resolve to resources/views/components/<path>.blade.php.
     * A class-based component has no such file, so one that resolves through the
     * container is accepted too rather than reported as missing.
     */
    private function componentExists(string $name): bool
    {
        if ($this->viewExists('components.'.$name)) {
            return true;
        }

        $studly = implode('\\', array_map(
            fn ($segment) => str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $segment))),
            explode('.', $name)
        ));

        return class_exists('App\\View\\Components\\'.$studly)
            || class_exists('App\\Http\\Livewire\\'.$studly);
    }

    /** @return list<string> */
    private function bladeFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        $this->assertNotEmpty($files, 'no Blade files were found, so this test would pass vacuously');

        return $files;
    }
}
