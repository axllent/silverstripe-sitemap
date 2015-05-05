<?php
/**
 * Sitemap support for SilverStripe
 * ================================
 *
 * Extension to sitemap support SilverStripe
 *
 * Usage: See README.md
 *
 * License: MIT-style license http://opensource.org/licenses/MIT
 * Authors: Techno Joy development team (www.technojoy.co.nz)
 *
 * Based on github.com/silverstripe-labs/silverstripe-googlesitemaps
 */

class Sitemap {

	private static $include_sitetree = true;

	private static $google_notifications = false;

	private static $dataobjects = array();

	public static function register_dataobject($className, $options = array()) {
		if (!$className || !is_array($options)) {
			trigger_error('SimpleSitemap::register_dataobject($className, $options=array())', E_USER_ERROR);
			return false;
		}
		if (!self::is_registered($className) && class_exists($className)) {
			$className::add_extension('SitemapExtension');
			self::$dataobjects[$className] = $options;
		}
	}

	/**
	 * Do not include SiteTree
	 *
	 * @param null
	 * @return null
	 */
	public static function exclude_sitetree() {
		self::$include_sitetree = false;
	}

	/**
	 * Enable Google pings
	 *
	 * @param null
	 * @return null
	 */
	public static function enable_google_notifications() {
		self::$google_notifications = false;
	}

	/**
	 * Is class already registered?
	 *
	 * @param String
	 * @return boolean
	 */
	public static function is_registered($className) {
		return isset(self::$dataobjects[$className]);
	}

	/**
	 * Remove class from list
	 *
	 * @param String
	 * @return null
	 */
	public static function unregister_dataobject($className) {
		if (self::is_registered($className)) {
			unset(self::$dataobjects[$className]);
		}
	}

	/**
	 * Returns the string frequency of edits for a particular dataobject class
	 * Frequency for {@link SiteTree} objects can be determined from the version history.
	 *
	 * @param string
	 * @return string
	 */
	public static function get_frequency_for_class($class) {
		if (self::is_registered($class) && isset(self::$dataobjects[$class]['frequency'])) {
			return self::$dataobjects[$class]['frequency'];
		}
	}

	/**
	 * Returns the default priority of edits for a particular dataobject class
	 *
	 * @param string
	 * @return float
	 */
	public static function get_priority_for_class($class) {
		if (self::is_registered($class) && isset(self::$dataobjects[$class]['priority'])) {
			return self::$dataobjects[$class]['priority'];
		}
		return 0.5;
	}

	/**
	 * Automatically add SiteTree
	 *
	 * @param null
	 * @return null
	 */
	public static function init_sitetree() {
		if(self::$include_sitetree && class_exists('SiteTree') && !self::is_registered('SiteTree')) {
			self::register_dataobject('SiteTree', array(
				'filter' => array('ShowInSearch' => 1)
			));
		}
	}

	/**
	 * Return all sitemaps
	 *
	 * @param null
	 * @return ArrayList
	 */
	public static function get_sitemaps() {

		self::init_sitetree();

		$sitemaps = new ArrayList();

		if(count(self::$dataobjects) > 0) {

			foreach(self::$dataobjects as $class => $config) {
				$items = self::get_filtered_results($class);

				if ($items->Count() > 0) {

					$list = new PaginatedList($items);
					$list->setPageLength(1000);
					$pages = $list->TotalPages();

					for ($x=1; $x <= $pages; $x++) {
						$latest = $items->limit(1000, ($x-1) * 1000)->Sort('LastEdited', 'DESC')->first();
						$sitemaps->push(new ArrayData(array(
							'ClassName' => $class,
							'LastEdited' => date('Y-m-d', strtotime($latest->LastEdited)),
							'Page' => ($x == 1) ? false : $x
						)));
					}
				}
			}
		}

		return $sitemaps;

	}

	/**
	 * Return all filtered items in class
	 * Supports additional filtering (filter, where & exclude)
	 *
	 * @param string
	 * @return ArrayList
	 */
	public static function get_filtered_results($className) {
		self::init_sitetree();

		if (!self::is_registered($className)) {
			return false;
		}

		$class = self::$dataobjects[$className];

		$list = new DataList($className);
		if (isset($class['filter'])) {
			$list = $list->filter($class['filter']);
		}
		if (isset($class['where'])) {
			$list = $list->where($class['where']);
		}
		if (isset($class['exclude'])) {
			$list = $list->exclude($class['exclude']);
		}
		$list = $list->sort('LastEdited', 'DESC');
		return $list;
	}

	/**
	 * Return paginated results in class
	 * Only returns items that contain Link()
	 * Limited to 1000 results per page
	 *
	 * @param string
	 * @return ArrayList
	 */
	public static function get_items($className, $page=1) {

		$items = self::get_filtered_results($className);
		$list = new PaginatedList($items);
		$list->setPageLength(1000);
		$list->setCurrentPage($page);

		$output = new ArrayList();

		/* only push items with a link */
		foreach ($list as $item) {
			$item->ChangeFrequency = self::get_frequency_for_class($className);
			$item->GooglePriority = self::get_priority_for_class($className);
			if ($item->hasMethod('SitemapAbsoluteURL')) {
				$item->SitemapAbsoluteURL = $SitemapAbsoluteURL->SitemapAbsoluteURL();
				$output->push($item);
			}
			else if ($item->hasMethod('Link')) {
				$item->SitemapAbsoluteURL = Director::absoluteURL($item->Link());
				$output->push($item);
			}
		}

		/* Make sure we only include one of each link, and no external links (ie: redirector pages */
		$output->removeDuplicates('SitemapAbsoluteURL');
		$external_links = array();
		$base_url = preg_quote(Director::absoluteBaseURL(), '/');
		foreach ($output as $item) {
			if (!preg_match('/^' . $base_url . '/', $item->SitemapAbsoluteURL)) {
				array_push($external_links, $item->SitemapAbsoluteURL);
			}
		}
		if (count($external_links) > 0) {
			$output = $output->exclude('SitemapAbsoluteURL', $external_links);
		}

		return $output;
	}

	/**
	 * Notifies Google about changes to your sitemap. This behavior is disabled
	 * by default, to enable, read the documentation provided in the docs folder.
	 *
	 * After notifications have been enabled, every publish / unpublish of a page.
	 * will notify Google of the update.
	 *
	 * If the site is in development mode no ping will be sent regardless whether
	 * the Google notification is enabled.
	 *
	 * @return string Response text
	 */
	public static function ping() {
		if(!self::$google_notifications) {
			return false;
		}

		if(!Director::isLive()) {
			return;
		}

		$location = urlencode(Controller::join_links(
			Director::absoluteBaseURL(),
			'sitemap.xml'
		));

		$response = self::send_ping(
			"www.google.com", "/webmasters/sitemaps/ping", sprintf("sitemap=%s", $location)
		);

		return $response;
	}

	/**
	 * Send an HTTP request to the host.
	 *
	 * @return String Response text
	 */
	protected static function send_ping($host, $path, $query) {
		$socket = fsockopen($host, 80, $errno, $error);
		if (!$socket) {
			return $error;
		}
		if ($query) {
			$query = '?' . $query;
		}
		$request = "GET {$path}{$query} HTTP/1.1\r\nHost: $host\r\nConnection: Close\r\n\r\n";
		fwrite($socket, $request);
		$response = stream_get_contents($socket);

		return $response;
	}

}