<?php
/**
 * SiteTree extension to calculate the update "Google Priority"
 *
 * Originally based on github.com/silverstripe-labs/silverstripe-googlesitemaps
 */

class SitemapSiteTreeExtension extends SitemapExtension
{

    /**
     * Ensure that all parent pages of this page (if any) are published
     * @return boolean
     */
    public function hasPublishedParent()
    {
        // Skip root pages
        if (empty($this->owner->ParentID)) {
            return true;
        }

        // Ensure direct parent exists
        $parent = $this->owner->Parent();
        if (empty($parent) || !$parent->exists()) {
            return false;
        }

        // Check ancestry
        return $parent->hasPublishedParent();
    }

    /**
     * @return mixed
     */
    public function getGooglePriority()
    {
        setlocale(LC_ALL, "en_US.UTF8");

        $parentStack = $this->owner->parentStack();
        $numParents = is_array($parentStack) ? count($parentStack) - 1 : 0;

        $num = max(0.1, 1.0 - ($numParents / 10));
        $result = str_replace(",", ".", $num);

        return $result;
    }
}
