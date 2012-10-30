<?php
/**
 * Sitemap helper class
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 */
class SitemapHelper extends Object {

	/**
	 * Singleton instance.
	 *
	 * @var SitemapHelper
	 */
	public static $instance = null;

	/**
	 * Generates / Updates the sitemap.
	 *
	 * @return boolean true if sitemap was successfully created, else false
	 */
	public static function generate_sitemap() {
		if (self::$instance == null) {
			self::$instance = new self();
		}

		$output = array();

		// Get the output path,
		$output_path = SITEPATH . self::$instance->core->get_dbconfig("sitemap", sitemap::CONFIG_OUTPUT_PATH, '/');

		// Get config if sitemap should be compressed
		$generate_gz = (self::$instance->core->get_dbconfig("sitemap", sitemap::CONFIG_ENABLE_GZIP, 'yes') == 'yes');

		// Get all sitemap entries.
		$entries = self::get_sitemap_entries();

		$domain = self::$instance->core->core_config('core', 'domain');

		// Generating the output.
		$output[] = '<?xml version="1.0" encoding="UTF-8"?>';
		$output[] = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ($entries AS $entry) {
			if (!is_array($entry)) {
				$entry = array('loc' => $entry);
			}

			if (empty($entry['loc'])) {
				continue;
			}
			$output[] = '	<url>';
			$output[] = '		<loc>' . html_entity_decode('http://' . $domain . $entry['loc'], ENT_QUOTES, 'UTF-8') . '</loc>';
			if (!empty($entry['lastmod'])) {
				$output[] = '		<lastmod>' . html_entity_decode($entry['lastmod'], ENT_QUOTES, 'UTF-8') . '</lastmod>';
			}
			if (!empty($entry['changefreq'])) {
				$output[] = '		<changefreq>' . html_entity_decode($entry['changefreq'], ENT_QUOTES, 'UTF-8') . '</changefreq>';
			}
			if (!empty($entry['priority'])) {
				$output[] = '		<priority>' . (float)$entry['priority'] . '</priority>';
			}
			$output[] = '	</url>';
		}
		$output[] = '</urlset>';

		$output = implode("\n", $output);

		// Generate the "normal" (without compression) sitemap file.
		$normal = (file_put_contents($output_path.'.xml', $output) !== FALSE);

		$gz = true;
		// If compressing is enabled, generate the compressed file.
		if ($generate_gz == true) {
			$gz = gzopen($output_path.'.xml.gz','w9');
			if (gzwrite($gz, $output) <= 0) {
				$gz = false;
			}
			gzclose($gz);
		}

		// Return status.
		return ($normal == true && $gz == true);
	}

	/**
	 * Returns all sitemap entries (url pathes) which we want back.
	 *
	 * @return array an array with all entries for the sitemap.
	 */
	public static function get_sitemap_entries() {
		if (self::$instance == null) {
			self::$instance = new self();
		}

		$sites = array();

		// Get all available sections.
		$sections = self::$instance->core->get_dbconfig("sitemap", sitemap::CONFIG_GENERATE_ELEMENTS, array());

		// Call all configured modules to return wanted pathes.

		/**
		 * Provides hook: sitemap_get_entries
		 *
		 * All modules which implements hook_sitemap_section() must implement this method.
		 * Each hook call will provide the array of all sections which we want back, so each
		 * module needs to switch on the provided section if the choosen configuration want the section.
		 *
		 * @param array $sections
		 *   the sections which we want back.
		 *
		 * @return array
		 *   An array with all site pathes excluding the protocoll and domain, just the path.
		 *   If you want to provide the last modified time or the update frequenz
		 *   please provide an array as the value for the array.
		 *   this "entry" array can have the following keys:
		 *     'loc' => the path what you normaly return as the single array value.
		 *     'changefreq' => the frequenz based up on changefreq http://www.sitemaps.org/protocol.html
		 *     'lastmod' => the last modifiction date as YYYY-MM-DD
		 *     'priority' => the priority (default priority is 0.5)
		 */
		foreach(self::$instance->core->hook('sitemap_get_entries', array($sections)) AS $entry) {
			$sites = array_merge($sites, $entry);
		}

		return $sites;
	}
}