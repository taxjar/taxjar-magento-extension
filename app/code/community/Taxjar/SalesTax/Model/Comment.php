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
 * @copyright  Copyright (c) 2019 TaxJar. TaxJar is a trademark of TPS Unlimited, Inc. (http://www.taxjar.com)
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */

/**
 * TaxJar Extension UI
 * Returns TaxJar account with connect buttons
 */
class Taxjar_SalesTax_Model_Comment
{
    private $_apiKey;
    private $_apiEmail;

    /**
     * Display Nexus states loaded and API Key setting
     *
     * @param void
     * @return string
     */
    public function getCommentText()
    {
        $this->_apiKey = Mage::getStoreConfig('tax/taxjar/apikey');
        $this->_apiEmail = Mage::getStoreConfig('tax/taxjar/email');

        if ($this->_apiKey) {
            return $this->_buildConnectedHtml();
        } else {
            return $this->_buildDisconnectedHtml();
        }
    }

    /**
     * Build connected HTML
     *
     * @param void
     * @return string
     */
    private function _buildConnectedHtml()
    {
        $disconnectUrl = Mage::helper('adminhtml')->getUrl('adminhtml/taxjar/disconnect');

        $htmlString = "<p class='note'><span>Sales tax calculations at checkout for improved accuracy and product exemptions. Magento's zip-based rates can be used as a fallback.</span></p><br/>";
        $htmlString .= "<p class='note'><span>TaxJar Account</span></p>";
        $htmlString .= "<ul class='messages'><li class='success-msg'><span style='font-size: 1.4em'>" . $this->_apiEmail . "</span></li></ul>";
        $htmlString .= <<<EOT
        <p><button type='button' class='scalable' onclick='window.open("https://app.taxjar.com/account", "_blank")'><span>Manage Account</span></button>&nbsp;&nbsp;or&nbsp;&nbsp;<a href='#' onclick='if (window.confirm("Are you sure you want to disconnect from TaxJar? This will remove all TaxJar rates from your Magento store. If you have a paid TaxJar subscription, manage your account at https://app.taxjar.com.")) window.location="{$disconnectUrl}"; return false'>Disconnect TaxJar</a></p><br/>
EOT;
        $htmlString .= "<p class='note'><span>Getting Started</span></p><p></p>";
        $htmlString .= "<p><a href='" . Mage::helper('adminhtml')->getUrl('adminhtml/tax_nexus/index') . "'>Nexus Addresses</a><br/><span style='font-size: 0.9em'>Before enabling TaxJar for calculations, set up your nexus addresses so TaxJar knows where to collect sales tax.</span></p>";
        $htmlString .= "<p><a href='" . Mage::helper('adminhtml')->getUrl('adminhtml/tax_class_product/index') . "'>Product Tax Classes</a><br/><span style='font-size: 0.9em'>If some of your products are tax-exempt, assign a TaxJar category tax code for new or existing product tax classes.</span></p>";
        $htmlString .= "<p><a href='http://www.taxjar.com/guides/integrations/magento/' target='_blank'>Extension User Guide</a><br/><span style='font-size: 0.9em'>Read our comprehensive Magento guide on how to configure your store properly for sales tax calculations and reporting.</span></p>";
        $htmlString .= "<p><a href='http://www.taxjar.com/contact/' target='_blank'>Help & Support</a><br/><span style='font-size: 0.9em'>Need help setting up TaxJar? Get in touch with our Magento sales tax experts.</span></p><br/>";
        $htmlString .= <<<EOT
        <p><button type='button' class='scalable' onclick='window.open("https://app.taxjar.com", "_blank")'><span>View Sales Tax Reports</span></button></p>
EOT;
        $htmlString .= "<p class='note'>Import your Magento transactions into TaxJar for automated sales tax reporting and filing.</p><br/>";

        return $htmlString;
    }

    /**
     * Build disconnected HTML
     *
     * @param void
     * @return string
     */
    private function _buildDisconnectedHtml()
    {
        $htmlString = "<p class='note'><span>Sales tax calculations at checkout for improved accuracy and product exemptions. Magento's zip-based rates can be used as a fallback.</p>";
        $htmlString .= $this->_buildConnectionHtml();
        return $htmlString;
    }

    /**
     * Build HTML for connect/disconnect buttons
     *
     * @param void
     * @return string
     */
    private function _buildConnectionHtml()
    {
        $authUrl = 'https://app.taxjar.com';
        $popupUrl = $authUrl . '/smartcalcs/connect/magento/?store=' . urlencode($this->_getStoreOrigin());
        $guideUrl = 'http://www.taxjar.com/guides/integrations/magento/';
        $connectUrl = Mage::helper('adminhtml')->getUrl('adminhtml/taxjar/connect');
        $connectUrl .= (parse_url($connectUrl, PHP_URL_QUERY) ? '&' : '?');
        $pluginVersion = Mage::getConfig()->getModuleConfig('Taxjar_SalesTax')->version;

        $popupUrl .= '&plugin=magento&version=' . $pluginVersion . '&email=' . $this->_getStoreGeneralEmail();

        $htmlString = <<<EOT
        <br/><p><button type='button' class='scalable' onclick='openConnectPopup("{$popupUrl}", "Connect to TaxJar", 400, 500)'><span>Connect to TaxJar</span></button>&nbsp;&nbsp;<button type='button' class='scalable' onclick='window.open("{$guideUrl}", "_blank")'><span>Learn More</span></button></p>
        <script>
            function openConnectPopup(url, title, w, h) {
                var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
                var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;
                var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
                var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
                var left = ((width / 2) - (w / 2)) + dualScreenLeft;
                var top = ((height / 2) - (h / 2)) + dualScreenTop;

                window.connectPopup = window.open(url, title, 'scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

                if (window.focus) window.connectPopup.focus();
            }

            window.addEventListener('message', function(e) {
                if (e.origin !== '{$authUrl}')
                    return;

                try {
                    var data = JSON.parse(e.data);
                    if (data.api_token && data.email) {
                        window.connectPopup.postMessage('Data received', '{$authUrl}');
                        window.location = encodeURI('{$connectUrl}api_key=' + data.api_token + '&api_email=' + data.email);
                    } else {
                        throw 'Invalid data';
                    }
                } catch(e) {
                    alert('Invalid API token or email provided. Please try connecting to TaxJar again or contact support@taxjar.com.');
                }
            }, false);
        </script>
EOT;

        return $htmlString;
    }

    /**
     * Get current store origin
     *
     * @param void
     * @return string
     */
    private function _getStoreOrigin()
    {
        $protocol = Mage::app()->getRequest()->isSecure() ? 'https://' : 'http://';
        return $protocol . $_SERVER['HTTP_HOST'];
    }

    /**
     * Get store general contact email if non-default
     *
     * @param void
     * @return string
     */
    private function _getStoreGeneralEmail()
    {
        $email = Mage::getStoreConfig('trans_email/ident_general/email');
        if ($email != 'owner@example.com') {
            return $email;
        } else {
            return '';
        }
    }
}
