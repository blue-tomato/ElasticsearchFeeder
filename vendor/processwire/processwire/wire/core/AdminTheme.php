<?php namespace ProcessWire;

/**
 * ProcessWire Admin Theme Module
 *
 * An abstract module intended as a base for admin themes. 
 *
 * See the Module interface (Module.php) for details about each method. 
 *
 * This file is licensed under the MIT license. 
 * https://processwire.com/about/license/mit/
 * 
 * ProcessWire 3.x, Copyright 2018 by Ryan Cramer
 * https://processwire.com
 * 
 * @property int|string $version Current admin theme version
 * 
 * @method void install()
 * @method void uninstall()
 * @method array getExtraMarkup()
 *
 */

abstract class AdminTheme extends WireData implements Module {

	/**
	 * Per the Module interface, return an array of information about the Module
	 *
	 */
	public static function getModuleInfo() {
		return array(
			'title'    => '',        // printable name/title of module
			'version'  => 1,    // version number of module
			'summary'  => '',    // 1 sentence summary of module
			'href'     => '',        // URL to more information (optional)

			// all admin themes should have this as their autoload selector:
			'autoload' => 'template=admin',
			'singular' => true
		);
	}

	/**
	 * Current admin theme version (cached from module info)
	 *
	 * @var int
	 *
	 */
	protected $version = 0;

	/**
	 * Keeps track of quantity of admin themes installed so that we know when to add profile field
	 *
	 */
	protected static $numAdminThemes = 0;

	/**
	 * Additional classes for body tag
	 *
	 * @var array
	 *
	 */
	protected $bodyClasses = array();

	/**
	 * General purpose classes indexed by name
	 *
	 * @var array
	 *
	 */
	protected $classes = array();

	/**
	 * Extra markup regions
	 * 
	 * @var array
	 * 
	 */
	protected $extraMarkup = array(
		'head' => '',
		'notices' => '',
		'body' => '',
		'masthead' => '',
		'content' => '',
		'footer' => '',
		'sidebar' => '', // sidebar not used in all admin themes
	);
	
	/**
	 * URLs to place in link prerender tags
	 * 
	 * @var array
	 * 
	 */
	protected $preRenderURLs = array();

	/**
	 * Construct
	 * 
	 */
	public function __construct() {
		// placeholder
	}

	/**
	 * Initialize the admin theme system and determine which admin theme should be used
	 *
	 * All admin themes must call this init() method to register themselves. 
	 * 
	 * Note: this should be called after API ready. 
	 *
	 */
	public function init() { 
		self::$numAdminThemes++;
		
		$info = $this->wire('modules')->getModuleInfo($this);
		$this->version = $info['version'];

		// if module has been called when it shouldn't (per the 'autoload' conditional)
		// then module author probably forgot the right 'autoload' string, so this 
		// serves as secondary stopgap to keep this module from loading when it shouldn't.
		if(!$this->wire('page') || $this->wire('page')->template != 'admin') return;
		
		if(self::$numAdminThemes > 1 && !$this->wire('fields')->get('admin_theme')) $this->install();

		// if admin theme has already been set, then no need to continue
		if($this->wire('adminTheme')) return; 

		/** @var Config $config */
		$config = $this->wire('config');
		/** @var Session $session */
		$session = $this->wire('session');
		/** @var User $user */
		$user = $this->wire('user');
		/** @var string $adminTheme */
		$adminTheme = $user->admin_theme; 

		if($adminTheme) {
			// there is user specified admin theme
			// check if this is the one that should be used
			if($adminTheme == $this->className()) $this->setCurrent();
			
		} else if($this->wire('config')->defaultAdminTheme == $this->className()) {
			// there is no user specified admin theme, so use this one
			$this->setCurrent();
		}

		// adjust $config adminThumbOptions[scale] for auto detect when requested
		$o = $config->adminThumbOptions; 
		if($o && isset($o['scale']) && $o['scale'] === 1) {
			$o['scale'] = $session->get('hidpi') ? 0.5 : 1.0; 
			$config->adminThumbOptions = $o;
		}

		$config->js('modals', $config->modals); 

		if($session->get('hidpi')) $this->addBodyClass('hidpi-device');
		if($session->get('touch')) $this->addBodyClass('touch-device'); 
		
		$this->addBodyClass($this->className());
	}

