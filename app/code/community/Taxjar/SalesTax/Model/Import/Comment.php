<?php
/**
 * Taxjar_SalesTax
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Taxjar
 * @package    Taxjar_SalesTax
 * @copyright  Copyright (c) 2016 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * TaxJar Extension UI
 * Returns imported states and help info
 */
class Taxjar_SalesTax_Model_Import_Comment
{
    /**
     * Display Nexus states loaded and API Key setting
     *
     * @param void
     * @return string
     */
    public function getCommentText()
    {
        $regionId = Mage::getStoreConfig('shipping/origin/region_id');
        $regionCode = Mage::getModel('directory/region')->load($regionId)->getCode();
        $lastUpdate = Mage::getStoreConfig('taxjar/config/last_update');

        if (!empty($lastUpdate)) {
            $states = unserialize(Mage::getStoreConfig('taxjar/config/states'));
            $statesHtml = $this->buildStatesHtml($states, $regionCode);
            return $this->buildInstalledHtml($statesHtml, $lastUpdate);
        } else {
            return $this->buildNotYetInstalledHtml($this->fullStateName($regionCode));
        }
    }

    /**
     * Get the number of rates loaded
     *
     * @param array $states
     * @return array
     */
    private function getNumberOfRatesLoaded($states)
    {
        $rates = Mage::getModel('tax/calculation_rate');
        $stateRatesLoadedCount = 0;
        $ratesByState = array();

        foreach (array_unique($states) as $state) {
            $regionModel = Mage::getModel('directory/region')->loadByCode($state, 'US');
            $regionId = $regionModel->getId();
            $ratesByState[$state] = $rates->getCollection()->addFieldToFilter('tax_region_id', array('eq' => $regionId))->getSize();
        }

        $rateCalcs = array(
            'total_rates' => array_sum($ratesByState), 
            'rates_loaded' => Mage::getModel('taxjar/import_rate')->getExistingRates()->getSize(),
            'rates_by_state' => $ratesByState
        );

        return $rateCalcs;
    }

    /**
     * Build String from State Abbr
     *
     * @param string $stateCode
     * @return string
     */
    private function fullStateName($stateCode)
    {
        $regionModel = Mage::getModel('directory/region')->loadByCode($stateCode, 'US');
        return $regionModel->getDefaultName();
    }

    /**
     * Build HTML for installed text
     *
     * @param string $statesHtml
     * @param string $lastUpdate
     * @return string
     */
    private function buildInstalledHtml($statesHtml, $lastUpdate)
    {
        $htmlString = "<p class='note'><span>TaxJar is installed. Check the <a href='" . Mage::helper('adminhtml')->getUrl('adminhtml/tax_rule/index') . "'>Manage Tax Rules section</a> to verify all installed states.</span></p><br/><p>TaxJar has automatically added rates for the following states to your Magento installation:<br/><ul class='messages'>". $statesHtml . "</ul>To manage your TaxJar states <a href='https://app.taxjar.com/account#states' target='_blank'>click here</a>.</p><p>Your sales tax rates were last updated on: <ul class='messages'><li class='info-msg'><ul><li><span style='font-size: 1.4em;'>" . $lastUpdate . "</span></li></ul></li></ul><small>Rates may be automatically or manually updated again once per month. For more information on how your tax settings are changed, <a href='http://www.taxjar.com/guides/integrations/magento/' target='_blank'>click here</a>. Contact <a href='mailto:support@taxjar.com'>support@taxjar.com</a> with the email address registered to your TaxJar account if you need assistance.</small></p><p><small>If you would like to uninstall TaxJar, remove the API Token from the box above and save the config. This will remove all TaxJar rates from your Magento store. You can then uninstall in the Magento Connect Manager.<small></p><p><strong>Important Notice</strong>: Your API key may be used to install rates on only <em>one</em> Magento installation at a time.</p>";
        
        return $htmlString;
    }

    /**
     * Build HTML for not yet installed text
     *
     * @param string $regionName
     * @return string
     */
    private function buildNotYetInstalledHtml($regionName)
    {
        $htmlString = "<p class='note'><span>Enter your TaxJar API Token</span></p><br/><p>Enter your TaxJar API Token to import current sales tax rates for all zip codes in <b>" . $regionName . "</b>, your state of origin as set in <a href='" . Mage::helper('adminhtml')->getUrl('adminhtml/system_config/edit/section/shipping') . "'>Shipping Settings</a>. We will also retrieve all other states from your TaxJar account. To get an API Token, go to <a href='https://app.taxjar.com/account' target='_blank'>TaxJar's Account Screen.</a></p><p>For more information on how your tax settings are changed, <a href='http://www.taxjar.com/guides/integrations/magento/' target='_blank'>click here</a>.</p><p><small><strong>Important Notice</strong>: Your API key may be used to install rates on only <em>one</em> Magento installation at a time.</small></p>";
        
        return $htmlString;
    }

    /**
     * Build HTML list of states
     *
     * @param string $states
     * @param string $regionCode
     * @return string
     */
    private function buildStatesHtml($states, $regionCode)
    {
        $states[] = $regionCode;
        $statesHtml = '';

        sort($states);

        $taxRatesByState = $this->getNumberOfRatesLoaded($states);

        foreach (array_unique($states) as $state) {
            if (($stateName = $this->fullStateName($state)) && !empty($stateName)) {
                if ($taxRatesByState['rates_by_state'][$state] == 1 && ($taxRatesByState['rates_loaded'] == $taxRatesByState['total_rates'])) {
                    $totalForState = 'Origin-based rates set';
                    $class = 'success';
                } elseif ($taxRatesByState['rates_by_state'][$state] == 0 && ($taxRatesByState['rates_loaded'] == $taxRatesByState['total_rates'])) {
                    $class = 'error';
                    $totalForState = '<a href="https://app.taxjar.com/account#states" target="_blank">Click here</a> and add a zip code for this state to load rates.';
                } else {
                    $class = 'success';
                    $totalForState = $taxRatesByState['rates_by_state'][$state] . ' rates';
                }

                $statesHtml .= '<li class="' . $class . '-msg"><ul><li><span style="font-size: 1.4em;">' . $stateName . '</span>: ' . $totalForState . '</li></ul></li>'; 
            }
        }

        if ($taxRatesByState['rates_loaded'] != $taxRatesByState['total_rates']) {
            $matches = 'error';
        } else {
            $matches = 'success';
        }

        $statesHtml .= '<p class="' . $matches . '-msg" style="background: none !important;"><small>&nbsp;&nbsp;' . $taxRatesByState['total_rates'] . ' of ' . $taxRatesByState['rates_loaded'] . ' expected rates loaded.</small></p>';

        return $statesHtml;   
    }
}
