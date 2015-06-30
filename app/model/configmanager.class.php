<?php

/**
 * Application configuration management
 * Is accessib
 */
class ConfigManager extends Manager {
    protected $params;
    protected $customParams;
    protected $globalFile = "";
    protected $defaultFile = "";
    protected $file = "";

    public function __construct(Application $app, $dao) {
        parent::__construct($app, $dao);

        $this->param = new StdClass();

        $this->globalFile = ROOT.'/data/config/config.global.json';
        $this->defaultFile = ROOT.'/data/config/config.default.json';
        $this->file = ROOT.'/data/config/config.json';

        if(!$this->load()) {
            $this->loadDefault();
        }
    }

    /**
     * Returns an application configuration parameter
     * or the complete configuration array if no key is given
     * @param  string $key key to search for
     * @return misc        value for the given key
     *                     or false for not found values
     *                     or array of all parameters
     */
    public function get($key = null) {
        if(is_null($key)) {
            return $this->params;
        } else {
            if(isset($this->params->$key)) {
                return $this->params->$key;
            } elseif(isset($this->params->global->$key)) {
                return $this->params->global->$key;
            } else {
                return null;
            }
        }
    }

    /**
     * Returns a configuration parameter from a custom file
     * @param  string @fileName custom file name (without path, without extension)
     * @param  string @key      key to search for
     * @return misc             value of the given key
     *                          or false for not found values
     *                          or array of all parameters
     */
    public function getCustom($fileName, $key = null) {
        if(!isset($this->customParams[$fileName])) {
            $this->loadCustom($fileName);
        }
        if(is_null($key)) {
            return $this->customParams[$fileName];
        } else {
            if(isset($this->customParams[$fileName]->$key)) {
                return $this->customParams[$fileName]->$key;
            } else {
                return null;
            }
        }
    }

    /**
     * Returns all application configuration parameters
     * @param  string  $type          whether the values returned should be 'default', 'app' or 'current' specific
     * @param  boolean $includeGlobal whether to include global config values
     * @return array                  configuration array
     */
    public function getAll($type = 'current', $includeGlobal = true) {
        //don't user $this->param her because it might contain user specific values & globals
        if($type == 'default') {
            $params = $this->loadFile($this->defaultFile);
        } elseif($type == 'app') {
            $params = $this->loadFile($this->file);
        } else {
            //current params: may be modified within app for different reasons
            //suchs as user specific parameters
            $params = clone $this->params;
        }

        if($includeGlobal) {
            $params->global = $this->loadFile($this->globalFile);
        } else {
            unset($params->global);
        }
        return $params;
    }

    /**
     * Returns all application configuration default values
     * @return array complete and default configuration array
     */
    public function getAllDefault() {
        return $this->loadFile($this->defaultFile);
    }

    /**
     * Add or edit an application configuration parameter and save it to the file
     * @param  string  $key   key
     * @param  misc    $value value
     * @return boolean        whether the save was a success
     */
    public function set($key, $value) {
        $this->params->$key = $value;

        return $this->save();
    }

    /**
     * Add or edit a global configuration parameter and save it to the file
     * @param  string  $key   key
     * @param  misc    $value value
     * @return boolean        whether the save was a success
     */
    public function setGlobal($key, $value) {
        $this->params->global->$key = $value;

        return $this->saveGlobal();
    }

    /**
     * Use the given application configuration array as is
     * @param object  $object configuration
     * @param boolean $save   whether to save this configuration to the file
     * @return boolean        whether the save was a success
     */
    public function setAll($object, $save = true) {
        //make sure that no key get lost by merging arrays
        //this way, keys not handle via the interface will be kept
        $this->params = (object)array_merge((array)$this->params, (array)$object);

        return ($save)?$this->save():true;
    }

    /**
     * Replace current configuration by the default one (and save!)
     * @return boolean whether the save was a success
     */
    public function reset() {
        $this->loadDefault();
        return $this->save();
    }

    /**
     * Load the default configuration in app
     * @return boolean false if the configuration is empty afterwards
     */
    private function loadDefault() {
        $this->params = $this->loadFile($this->defaultFile);
        $this->params->global = $this->loadFile($this->globalFile);

        return !empty($this->params);
    }

    /**
     * Load the application configuration from file
     * @return boolean false if the configuration is empty afterwards
     */
    private function load() {
        $this->params = $this->loadFile($this->file);
        if(!empty($this->params)) {
            $this->params->global = $this->loadFile($this->globalFile);
            return true;
        } else {
            return false;
        }
    }

    private function loadCustom($fileName) {
        $path = ROOT.'/data/config/'.$fileName.'.json';
        $this->customParams[$fileName] = $this->loadFile($path);
        if(!empty($this->customParams[$fileName])) {
            return $this->customParams;
        } else {
            return false;
        }
    }

    /**
     * Returns a configuration file content
     * @param  string $file file name
     * @return array        configuration parameters (or false if file not found)
     */
    private function loadFile($file) {
        if (file_exists( $file )) {
            return json_decode(file_get_contents($file));
        } else {
            touch($file);
            return false;
        }
    }

    /**
     * Save the current configuration to file
     * @return boolean true if save was a success
     */
    private function save() {
        $params = clone $this->params;
        unset($params->global);
        return $this->saveFile($this->file, $params);
    }

    /**
     * Save the current configuration to DEFAULT file
     * @return boolean true if save was a success
     */
    private function saveDefault() {
        $params = clone $this->params;
        unset($params->global);
        return $this->saveFile($this->defaultFile, $params);
    }

    /**
     * Save the current GLOBAL configuration to GLOBAL file
     * @return boolean true if save was a success
     */
    private function saveGlobal() {
        return $this->saveFile($this->globalFile, $this->params->global);
    }

    /**
     * Save the current configuration to the given file
     * @param  string  $file   file name
     * @param  misc    $params params to put in the file (json encoded)
     * @return boolean        true if save was a success
     */
    private function saveFile($file, $params) {
        $fp = fopen( $file, 'w' );
        if($fp) {
            fwrite($fp, json_encode($params));
            fclose($fp);
        }
        return $fp !== false;
    }
}

?>