	public function get($key) {
		if($key == 'version') return $this->version;
		return parent::get($key); 
	}

	/**
	 * Returns true if this admin theme is the one that will be used for this request
	 *
	 */
	public function isCurrent() {
		return $this->wire('adminTheme') === $this; 
	}

	/**
	 * Set this admin theme as the current one
	 * 
	 */
	protected function setCurrent() {
		$config = $this->wire('config');
		$name = $this->className();
		$config->paths->set('adminTemplates', $config->paths->get($name));
		$config->urls->set('adminTemplates', $config->urls->get($name)); 
		$config->set('defaultAdminTheme', $name);
		$this->wire('adminTheme', $this);
	}

	/**
	 * Enables hooks to append extra markup to various sections of the admin page
	 * 
	 * @return array Associative array containing the following properties, any of 
	 * which may be populated or empty: 
	 * 	- head
	 * 	- body
	 * 	- masthead
	 * 	- content
	 * 	- footer
	 * 	- sidebar
	 * 
	 */
	public function ___getExtraMarkup() {
		$parts = $this->extraMarkup;
		$isLoggedin = $this->wire('user')->isLoggedin();
		if($isLoggedin && $this->wire('modules')->isInstalled('InputfieldCKEditor') 
			&& $this->wire('process') instanceof WirePageEditor) {
			// necessary for when CKEditor is loaded via ajax
			$parts['head'] .= "<script>" . 
				"window.CKEDITOR_BASEPATH='" . $this->wire('config')->urls->InputfieldCKEditor . 
				'ckeditor-' . InputfieldCKEditor::CKEDITOR_VERSION . "/';</script>";
		}
		/*
		if($isLoggedin && $this->wire('config')->advanced) {
			$parts['footer'] = "<p class='AdvancedMode'><i class='fa fa-flask'></i> " . $this->_('Advanced Mode') . "</p>";
		}
		*/
		foreach($this->preRenderURLs as $url) {
			$parts['head'] .= "<link rel='prerender' href='$url'>";
		}
		return $parts; 
	}

	/**
	 * Add extra markup to a region in the admin theme
	 * 
	 * @param string $name
	 * @param string $value
	 * 
	 */
	public function addExtraMarkup($name, $value) {
		if(!empty($this->extraMarkup[$name])) {
			$this->extraMarkup[$name] .= "\n$value";
		} else {
			$this->extraMarkup[$name] = $value;
		}
	}

	/**
	 * Add a <body> class to the admin theme
	 * 
	 * @param string $className
	 * 
	 */
	public function addBodyClass($className) {
		$this->addClass('body', $className);
	}

	/**
	 * Get the body[class] attribute string
	 * 
	 * @return string
	 * 
	 */
	public function getBodyClass() {
		return $this->getClass('body'); 
	}

	/**
	 * Return class for a given named item or blank if none available
	 * 
	 * Omit the first argument to return all classes in an array.
	 * 
	 * @param string $name Tag or item name, i.e. “input”, or omit to return all defined [tags=classes]
	 * @param bool $getArray Specify true to return array of class name(s) rather than string (default=false). $tagName argument required.
	 * @return string|array Returns string or array of class names, or array of all [tags=classes] or $tagName argument is empty.
	 * 
	 */
	public function getClass($name = '', $getArray = false) {
		if(empty($name)) {
			return $this->classes;
		} else if(isset($this->classes[$name])) {
			return $getArray ? explode(' ', $this->classes[$name]) : $this->classes[$name];
		} else {
			return $getArray ? array() : '';
		}
	}

