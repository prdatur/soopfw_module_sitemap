<?php

/**
 * Provide cli commando (drush) to re-generate the sitemap
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package cli
 */
class cli_generate_sitemap extends CLICommand
{
	/**
	 * Overrides CLICommand::description
	 * The description for help
	 *
	 * @var string
	 */
	protected $description = "Recreate sitemap";

	/**
	 * Execute the command
	 *
	 * @return boolean return true if no errors occured, else false
	 */
	public function execute() {
		return SitemapHelper::generate_sitemap();
	}

	/**
	 * Overrides CLICommand::on_success
	 * callback for on_success
	 */
	public function on_success() {
		CliHelper::console_log('Sitemap generated', 'ok');
	}

}


