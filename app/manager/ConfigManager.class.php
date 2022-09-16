<?php

namespace Watamelo\Managers;

use http\Exception\RuntimeException;
use StdClass;
use Watamelo\Lib\Application;
use Watamelo\Lib\Manager;

/**
 * Application configuration management
 */
class ConfigManager extends Manager
{
    protected StdClass $params;
    protected array $customParams = [];
    protected string $globalFile = '';
    protected string $defaultFile = '';
    protected string $file = '';

    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->params = new StdClass();

        $this->globalFile = ROOT . '/data/config/config.global.json';
        $this->defaultFile = ROOT . '/data/config/config.default.json';
        $this->file = ROOT . '/data/config/config.json';

        if (!$this->load()) {
            $this->loadDefault();
        }
    }

    /**
     * Returns an application configuration parameter
     * or the complete configuration array if no key is given
     * @param string|null $key key to search for
     * @return mixed       value for the given key
     *                     or false for not found values
     *                     or array of all parameters
     */
    public function get(string $key = null)
    {
        if (is_null($key)) {
            return $this->params;
        }

        if (isset($this->params->$key)) {
            return $this->params->$key;
        }

        if (isset($this->params->global->$key)) {
            return $this->params->global->$key;
        }

        return null;
    }

    /**
     * Returns a configuration parameter from a custom file
     * @param string @fileName custom file name (without path, without extension)
     * @param string|null $key
     * @return mixed            value of the given key
     *                          or false for not found values
     *                          or array of all parameters
     */
    public function getCustom(string $fileName, string $key = null)
    {
        if (!isset($this->customParams[$fileName])) {
            $this->loadCustom($fileName);
        }
        if (is_null($key)) {
            return $this->customParams[$fileName];
        }

        if (isset($this->customParams[$fileName]->$key)) {
            return $this->customParams[$fileName]->$key;
        }

        return null;
    }

    /**
     * Returns all application configuration parameters
     * @param string $type whether the values returned should be 'default', 'app' or 'current' specific
     * @param bool $includeGlobal whether to include global config values
     * @return StdClass configuration data
     */
    public function getAll($type = 'current', $includeGlobal = true): StdClass
    {
        //don't user $this->params her because it might contain user specific values & globals
        if ($type === 'default') {
            $params = $this->loadFile($this->defaultFile);
        } elseif ($type === 'app') {
            $params = $this->loadFile($this->file);
        } else {
            //current params: may be modified within app for different reasons
            //such as user specific parameters
            $params = clone $this->params;
        }

        if ($includeGlobal) {
            $params->global = $this->loadFile($this->globalFile);
        } else {
            unset($params->global);
        }
        return $params;
    }

    /**
     * Returns all application configuration default values
     * @return StdClass complete and default configuration data
     */
    public function getAllDefault(): StdClass
    {
        return $this->loadFile($this->defaultFile);
    }

    /**
     * Add or edit an application configuration parameter and save it to the file
     * @param string $key key
     * @param mixed $value value
     * @return bool        whether the save was a success
     */
    public function set(string $key, $value): bool
    {
        $this->params->$key = $value;

        return $this->save();
    }

    /**
     * Add or edit a global configuration parameter and save it to the file
     * @param string $key key
     * @param mixed $value value
     * @return bool        whether the save was a success
     */
    public function setGlobal(string $key, $value): bool
    {
        $this->params->global->$key = $value;

        return $this->saveGlobal();
    }

    /**
     * Use the given application configuration array as is
     * @param object $object configuration
     * @param bool $save whether to save this configuration to the file
     * @return bool        whether the save was a success
     */
    public function setAll(object $object, bool $save = true): bool
    {
        //make sure that no key get lost by merging arrays
        //this way, keys not handle via the interface will be kept
        $this->params = (object)array_merge((array)$this->params, (array)$object);

        return $save ? $this->save() : true;
    }

    /**
     * Replace current configuration by the default one (and save!)
     * @return bool whether the save was a success
     */
    public function reset(): bool
    {
        $this->loadDefault();
        return $this->save();
    }

    /**
     * Load the default configuration in app
     * @return bool false if the configuration is empty afterwards
     */
    private function loadDefault(): bool
    {
        $this->params = $this->loadFile($this->defaultFile);
        $this->params->global = $this->loadFile($this->globalFile);

        return !empty($this->params);
    }

    /**
     * Load the application configuration from file
     * @return bool false if the configuration is empty afterwards
     */
    private function load(): bool
    {
        $this->params = $this->loadFile($this->file);
        if (!empty($this->params)) {
            $this->params->global = $this->loadFile($this->globalFile);
            return true;
        }

        return false;
    }

    private function loadCustom($fileName)
    {
        $path = ROOT . '/data/config/' . $fileName . '.json';
        $this->customParams[$fileName] = $this->loadFile($path);
        if (!empty($this->customParams[$fileName])) {
            return $this->customParams;
        }

        return false;
    }

    /**
     * Returns a configuration file content
     * @param string $file file name
     * @return StdClass configuration parameters (or false if file not found)
     */
    private function loadFile(string $file): StdClass
    {
        if (!file_exists($file)) {
            throw new RuntimeException(sprintf('Configuration file "%s" not found.', $file));
        }
        return json_decode(file_get_contents($file));
    }

    /**
     * Save the current configuration to file
     * @return bool true if save was a success
     */
    private function save(): bool
    {
        $params = clone $this->params;
        unset($params->global);
        return $this->saveFile($this->file, $params);
    }

    /**
     * Save the current GLOBAL configuration to GLOBAL file
     * @return bool true if save was a success
     */
    private function saveGlobal(): bool
    {
        return $this->saveFile($this->globalFile, $this->params->global);
    }

    /**
     * Save the current configuration to the given file
     * @param string $file file name
     * @param mixed $params params to put in the file (json encoded)
     * @return bool        true if save was a success
     */
    private function saveFile(string $file, $params): bool
    {
        $fp = fopen($file, 'wb');
        if ($fp) {
            fwrite($fp, json_encode($params));
            fclose($fp);
        }
        return $fp !== false;
    }
}
