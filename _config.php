<?php
// add the extension to pages
if (class_exists('SiteTree')) {
	SiteTree::add_extension('SitemapSiteTreeExtension');
}