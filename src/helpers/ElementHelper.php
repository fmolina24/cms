<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\helpers;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\errors\OperationAbortedException;
use yii\base\Exception;

/**
 * Class ElementHelper
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ElementHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Sets a valid slug on a given element.
     *
     * @param ElementInterface $element
     *
     * @return void
     */
    public static function setValidSlug(ElementInterface $element)
    {
        /** @var Element $element */
        $slug = $element->slug;

        if (!$slug) {
            // Create a slug for them, based on the element's title.
            // Replace periods, underscores, and hyphens with spaces so they get separated with the slugWordSeparator
            // to mimic the default JavaScript-based slug generation.
            $slug = str_replace(['.', '_', '-'], ' ', $element->title);

            // Enforce the limitAutoSlugsToAscii config setting
            if (Craft::$app->getConfig()->get('limitAutoSlugsToAscii')) {
                $slug = StringHelper::toAscii($slug);
            }
        }

        $element->slug = static::createSlug($slug);
    }

    /**
     * Creates a slug based on a given string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function createSlug(string $str): string
    {
        // Remove HTML tags
        $str = StringHelper::stripHtml($str);

        // Convert to kebab case
        $glue = Craft::$app->getConfig()->get('slugWordSeparator');
        $lower = !Craft::$app->getConfig()->get('allowUppercaseInSlug');
        $str = StringHelper::toKebabCase($str, $glue, $lower, false);

        return $str;
    }

    /**
     * Sets the URI on an element using a given URL format, tweaking its slug if necessary to ensure it's unique.
     *
     * @param ElementInterface $element
     *
     * @throws OperationAbortedException
     */
    public static function setUniqueUri(ElementInterface $element)
    {
        /** @var Element $element */
        $uriFormat = $element->getUriFormat();

        // No URL format, no URI.
        if ($uriFormat === null) {
            $element->uri = null;

            return;
        }

        // No slug, or a URL format with no {slug}, just parse the URL format and get on with our lives
        if (!$element->slug || !static::doesUriFormatHaveSlugTag($uriFormat)) {
            $element->uri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element);

            return;
        }

        $uniqueUriConditions = [
            'and',
            ['siteId' => $element->siteId],
            '[[uri]]=:uri'
        ];

        if ($element->id) {
            $uniqueUriConditions[] = ['not', ['elementId' => $element->id]];
        }

        $slugWordSeparator = Craft::$app->getConfig()->get('slugWordSeparator');
        $maxSlugIncrement = Craft::$app->getConfig()->get('maxSlugIncrement');

        for ($i = 0; $i < $maxSlugIncrement; $i++) {
            $testSlug = $element->slug;

            if ($i > 0) {
                $testSlug .= $slugWordSeparator.$i;
            }

            $originalSlug = $element->slug;
            $element->slug = $testSlug;

            $testUri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element);

            // Make sure we're not over our max length.
            if (strlen($testUri) > 255) {
                // See how much over we are.
                $overage = strlen($testUri) - 255;

                // Do we have anything left to chop off?
                if (strlen($overage) > strlen($element->slug) - strlen($slugWordSeparator.$i)) {
                    // Chop off the overage amount from the slug
                    $testSlug = $element->slug;
                    $testSlug = substr($testSlug, 0, -$overage);

                    // Update the slug
                    $element->slug = $testSlug;

                    // Let's try this again.
                    $i--;
                    continue;
                } else {
                    // We're screwed, blow things up.
                    throw new OperationAbortedException('Could not find a unique URI for this element');
                }
            }

            $totalElements = (new Query())
                ->from(['{{%elements_i18n}}'])
                ->where($uniqueUriConditions, [':uri' => $testUri])
                ->count('[[id]]');

            if ($totalElements == 0) {
                // OMG!
                $element->slug = $testSlug;
                $element->uri = $testUri;

                return;
            } else {
                $element->slug = $originalSlug;
            }
        }

        throw new OperationAbortedException('Could not find a unique URI for this element');
    }

    /**
     * Returns whether a given URL format has a proper {slug} tag.
     *
     * @param string $uriFormat
     *
     * @return bool
     */
    public static function doesUriFormatHaveSlugTag(string $uriFormat): bool
    {
        $element = (object)['slug' => StringHelper::randomString()];
        $uri = Craft::$app->getView()->renderObjectTemplate($uriFormat, $element);

        return StringHelper::contains($uri, $element->slug);
    }

    /**
     * Returns a list of sites that a given element supports.
     *
     * Each site is represented as an array with 'siteId' and 'enabledByDefault' keys.
     *
     * @param ElementInterface $element
     *
     * @return array
     * @throws Exception if any of the element's supported sites are invalid
     */
    public static function supportedSitesForElement(ElementInterface $element): array
    {
        $sites = [];

        foreach ($element->getSupportedSites() as $site) {
            if (!is_array($site)) {
                $site = [
                    'siteId' => $site,
                ];
            } else if (!isset($site['siteId'])) {
                throw new Exception('Missing "siteId" key in '.get_class($element).'::getSupportedSites()');
            }
            $sites[] = array_merge([
                'enabledByDefault' => true,
            ], $site);
        }

        return $sites;
    }

    /**
     * Returns whether the given element is editable by the current user, taking user permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return bool
     */
    public static function isElementEditable(ElementInterface $element): bool
    {
        if ($element->getIsEditable()) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::supportedSitesForElement($element) as $siteInfo) {
                    if (Craft::$app->getUser()->checkPermission('editSite:'.$siteInfo['siteId'])) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the editable site IDs for a given element, taking user permissions into account.
     *
     * @param ElementInterface $element
     *
     * @return array
     */
    public static function editableSiteIdsForElement(ElementInterface $element): array
    {
        $siteIds = [];

        if ($element->getIsEditable()) {
            if (Craft::$app->getIsMultiSite()) {
                foreach (static::supportedSitesForElement($element) as $siteInfo) {
                    if (Craft::$app->getUser()->checkPermission('editSite:'.$siteInfo['siteId'])) {
                        $siteIds[] = $siteInfo['siteId'];
                    }
                }
            } else {
                $siteIds[] = Craft::$app->getSites()->getPrimarySite()->id;
            }
        }

        return $siteIds;
    }

    /**
     * Given an array of elements, will go through and set the appropriate "next"
     * and "prev" elements on them.
     *
     * @param ElementInterface[] $elements The array of elements.
     *
     * @return void
     */
    public static function setNextPrevOnElements(array $elements)
    {
        /** @var ElementInterface $lastElement */
        $lastElement = null;

        foreach ($elements as $i => $element) {
            if ($lastElement) {
                $lastElement->setNext($element);
                $element->setPrev($lastElement);
            } else {
                $element->setPrev(false);
            }

            $lastElement = $element;
        }

        if ($lastElement) {
            $lastElement->setNext(false);
        }
    }

    /**
     * Returns an element type's source definition based on a given source key/path and context.
     *
     * @param string      $elementType The element type class
     * @param string      $sourceKey   The source key/path
     * @param string|null $context     The context
     *
     * @return array|null The source definition, or null if it cannot be found
     */
    public static function findSource(string $elementType, string $sourceKey, string $context = null)
    {
        /** @var string|ElementInterface $elementType */
        $path = explode('/', $sourceKey);
        $sources = $elementType::sources($context);

        while (!empty($path)) {
            $key = array_shift($path);
            $source = null;

            foreach ($sources as $testSource) {
                if (isset($testSource['key']) && $testSource['key'] === $key) {
                    $source = $testSource;
                    break;
                }
            }

            if ($source === null) {
                return null;
            }

            // Is that the end of the path?
            if (empty($path)) {
                return $source;
            }

            // Prepare for searching nested sources
            $sources = $source['nested'] ?? [];
        }

        return null;
    }
}
