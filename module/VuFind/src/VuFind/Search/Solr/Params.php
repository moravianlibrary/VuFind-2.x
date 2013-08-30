<?php
/**
 * Solr aspect of the Search Multi-class (Params)
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
namespace VuFind\Search\Solr;

/**
 * Solr Search Parameters
 *
 * @category VuFind2
 * @package  Search_Solr
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.vufind.org  Main Page
 */
class Params extends \VuFind\Search\Base\Params
{
    /**
     * Facet result limit
     *
     * @var int
     */
    protected $facetLimit = 30;

    /**
     * Offset for facet results
     *
     * @var int
     */
    protected $facetOffset = null;

    /**
     * Prefix for facet searching
     *
     * @var string
     */
    protected $facetPrefix = null;

    /**
     * Sorting order for facet search results
     *
     * @var string
     */
    protected $facetSort = null;
    
    /**
     * array of multi facets (Results_Settings->multiselect_facets in facets.ini)
     * 
     * @var array
     */
    protected $multiSelectFacets = array();
    
    /**
     * Constructor
     *
     * @param \VuFind\Search\Base\Options  $options      Options to use
     * @param \VuFind\Config\PluginManager $configLoader Config loader
     */
    public function __construct($options, \VuFind\Config\PluginManager $configLoader)
    {
        parent::__construct($options, $configLoader);

        // Use basic facet limit by default, if set:
        $config = $configLoader->get('facets');
        if (isset($config->Results_Settings->facet_limit)
            && is_numeric($config->Results_Settings->facet_limit)
        ) {
            $this->setFacetLimit($config->Results_Settings->facet_limit);
        }
        
        if (isset($config->Results_Settings->multiselect_facets) ) {
            $this->setMultiselectFacets($config->Results_Settings->multiselect_facets);
        }
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        // Define Filter Query
        $filterQuery = $this->getOptions()->getHiddenFilters();
        $orFilterQuery = array();
        foreach ($this->filterList as $field => $filter) {
            foreach ($filter as $value) {
                // Special case -- allow trailing wildcards and ranges:
                if (substr($value, -1) == '*'
                    || preg_match('/\[[^\]]+\s+TO\s+[^\]]+\]/', $value)
                ) {
                    if (in_array($field,$this->multiSelectFacets)) {
                        $orFilterQuery[$fileld][] = $field.':'.$value;
                    } else {
                        $filterQuery[] = $field.':'.$value;
                    }
                } else {
                    if (in_array($field,$this->multiSelectFacets)) {
                        $orFilterQuery[$field][]  = $field.':"'.addcslashes($value, '"\\').'"';
                    } else {
                        $filterQuery[] = $field.':"'.addcslashes($value, '"\\').'"';
                    }
                }
            }
        }
        
        if (!empty($orFilterQuery) ) {
            foreach ($orFilterQuery as $filter => $value) {
               $filterQuery[] = '{!tag='.$filter.'_filter}'. implode(' OR ', $value); 
           }
        }
        return $filterQuery;
    }

    /**
     * Return current facet configurations
     *
     * @return array $facetSet
     */
    public function getFacetSettings()
    {
        // Build a list of facets we want from the index
        $facetSet = array();
        if (!empty($this->facetConfig)) {
            $facetSet['limit'] = $this->facetLimit;
            foreach ($this->facetConfig as $facetField => $facetName) {
                if (in_array($facetField, $this->multiSelectFacets)) {
                   $facetSet['field'][] = '{!ex=' . $facetField . '_filter}' . $facetField;
                } else {
                    $facetSet['field'][] = $facetField;
                }
            }
            if ($this->facetOffset != null) {
                $facetSet['offset'] = $this->facetOffset;
            }
            if ($this->facetPrefix != null) {
                $facetSet['prefix'] = $this->facetPrefix;
            }
            if ($this->facetSort != null) {
                $facetSet['sort'] = $this->facetSort;
            } else {
                // No explicit setting? Set one based on the documented Solr behavior
                // (index order for limit = -1, count order for limit > 0)
                // Later Solr versions may have different defaults than earlier ones,
                // so making this explicit ensures consistent behavior.
                $facetSet['sort'] = ($this->facetLimit > 0) ? 'count' : 'index';
            }
        }
        return $facetSet;
    }

    /**
     * Initialize the object's search settings from a request object.
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initSearch($request)
    {
        // Special case -- did we get a list of IDs instead of a standard query?
        $ids = $request->get('overrideIds', null);
        if (is_array($ids)) {
            $this->setQueryIDs($ids);
        } else {
            // Use standard initialization:
            parent::initSearch($request);
        }
    }

    /**
     * Set Facet Limit
     *
     * @param int $l the new limit value
     *
     * @return void
     */
    public function setFacetLimit($l)
    {
        $this->facetLimit = $l;
    }

