<?php

/**
 * Controller for displaying the sitemap.xml. The module displays an index
 * sitemap at the sitemap.xml level, then outputs the individual objects
 * at a second level.
 *
 * <code>
 * http://site.com/sitemap.xml
 * http://site.com/sitemap.xml/sitemap/$ClassName
 * </code>
 *
 * Originally based on github.com/silverstripe-labs/silverstripe-googlesitemaps
 */
class Sitemap_Controller extends Controller {

	private static $allowed_actions = array(
		'index',
		'sitemap'
	);

	/**
	 * Default controller action for the sitemap.xml file. Renders a index
	 * file containing a list of links to sub sitemaps containing the data.
	 */
	public function index($url) {

		$this->Sitemaps = Sitemap::get_sitemaps();

		if ($this->Sitemaps->Count() == 0) {
			return new SS_HTTPResponse('Page not found', 404);
		}

		foreach ($this->Sitemaps as $s) {
			$s->SitemapAbsoluteURL = Director::absoluteURL('sitemap.xml/sitemap/' . $s->ClassName . '/');
		}

		$this->getResponse()->addHeader('Content-Type', 'application/xml; charset="utf-8"');
		$this->getResponse()->addHeader('X-Robots-Tag', 'noindex');

		return $this->renderWith('Sitemap_sitemaps');

	}

	/**
	 * Specific controller action for displaying a particular list of links
	 * for a class
	 */
	public function sitemap() {
		$class = $this->request->param('ID');

		$this->Items = Sitemap::get_items($class);

		if (!$this->Items || $this->Items->Count() == 0) {
			return new SS_HTTPResponse('Page not found', 404);
		}

		$this->getResponse()->addHeader('Content-Type', 'application/xml; charset="utf-8"');
		$this->getResponse()->addHeader('X-Robots-Tag', 'noindex');

		return $this->renderWith('Sitemap');
	}

}