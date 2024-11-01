<?php
class wpTemplate {
    protected $vars = array(); /// Holds all the template variables
	protected $files = array();
	protected $templatefolder = 'templates';
    
    /**
     * Constructor
     *
     * @param $file string the file name you want to load
     */
    public function __construct($file = false) {
        if ($file) $this->append_template($file);
    }

	public function append_template($file) {
        $this->files[] = $file;
	}
    /**
     * Set a template variable.
     */
    public function set($name, $value) {
        $this->vars[$name] = $value;
    }

    /**
     * Open, parse, and return the template file.
     *
     * @param $file string the template file name
     */
    public function fetch($file = false) {
        if($file) {
        	$files = array($file); 
		} else {
			$files = $this->files;
		}
        extract($this->vars);          // Extract the vars to local namespace
        ob_start();                    // Start output buffering
		foreach ($files as $file) {
	        include(dirname(__FILE__)."/".$this->templatefolder."/".$file.".php");	// Include the file
		}
        $contents = ob_get_contents(); // Get the contents of the buffer
        ob_end_clean();                // End buffering and discard
        return $contents;              // Return the contents
    }
}