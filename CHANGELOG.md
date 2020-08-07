# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.0.5] - 2020-08-07
- Update the "transaction sync" setting to use the current store's scope
- Update the shipping origin to only use the first line in the street address

## [3.0.4] - 2020-05-15
- SmartCalcs Sales Tax API is now simplified to TaxJar Sales Tax API or TaxJar API.
- Fix a typo in several class names

## [3.0.3] - 2020-05-01
- Improve error handling during transaction sync
- Update product tax categories to remove the plus_only flag

## [3.0.2] - 2020-03-02
- Update the gift card product tax category to be more accurate.
- Add a custom user agent string and Referer header to all API requests.

## [3.0.1] - 2020-02-05
- Improve support for M2ePro users when syncing multi-channel orders to TaxJar
- Improve storing product tax categories by storing them in their own table

## [3.0.0] - 2019-11-01
- **Real-time transaction sync for Magento 1 now available.** If you were previously connected to TaxJar through our app, we’ll automatically unlink your store and begin pushing orders through our API. If you were previously using our beta, orders will continue to be pushed through our API.
- Improve security by removing the now deprecated SOAP API users/roles.
- Make provider default to Magento for better reporting inside TaxJar.
- Replace serialize/unserialize functions with JSON encode/decode.

## [2.4.2] - 2019-07-31
- Products set to "None" tax class will no longer pass a fully exempt `99999` tax code for calculations and transaction sync in order to support AutoFile.
- Add description to product tax class field explaining that a TaxJar category is required to exempt products from sales tax.

## [2.4.1] - 2019-01-22
- Make nexus address fields (street, city, zip) optional for remote sellers / economic nexus.
- Fix error when syncing nexus addresses with an incomplete TaxJar business profile.
- Rename empty product category label from "None" to "Fully Taxable".
- Include fixed product taxes in total tax amount.

## [2.4.0] - 2018-10-11
- Add calculation logging when debug mode is enabled.
- Improve backup rate sync performance up to 25%.
- Fix calculations for bundle product quantities caused by regression in 2.3.7.

## [2.3.7] - 2018-07-02
- Fix discounts applied to shipping amounts for calculations.
- Fix calculations on newly added cart items if redirect to cart after adding a product is turned off.
- Fix zip validation for backup rates.
- Fix PHP 7 type error when running scheduled backup rate sync.

## [2.3.6] - 2018-01-17
- Increase API request timeout to 30 seconds for merchants with large volume of backup rates.
- Fully exempt tax when product tax class is set to "None".
- Fix backup rates field comment typo.

## [2.3.5] - 2017-08-08
- Fix calculations for fixed price bundle products.
- Add note to "TaxJar Exempt" field for customer exemptions.

## [2.3.4] - 2017-05-23
- Fix child item quantity undefined offset error.
- Improve US region validation for backup rates and tax configuration.

## [2.3.3] - 2017-02-17
- Fix child item quantity calculation for bundle items when parent quantity is > 1.

## [2.3.2] - 2017-01-24
- Improve checkout calculation support for bundle products.

## [2.3.1] - 2016-08-29
- Add manage account and sales tax report buttons to configuration.
- Create reporting API user and role immediately after connecting to TaxJar.
- Import TaxJar product categories immediately after connecting to TaxJar.
- Fix multi-store connection issue with SID param.
- Fix non-SSL store connection issue.

## [2.3.0] - 2016-08-09
- Exempt specific customer tax classes from SmartCalcs requests.
- Support product tax codes in Core API salesOrderInfo response.

## [2.2.1] - 2016-07-17
- Support Magento EE gift card tax exemption.
- Tweak calculation requests to rely directly on line items for upcoming SmartCalcs accuracy check.

## [2.2.0] - 2016-06-30
- **Special promo sales tax calculations for Magento merchants.** You must upgrade to this version to receive special promo calculations at checkout using our new API endpoint.

## [2.1.2] - 2016-06-24
- Fix calculations with associated items in configurable/bundled products.
- Fix calculations fallback for earlier versions of Magento CE before 1.8.
- Add tax by line item rather than subtotal to prevent rounding issues.
- Fix nexus check for international locations.

## [2.1.1] - 2016-06-16
- Fix nexus upgrade script for v2.0.1 users.
- Purge nexus addresses on disconnect for switching accounts.
- Package nexus address template form.phtml.

## [2.1.0] - 2016-06-10
- **Nexus addresses can now be managed in Magento under Sales > Tax > Nexus Addresses.** If upgrading from a previous version and using checkout calculations, make sure you sync your existing addresses from TaxJar or set up a new address. Your addresses will automatically sync with TaxJar when added or changed.
- **International support for SmartCalcs checkout calculations.** One nexus address per country outside of US/CA is currently supported for [more than 30 countries](https://developers.taxjar.com/api/reference/#countries).
- Review nexus addresses for missing data and set up observer to report tax configuration issues.
- Report errors when using AJAX sync backup rate button in the TaxJar configuration.

## [2.0.1] - 2016-05-30
- Separate SmartCalcs shipping tax amount for orders and invoices.
- Fix display issue after connecting to TaxJar with caching enabled.
- Minor bug fixes for older versions of PHP and strict standards.

## [2.0.0] - 2016-05-22
- **Moved TaxJar configuration to System > Configuration > Tax.** This is a breaking change with new configuration fields. If upgrading from a previous version, you'll need to re-connect to TaxJar and re-enable your settings.
- Streamlined configuration with focus on SmartCalcs for upcoming free calculations and zip-based rates as a fallback.
- New "Connect to TaxJar" button for faster onboarding.
- New select fields for assigning backup tax rules to custom product and customer tax classes.
- New AJAX sync button for manually refreshing backup rates from TaxJar.
- Admin notifications tied to our RSS feed for extension updates and news.

[Unreleased]: https://github.com/taxjar/taxjar-magento-extension/compare/v3.0.5...HEAD
[3.0.5]: https://github.com/taxjar/taxjar-magento-extension/compare/v3.0.4...v3.0.5
[3.0.4]: https://github.com/taxjar/taxjar-magento-extension/compare/v3.0.3...v3.0.4
[3.0.3]: https://github.com/taxjar/taxjar-magento-extension/compare/v3.0.2...v3.0.3
[3.0.2]: https://github.com/taxjar/taxjar-magento-extension/compare/v3.0.1...v3.0.2
[3.0.1]: https://github.com/taxjar/taxjar-magento-extension/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.4.2...v3.0.0
[2.4.2]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.4.1...v2.4.2
[2.4.1]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.4.0...v2.4.1
[2.4.0]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.7...v2.4.0
[2.3.7]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.6...v2.3.7
[2.3.6]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.5...v2.3.6
[2.3.5]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.4...v2.3.5
[2.3.4]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.3...v2.3.4
[2.3.3]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.2...v2.3.3
[2.3.2]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.1...v2.3.2
[2.3.1]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.3.0...v2.3.1
[2.3.0]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.2.1...v2.3.0
[2.2.1]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.2.0...v2.2.1
[2.2.0]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.1.2...v2.2.0
[2.1.2]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.1.1...v2.1.2
[2.1.1]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.0.1...v2.1.0
[2.0.1]: https://github.com/taxjar/taxjar-magento-extension/compare/v2.0.0...v2.0.1
[2.0.0]: https://github.com/taxjar/taxjar-magento-extension/compare/v1.6.1...v2.0.0
