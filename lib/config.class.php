<?php

/**
 * Application configuration management
 */
class Config extends ApplicationComponent {
    protected $params = array();
    protected $defaultFile = "";
    protected $file = "";

    public function __construct(Application $app) {
        parent::__construct($app);

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
            if(array_key_exists($key, $this->params)) {
                return $this->params[$key];
            } else {
                return false;
            }
        }
    }

    /**
     * Returns all application configuration parameters
     * @return array complete configuration array
     */
    public function getAll() {
        return $this->params;
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
        $this->params[$key] = $value;

        return $this->save();
    }

    /**
     * Use the given application configuration array as is 
     * @param array   $array configuration
     * @param boolean $save  whether to save this configuration to the file
     * @return boolean       whether the save was a success
     */
    public function setAll($array, $save = true) {
        //make sure that no key get lost by merging arrays
        //this way, keys not handle via the interface will be kept
        $this->params = array_merge($this->params, $array);

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
     * @return array false the configuration is empty afterwards
     */
    private function loadDefault() {
        $this->params = $this->loadFile($this->defaultFile);
        return !empty($this->params);
    }

    /**
     * Load the application configuration from file
     * @return array false the configuration is empty afterwards
     */
    private function load() {
        $this->params = $this->loadFile($this->file);
        return !empty($this->params);
    }

    /**
     * Returns a configuration file content
     * @param  string $file file name
     * @return array        configuration parameters (or false if file not found)
     */
    private function loadFile($file) {
        if (file_exists( $file )) {
            return json_decode(file_get_contents($file), true);
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
        return $this->saveFile($this->file);
    }

    /**
     * Save the current configuration to DEFAULT file
     * @return boolean true if save was a success
     */
    private function saveDefault() {
        return $this->saveFile($this->defaultFile);
    }

    /**
     * Save the current configuration to the given file
     * @param  string $file file name
     * @return boolean      true if save was a success
     */
    private function saveFile($file) {
        $fp = fopen( $file, 'w' );
        if($fp) {
            fwrite($fp, json_encode($this->params));
            fclose($fp);
        }
        return $fp !== false;
    }
}

?>