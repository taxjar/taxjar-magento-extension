# Magento Sales Tax Extension by TaxJar

Simplify your sales tax with live checkout calculations and zip-based backup rates from [TaxJar](http://www.taxjar.com).

To get started, check out our [extension guide](http://www.taxjar.com/guides/integrations/magento/)!

## Getting Started

Before installing you'll want to [set up a TaxJar account](https://app.taxjar.com/sign_up), configure your [nexus states](http://blog.taxjar.com/sales-tax-nexus-definition/) and generate an API token. Our [guide](http://www.taxjar.com/guides/integrations/magento/) will show you exactly how.

Now comes the really fun part. Install our extension one of four ways:

- Install via [Magento Connect](https://marketplace.magento.com/taxjar-taxjar-salestaxautomation.html).
- Upload the latest `tgz` package from `/var/connect/` using the Magento Connect Manager.
- Manually upload the `/app` files to your server.
- Use [magento-composer-installer](https://github.com/Cotya/magento-composer-installer) to install via [Composer](https://getcomposer.org).

From there it's smooth sailing. Simply paste in your API token, click **Save Config** and we'll import zip-based rates for states where you have nexus. If you'd like more accurate rates that go beyond the zip code, enable the TaxJar API for live checkout calculations.

## Development

Our extension is set up for local development with [modman](https://github.com/colinmollenhour/modman).