	/**
	 * Add class for given named item
	 * 
	 * Default behavior is to merge classes if existing classes are already present for given item $name.
	 * 
	 * #pw-internal
	 * 
	 * @param string $name
	 * @param string|array $class
	 * @param bool $replace Specify true to replace any existing classes rather than merging them
	 * 
	 */
	public function addClass($name, $class, $replace = false) {
		if(is_array($class)) {
			foreach($class as $c) {
				$this->addClass($name, $c);
			}
		} else if(!$replace && isset($this->classes[$name])) {
			$classes = $this->classes[$name];
			if(strpos($classes, $class) !== false) {
				// avoid re-adding class if it is already present
				if(array_search($class, explode(' ', $classes)) !== false) return; 
			}
			$this->classes[$name] = trim($classes . ' ' . ltrim($class));	
		} else {
			$this->classes[$name] = trim($class);
		}
	}

	/**
	 * Set classes for multiple tags
	 * 
	 * #pw-internal
	 * 
	 * @param array $classes Array of strings (class names) where keys are tag names
	 * @param bool $replace Specify true to replace any existing classes rather than merge them (default=false)
	 * 
	 */
	public function setClasses(array $classes, $replace = false) {
		if($replace || empty($this->classes)) {
			$this->classes = $classes;	
		} else {
			foreach($classes as $name => $class) {
				$this->addClass($name, $class);
			}
		}
	}

	/**
	 * Install the admin theme
	 *
	 * Other admin themes using an install() method must call this install before their own.
	 *
	 */
	public function ___install() { 

		// if we are the only admin theme installed, no need to add an admin_theme field
		if(self::$numAdminThemes == 0) return;

		// install a field for selecting the admin theme from the user's profile
		$field = $this->wire('fields')->get('admin_theme'); 

		$toUseNote = $this->_('To use this theme, select it from your user profile.'); 

		// we already have this field installed, no need to continue
		if($field) {
			$this->message($toUseNote); 
		} else {
			// this will be the 2nd admin theme installed, so add a field that lets them select admin theme
			$field = $this->wire(new Field());
			$field->name = 'admin_theme';
			$field->type = $this->wire('modules')->get('FieldtypeModule');
			$field->set('moduleTypes', array('AdminTheme'));
			$field->set('labelField', 'title');
			$field->set('inputfieldClass', 'InputfieldRadios');
			$field->label = 'Admin Theme';
			$field->flags = Field::flagSystem;
			try {
				$field->save();
			} catch(\Exception $e) {
				// $this->error("Error creating 'admin_theme' field: " . $e->getMessage());
			}
		}

		if($field && $field->id) {
			/** @var Fieldgroup $fieldgroup */
			$fieldgroup = $this->wire('fieldgroups')->get('user');
			if(!$fieldgroup->hasField($field)) {
				$fieldgroup->add($field);
				$fieldgroup->save();
				$this->message($this->_('Installed field "admin_theme" and added to user profile settings.'));
				$this->message($toUseNote);
			}
			// make this field one that the user is allowed to configure in their profile
			$data = $this->wire('modules')->getModuleConfigData('ProcessProfile');
			$data['profileFields'][] = 'admin_theme';
			$this->wire('modules')->saveModuleConfigData('ProcessProfile', $data); 
		}
	}

	/**
	 * Set a pre-render URL or get currently pre-render URL(s)
	 * 
	 * #pw-internal
	 * 
	 * @param string $url
	 * @return array
	 * 
	 */
	public function preRenderURL($url = '') {
		if(!empty($url)) $this->preRenderURLs[] = $url;
		return $this->preRenderURLs;
	}
	
	public function ___uninstall() { 
	
		$defaultAdminTheme = $this->wire('config')->defaultAdminTheme;
		if($defaultAdminTheme == $this->className()) {
			throw new WireException(
				"Cannot uninstall this admin theme because \$config->defaultAdminTheme = '$defaultAdminTheme'; " . 
				"Please add this setting with a different value in /site/config.php"
			); 
		}

		/*
		if(self::$numAdminThemes > 1) return;

		// this is the last installed admin theme
		$field = $this->wire('fields')->get('admin_theme'); 	
		$field->flags = Field::flagSystemOverride; 
		$field->flags = 0; 
		$field->save();

		$fieldgroup = $this->wire('fieldgroups')->get('user'); 
		$fieldgroup->remove($field); 
		$fieldgroup->save();

		$this->wire('fields')->delete($field); 
		$this->message($this->_('Removed field "admin_theme" from system.')); 
		*/
	}
}

