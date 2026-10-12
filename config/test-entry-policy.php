<?php
/**
 * Test Web entry points are fail-closed.
 *
 * @return Closure(?string): bool
 */
return static function (?string $environment): bool {
    $environment = strtolower(trim((string) $environment));

    return in_array($environment, ['testing', 'test'], true);
};