    /**
     * Set Facet Offset
     *
     * @param int $o the new offset value
     *
     * @return void
     */
    public function setFacetOffset($o)
    {
        $this->facetOffset = $o;
    }

    /**
     * Set Facet Prefix
     *
     * @param string $p the new prefix value
     *
     * @return void
     */
    public function setFacetPrefix($p)
    {
        $this->facetPrefix = $p;
    }

    /**
     * Set Facet Sorting
     *
     * @param string $s the new sorting action value
     *
     * @return void
     */
    public function setFacetSort($s)
    {
        $this->facetSort = $s;
    }
    
    public function setMultiselectFacets ($multiselectFacets) {
        $this->multiSelectFacets = explode(',', $multiselectFacets);

    }

    /**
     * Initialize facet settings for the specified configuration sections.
     *
     * @param string $facetList     Config section containing fields to activate
     * @param string $facetSettings Config section containing related settings
     *
     * @return bool                 True if facets set, false if no settings found
     */
    protected function initFacetList($facetList, $facetSettings)
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('facets');
        if (!isset($config->$facetList)) {
            return false;
        }
        foreach ($config->$facetList as $key => $value) {
            $this->addFacet($key, $value);
        }
        if (isset($config->$facetSettings->facet_limit)
            && is_numeric($config->$facetSettings->facet_limit)
        ) {
            $this->setFacetLimit($config->$facetSettings->facet_limit);
        }
        return true;
    }

    /**
     * Initialize facet settings for the advanced search screen.
     *
     * @return void
     */
    public function initAdvancedFacets()
    {
        $this->initFacetList('Advanced', 'Advanced_Settings');
    }

    /**
     * Initialize facet settings for the home page.
     *
     * @return void
     */
    public function initHomePageFacets()
    {
        // Load Advanced settings if HomePage settings are missing (legacy support):
        if (!$this->initFacetList('HomePage', 'HomePage_Settings')) {
            $this->initAdvancedFacets();
        }
    }

    /**
     * Initialize facet settings for the standard search screen.
     *
     * @return void
     */
    public function initBasicFacets()
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('facets');
        if (isset($config->ResultsTop)) {
            foreach ($config->ResultsTop as $key => $value) {
                $this->addFacet($key, $value);
            }
        }
        if (isset($config->Results)) {
            foreach ($config->Results as $key => $value) {
                $this->addFacet($key, $value);
            }
        }
    }

    /**
     * Load all available facet settings.  This is mainly useful for showing
     * appropriate labels when an existing search has multiple filters associated
     * with it.
     *
     * @param string $preferredSection Section to favor when loading settings; if
     * multiple sections contain the same facet, this section's description will
     * be favored.
     *
     * @return void
     */
    public function activateAllFacets($preferredSection = false)
    {
        // Based on preference, change the order of initialization to make sure
        // that preferred facet labels come in last.
        if ($preferredSection == 'Advanced') {
            $this->initHomePageFacets();
            $this->initBasicFacets();
            $this->initAdvancedFacets();
        } else {
            $this->initHomePageFacets();
            $this->initAdvancedFacets();
            $this->initBasicFacets();
        }
    }

    /**
     * Add filters to the object based on values found in the request object.
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void
     */
    protected function initFilters($request)
    {
        // Use the default behavior of the parent class, but add support for the
        // special illustrations filter.
        parent::initFilters($request);
        switch ($request->get('illustration', -1)) {
        case 1:
            $this->addFilter('illustrated:Illustrated');
            break;
        case 0:
            $this->addFilter('illustrated:"Not Illustrated"');
            break;
        }

        // Check for hidden filters:
        $hidden = $request->get('hiddenFilters');
        if (!empty($hidden) && is_array($hidden)) {
            foreach ($hidden as $current) {
                $this->getOptions()->addHiddenFilter($current);
            }
        }
    }

    /**
     * Override the normal search behavior with an explicit array of IDs that must
     * be retrieved.
     *
     * @param array $ids Record IDs to load
     *
     * @return void
     */
    public function setQueryIDs($ids)
    {
        // No need for spell checking or highlighting on an ID query!
        $this->getOptions()->spellcheckEnabled(false);
        $this->getOptions()->disableHighlighting();

        // Special case -- no IDs to set:
        if (empty($ids)) {
            return $this->setOverrideQuery('NOT *:*');
        }

        $callback = function ($i) {
            return '"' . addcslashes($i, '"') . '"';
        };
        $ids = array_map($callback, $ids);
        $this->setOverrideQuery('id:(' . implode(' OR ', $ids) . ')');
    }

    /**
     * Get the maximum number of IDs that may be sent to setQueryIDs (-1 for no
     * limit).
     *
     * @return int
     */
    public function getQueryIDLimit()
    {
        $config = $this->getServiceLocator()->get('VuFind\Config')->get('config');
        return isset($config->Index->maxBooleanClauses)
            ? $config->Index->maxBooleanClauses : 1024;
    }
}