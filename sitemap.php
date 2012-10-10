<?php
/**
 * Sitemap action module
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package modules.sitemap
 */
class sitemap extends ActionModul
{
	/**
	 * Define config constances
	 */
	const CONFIG_DEFAULT_SITEMAP_GENERATION_INTERVAL = 'default_sitemap_generation_interval';
	const CONFIG_GENERATE_ELEMENTS = 'generate_elements';
	const CONFIG_ENABLE_GZIP = 'enable_gzip';
	const CONFIG_ENABLE_SITEMAP = 'enable_sitemap';
	const CONFIG_OUTPUT_PATH = 'output_path';

	/**
	 * The default method
	 * @var string
	 */
	protected $default_methode = self::NO_DEFAULT_METHOD;

	/**
	 * Implementation of get_admin_menu()
	 *
	 * @return array the menu
	 */
	public function get_admin_menu() {
		return array(
			55 => array(//Order id, same order ids will be unsorted placed behind each
				'#id' => 'soopfw_sitemap', //A unique id which will be needed to generate the submenu
				'#title' => t("Sitemap"), //The main title
				'#perm' => 'admin.sitemap', //Perm needed
				'#childs' => array(
					array(
						'#title' => t("Config"), //The main title
						'#link' => "/admin/sitemap/config", // The main link
						'#perm' => "admin.sitemap.manage", // perms needed
					),
				)
			)
		);
	}

	/**
	 * Implements hook: cron
	 *
	 * Allow other modules to run cron's
	 *
	 * @param Cron $cron
	 *   A cron object.
	 *   So we don't need to initialize this object within every hook
	 *   to use it.
	 *   Its just a helper for performance
	 */
	public function hook_cron(Cron &$cron) {
		$runtime = (int)$this->core->get_dbconfig("sitemap", self::CONFIG_DEFAULT_SITEMAP_GENERATION_INTERVAL, 7);
		if (!empty($runtime)) {
			if ($cron->match("1 1 */" . $runtime ." * *")) {
				$cli = new cli_generate_sitemap();
				$cli->execute();
			}
		}
	}

	/**
	 * Action: config
	 *
	 * Configurate the system main settings.
	 */
	public function config() {
		//Check perms
		if (!$this->right_manager->has_perm('admin.sitemap.manage', true)) {
			throw new SoopfwNoPermissionException();
		}

		//Setting up title and description
		$this->title(t("Sitemap config"), t("Here we can configure the sitemap settings"));

		//Configurate the settings form
		$form = new SystemConfigForm($this, "sitemap_config");

		$form->add(new Fieldset('main_config', t('Main')));
		$form->add(new Textfield(self::CONFIG_DEFAULT_SITEMAP_GENERATION_INTERVAL, $this->core->get_dbconfig("sitemap", self::CONFIG_DEFAULT_SITEMAP_GENERATION_INTERVAL, 7), t("Default generation interval (days)"), t('If the provided generation sections not defining any interval, this interval will be used.')));
		$form->add(new Textfield(self::CONFIG_OUTPUT_PATH, $this->core->get_dbconfig("sitemap", self::CONFIG_OUTPUT_PATH, '/sitemap'), t("Output file"), t('Please provide the endpoint where the sitemap can be accessed without fileending.<br/>For example if you want to have the sitemap file at @sitepath/sitemap.xml you provide here only "/sitemap", .xml and .xml.gz (of compression is enabled) will be appended.<br/>The url to access the sitemap would be then @domain/sitemap.xml or @domain/sitemap.xml.gz', array(
			'@sitepath' => SITEPATH,
			'@domain' => 'http://' . $this->core->core_config('core', 'domain'),
		))), array(
			new FunctionValidator(t('The directory you choose is not writeable by the webserver user.'), function($value) {
				$check_path = $value;
				if (preg_match("/^(.*\/)[^\/]*$/", $check_path, $matches)) {
					$check_path = $matches[1];
				}
				return is_writable(SITEPATH . $check_path);
			})
		));

		$sections = array();

		/**
		 * Provides hook: sitemap_section
		 *
		 * Allow other modules to provide sections for sitemap generation.
		 *
		 * @return array An array which holds unique sections as the keys and a label as the value.
		 */
		foreach($this->core->hook('sitemap_section') AS $section) {
			$sections = array_merge($sections, $section);
		}
		$form->add(new Checkboxes(self::CONFIG_GENERATE_ELEMENTS, $sections, $this->core->get_dbconfig("sitemap", self::CONFIG_GENERATE_ELEMENTS, array()), t("Generate sitemap for"), t('Only selected "sections" will be included within the generated sitemap')));
		$form->add(new YesNoSelectfield(self::CONFIG_ENABLE_GZIP, $this->core->get_dbconfig("sitemap", self::CONFIG_ENABLE_GZIP, 'yes'), t("Also generate gzip compressed sitemap file")), array(
			new FunctionValidator(t('Can not find extension "zlip", zlip is required to generate compressed .gz sitemaps'), function($value) {
				if ($value == 'yes') {
					return extension_loaded('zlib');
				}
				return true;
			})
		));
		$form->add(new YesNoSelectfield(self::CONFIG_ENABLE_SITEMAP, $this->core->get_dbconfig("sitemap", self::CONFIG_ENABLE_SITEMAP, 'yes'), t("Enable sitemap generator"), t('Only if this is enabled a sitemap will be generated.')));

		//Execute the settings form
		$form->execute();
	}
}