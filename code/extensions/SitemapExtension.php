<?php
/**
 * Decorate the page object to provide sitemaps
 *
 * Based on github.com/silverstripe-labs/silverstripe-googlesitemaps
 */

class SitemapExtension extends DataExtension
{

    /**
     * The default value of the priority field depends on the depth of the page in
     * the site tree, so it must be calculated dynamically.
     *
     * @return mixed
     */
    public function getGooglePriority()
    {
        return Sitemap::get_priority_for_class($this->owner->class);
    }

    /**
     * Returns a pages change frequency calculated by pages age and number of
     * versions. Google expects always, hourly, daily, weekly, monthly, yearly
     * or never as values.
     *
     * @see http://support.google.com/webmasters/bin/answer.py?hl=en&answer=183668&topic=8476&ctx=topic
     *
     * @return SS_Datetime
     */
    public function getChangeFrequency()
    {
        if ($freq = Sitemap::get_frequency_for_class($this->owner->class)) {
            return $freq;
        }

        $date = date('Y-m-d H:i:s');

        $created = new SS_Datetime();
        $created->value = ($this->owner->Created) ? $this->owner->Created : $date;

        $now = new SS_Datetime();
        $now->value = $date;

        $versions = ($this->owner->Version) ? $this->owner->Version : 1;
        $timediff = $now->format('U') - $created->format('U');

        // Check how many revisions have been made over the lifetime of the
        // Page for a rough estimate of it's changing frequency.
        $period = $timediff / ($versions + 1);

        if ($period > 60 * 60 * 24 * 365) {
            $freq = 'yearly';
        } elseif ($period > 60 * 60 * 24 * 30) {
            $freq = 'monthly';
        } elseif ($period > 60 * 60 * 24 * 7) {
            $freq = 'weekly';
        } elseif ($period > 60 * 60 * 24) {
            $freq = 'daily';
        } elseif ($period > 60 * 60) {
            $freq = 'hourly';
        } else {
            $freq = 'always';
        }

        return $freq;
    }

    /*
     * @return void
     */
    public function onAfterPublish()
    {
        Sitemap::ping();
    }

    /*
     * @return void
     */
    public function onAfterUnpublish()
    {
        Sitemap::ping();
    }
}
