<?php
/**
 * MyResearch Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace MZKCatalog\Controller;

use VuFind\Exception\Auth as AuthException,
    VuFind\Exception\ListPermission as ListPermissionException,
    VuFind\Exception\RecordMissing as RecordMissingException,
    VuFind\Controller\MyResearchController as BaseMyResearchController,
    Zend\Stdlib\Parameters;

/**
 * Controller for the user account area.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class MyResearchController extends BaseMyResearchController {
public function checkedOutHistoryAction()
    {
        // Stop now if the user does not have valid catalog credentials available:
        if (!is_array($patron = $this->catalogLogin())) {
            return $patron;
        }

        // Connect to the ILS:
        $catalog = $this->getILS();

        // Get history:
        $result = $catalog->getMyHistory($patron);
        //print_r($result);

        $transactions = array();
        foreach ($result as $current) {
        	// Add renewal details if appropriate:
        	$current = $this->renewals()->addRenewDetails(
        			$catalog, $current, $renewStatus
        	);
        	// Build record driver:
        	$transactions[] = $this->getDriverForILSRecord($current);
        }
        
        return $this->createViewModel(
        		array(
        				'transactions' => $transactions
        		)
        );
    }
}