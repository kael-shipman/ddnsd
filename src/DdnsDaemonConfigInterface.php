<?php
namespace KS\Ddnsd;

interface DdnsDaemonConfigInterface extends \KS\DaemonConfigInterface
{
    /**
     * Get the interval at which to recheck IP records for changes
     *
     * @return int The number of seconds to wait between checks
     */
    public function getCheckInterval(): int;

    /**
     * Get the profiles this daemon is managing
     *
     * @return array An array of profile data objects
     */
    public function getProfiles(): array;

    /**
     * Get the prefix for provider binaries (defaults to `ddnsd-provider-`)
     *
     * @return string
     */
    public function getProviderPrefix(): string;

    /**
     * Get the number of seconds to wait in the run loop.
     *
     * Bigger numbers mean slightly less processor-heavy, but more sluggish in response
     * to signals.
     *
     * @return int
     */
    public function getRunLoopInterval(): int;
}

