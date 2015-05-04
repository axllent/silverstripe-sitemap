# Sitemap generator for SilverStripe 3
This extension adds sitemap functionality to your SilverStripe website. It is heavily based on
[googlesitemaps](https://github.com/silverstripe-labs/silverstripe-googlesitemaps), however does things a little
differently, adding in support for basic custom filters when adding DataObjects, and removing some CMS integration,
pagination of results, and JavaScript support. The sitemap is hard-coded to have a maximum of 1000 dataobjects per
sitemap (due to memory use), and will automatically split your sitemaps into multiple if there are more.

## Requirements
* SilverStripe 3.0+

## Basic usage
Simply install the module. To check the sitemap is working, go to
`http://yoursite.com/sitemap.xml?flush=1`. By default it will add your SiteTree pages (with "show in search" enabled),
however you can also optionally add DataObjects provided they are mapped to URLs.

## Specifying the Sitemap location in your robots.txt file
```
Sitemap: http://yoursite.com/sitemap.xml
```

## Adding DataObjects
DataObjects can easily be added too, assuming of course that those DataObjects have unique URLs on your site.
DatObjects must either have a `Link()` function, or alternatively have a `SitemapAbsoluteURL()` function (which will
override `Link()`). The `SitemapAbsoluteURL()` function must return an absolute URL, `Link()` will get automatically
converted.

To add a DataObject to your sitemap you can simply add the following to your `mysite/_config.php`:
```php
Sitemap::register_dataobject('MyDataObject');
```

Additional options are available:
```php
Sitemap::register_dataobject('MyDataObject', array(
	'filter' => array('ShowOnWeb' => 1),
	'where' => '"ExpiryDate" >= \'' . date('Y-m-d') . '\' OR "ExpiryDate" IS NULL',
	'exclude' => array('StockLevel' => 0),
	'frequency' => 'weekly', // always, hourly, daily, weekly, monthly, yearly, never
	'priority' => 0.5 // Valid values range from 0.0 to 1.0
));
```

## Google Notifications
Publishing & unpublishing of SiteTree pages can automatically send a "ping" to Google. Please note
that this is turned off by default and does not apply to saving of DataObjects. Also note that you must have
registered your sitemap with Google's webmaster tools first.

To enable change notifications to Google:

```php
Sitemap::enable_google_notifications();
```