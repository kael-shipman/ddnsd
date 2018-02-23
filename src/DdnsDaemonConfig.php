<?php
namespace KS\Ddnsd;

class DdnsDaemonConfig implements DdnsDaemonConfigInterface
{
    protected $config;
    protected $conffiles = [];
    const PROFILE_PROD = 'production';

    public function __construct(array $conffiles)
    {
        $this->conffiles = $conffiles;
        $this->reload();
    }

    public function getExecutionProfile(): string
    {
        try {
            return $this->get('execution-profile');
        } catch (\KS\MissingConfigException $e) {
            return $this::PROFILE_PROD;
        }
    }

    public function getCheckInterval(): int
    {
        try {
            return $this->get('check-interval');
        } catch (\KS\MissingConfigException $e) {
            return 3600;
        }
    }

    public function getProfiles(): array
    {
        return $this->get('profiles');
    }

    public function getProviderPrefix(): string
    {
        try {
            return $this->get('provider-prefix');
        } catch (\KS\MissingConfigException $e) {
            return 'ddnsd-provider-';
        }
    }

    public function getRunLoopInterval(): int
    {
        try {
            return $this->get('run-loop-interval');
        } catch (\KS\MissingConfigException $e) {
            return 1;
        }
    }



    /** Implementing \KS\DaemonConfigInterface **/


    public function getPhpErrorLevel() : int
    {
        try {
            return $this->get('php-error-level');
        } catch (\KS\MissingConfigException $e) {
            return E_ALL & ~E_NOTICE;
        }
    }
    public function getPhpDisplayErrors() : int
    {
        try {
            return $this->get('php-display-errors');
        } catch (\KS\MissingConfigException $e) {
            return 0;
        }
    }
    public function getLogLevel() : int
    {
        try {
            return $this->get('log-level');
        } catch (\KS\MissingConfigException $e) {
            return LOG_WARNING;
        }
    }
    public function getLogIdentifier() : string
    {
        try {
            return $this->get('log-identifier');
        } catch (\KS\MissingConfigException $e) {
            return "ddnsd";
        }
    }


    /** Implementing \KS\BaseConfigInterface */

    public function reload(): void
    {
        $parser = new \HJSON\HJSONParser();

        $this->config = [];
        foreach($this->conffiles as $f) {
            if (!is_file($f)) {
                throw new \KS\NonexistentFileException("Couldn't find config file $f");
            }
            $conf = $parser->parse(file_get_contents($f), ['assoc' => true]);
            $this->config = array_replace_recursive($this->config, $conf);
        }

        $this->checkConfig(true);
    }

    /** @inheritDoc */
    public function checkConfig(bool $force=false) : void {
        if (!$force && $this->getExecutionProfile() == static::PROFILE_PROD) {
            return;
        }

        $ref = new \ReflectionClass($this);
        $interfaces = $ref->getInterfaces();
        $check = array();
        foreach($interfaces as $name => $i) {
            if ($name == 'KS\BaseConfigInterface') continue;
            $methods = $i->getMethods();
            foreach($methods as $m) {
                $m = $m->getName();
                if (substr($m,0,3) == 'get' && strlen($m) > 3) $check[$m] = $m;
            }
        }

        $errors = array();
        foreach($check as $m) {
            try {
                $testParams = $this->getTestParams();
                if (!array_key_exists($m, $testParams)) $this->$m();
                else {
                    if (!is_array($testParams[$m])) $testParams[$m] = array($testParams[$m]);
                    call_user_func_array(array($this, $m), $testParams[$m]);
                }
            } catch (\KS\MissingConfigException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (count($errors) > 0) throw new \KS\MissingConfigException("Your configuration is incomplete:\n\n".implode("\n  ", $errors));
    }

    /**
     * An internal method that ensures an error is thrown if the given key is not found in the configuration.
     *
     * @param string $key The key of the configuration value to get
     * @return mixed Returns the configuration value at `$key`
     * @throws MissingConfigException in the even that a given config key isn't loaded
     */
    protected function get(string $key) {
        if (!array_key_exists($key, $this->config)) throw new \KS\MissingConfigException("Your configuration doesn't have a value for the key `$key`");
        return $this->config[$key];
    }

    /** @inheritDoc */
    public function dump(): string {
        $dump = array();
        foreach ($this->config as $k => $v) $dump[] = "$k: `$v`;";
        return implode("\n", $dump);
    }

    protected function getTestParams()
    {
        return [];
    }
}


