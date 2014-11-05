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
 * Heavily based on github.com/silverstripe-labs/silverstripe-googlesitemaps
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

	public static function exclude_sitetree() {
		self::$include_sitetree = false;
	}

	public static function enable_google_notifications() {
		self::$google_notifications = false;
	}

	public static function is_registered($className) {
		return isset(self::$dataobjects[$className]);
	}

	public static function unregister_dataobject($className) {
		if (self::is_registered($className)) {
			unset(self::$dataobjects[$className]);
		}
	}

	/**
	 * Returns the string frequency of edits for a particular dataobject class.
	 * Frequency for {@link SiteTree} objects can be determined from the version history.
	 * @param string
	 * @return string
	 */
	public static function get_frequency_for_class($class) {
		if (self::is_registered($class) && isset(self::$dataobjects[$class]['frequency'])) {
			return self::$dataobjects[$class]['frequency'];
		}
	}

	/**
	 * Returns the default priority of edits for a particular dataobject class.
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
	 * @param null
	 * @return ArrayList
	 */
	public static function get_sitemaps() {

		self::init_sitetree();

		$sitemaps = new ArrayList();

		if(count(self::$dataobjects) > 0) {

			foreach(self::$dataobjects as $class => $config) {
				$items = self::get_items($class)->Count();
				if ($items > 0) {
					$list = new DataList($class);
					$latest = $list->max('LastEdited');

					$sitemaps->push(new ArrayData(array(
						'ClassName' => $class,
						'LastEdited' => date('Y-m-d', strtotime($latest))
					)));
				}
			}
		}

		return $sitemaps;
	}

	/**
	 * Return all items in class
	 * Supports additional filtering (filter, where & exclude)
	 * Only returns items that contain Link()
	 * @param string
	 * @return ArrayList
	 */
	public static function get_items($className) {

		self::init_sitetree();

		if (!self::is_registered($className)) {
			return false;
		}

		$class = self::$dataobjects[$className];

		$output = new ArrayList();

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
		$list = $list->sort('LastEdited DESC');

		/* only push items with a link */
		foreach ($list as $item) {
			$item->ChangeFrequency = self::get_frequency_for_class($className);
			$item->GooglePriority = self::get_priority_for_class($className);
			if ($item->hasMethod('SitemapAbsoluteURL')) {
				$output->push($item);
			}
			else if ($item->hasMethod('Link')) {
				$item->SitemapAbsoluteURL = Director::absoluteURL($item->Link());
				$output->push($item);
			}
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
