<?php

namespace Gaambo\DeployerWordpress;

class Runtime
{
    private static int $contextDepth = 0;

    /**
     * Run an application command using the configured localhost runtime.
     *
     * @param array{
     *     cwd?:string|null,
     *     timeout?:int|null,
     *     idleTimeout?:int|null,
     *     env?:array<string,string>|null,
     *     secrets?:array<string,string>|null,
     *     nothrow?:bool,
     *     forceOutput?:bool,
     *     shell?:string|null
     * }|null $options
     */
    public static function run(string $command, ?array $options = null): string
    {
        $runtime = self::configured();
        if (isset($options['cwd'])) {
            $options['cwd'] = self::parse($options['cwd'], $runtime);
        }
        if ($runtime === null) {
            return Localhost::runNative($command, $options);
        }

        return $runtime->run(self::parse($command, $runtime), $options);
    }

    public static function path(string $hostPath): string
    {
        return self::configured()?->path($hostPath) ?? $hostPath;
    }

    public static function getConfig(string $key): mixed
    {
        $runtime = self::configured();
        if ($runtime !== null && $runtime->hasConfig($key)) {
            return $runtime->getConfig($key);
        }

        return Localhost::getConfig($key);
    }

    /**
     * Make Localhost::run() runtime-aware for the duration of a callback.
     *
     * @template T
     * @param callable():T $callback
     * @return T
     */
    public static function within(callable $callback): mixed
    {
        self::$contextDepth++;
        try {
            return $callback();
        } finally {
            self::$contextDepth--;
        }
    }

    public static function isActive(): bool
    {
        return self::$contextDepth > 0;
    }

    private static function configured(): ?RuntimeInterface
    {
        $runtime = Localhost::getConfig('runtime');
        if ($runtime === null) {
            return null;
        }
        if (!$runtime instanceof RuntimeInterface) {
            throw new \InvalidArgumentException(
                'The localhost "runtime" configuration must implement RuntimeInterface.'
            );
        }

        return $runtime;
    }

    private static function parse(string $command, ?RuntimeInterface $runtime): string
    {
        return preg_replace_callback(
            '/\{\{\s*([\w\.\/-]+)\s*(?:\|\s*(\w+)\s*)?\}\}/',
            static function (array $matches) use ($runtime) {
                $value = $runtime !== null && $runtime->hasConfig($matches[1])
                    ? $runtime->getConfig($matches[1])
                    : Localhost::getConfig($matches[1]);
                if (!is_string($value)) {
                    throw new \RuntimeException("Config option \"{$matches[1]}\" must be a string.");
                }

                $value = self::parse($value, $runtime);
                return match ($matches[2] ?? null) {
                    null => $value,
                    'quote' => Utils::quote($value),
                    default => throw new \InvalidArgumentException("Unknown filter: {$matches[2]}"),
                };
            },
            $command
        );
    }
}
