<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


class ModuleBoundariesTest extends TestCase
{
    public static function rules(): array
    {
        return [
            'Users مستقلة' => ['Users', [
                'App\Modules\Products',
                'App\Modules\Orders',
                'App\Modules\Notifications',
            ]],
            'Products مستقلة' => ['Products', [
                'App\Modules\Users',
                'App\Modules\Orders',
                'App\Modules\Notifications',
            ]],
            'Orders لا تلمس داخل Products ولا Notifications' => ['Orders', [
                'App\Modules\Products\Models',
                'App\Modules\Products\Services',
                'App\Modules\Products\Http',
                'App\Modules\Notifications',
            ]],
            'Notifications لا تلمس داخل Orders' => ['Notifications', [
                'App\Modules\Orders\Models',
                'App\Modules\Orders\Services',
                'App\Modules\Orders\Http',
                'App\Modules\Products',
            ]],
        ];
    }

    #[DataProvider('rules')]
    public function test_module_does_not_use_forbidden_namespaces(string $module, array $forbidden): void
    {
        $dir = dirname(__DIR__, 2).'/app/Modules/'.$module;
        $this->assertDirectoryExists($dir);

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $code = file_get_contents($file->getPathname());

            foreach ($forbidden as $namespace) {
                $this->assertStringNotContainsString(
                    $namespace,
                    $code,
                    "{$file->getFilename()} في وحدة {$module} يستخدم {$namespace}"
                );
            }
        }
    }
}