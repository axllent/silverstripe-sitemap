<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type='text/xsl' href='{$BaseHref}silverstripe-sitemap/templates/sitemapindex.xsl'?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><% loop $Sitemaps %>
	<sitemap>
		<loc>$SitemapAbsoluteURL</loc>
		<% if $LastEdited %><lastmod>$LastEdited</lastmod><% end_if %>
	</sitemap><% end_loop %>
</sitemapindex>