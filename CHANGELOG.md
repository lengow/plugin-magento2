# Changelog


### Bug Fixes
## [1.7.0](https://github.com/lengow/plugin-magento2/compare/v1.6.10...v1.7.0) (2025-04-01)


### Features

* **orders:** [ECP-88] Bundle product manage ([#24](https://github.com/lengow/plugin-magento2/issues/24)) ([5b6f817](https://github.com/lengow/plugin-magento2/commit/5b6f817b15d60df4c37598302aefed9391ab4913))

## [1.6.10](https://github.com/lengow/plugin-magento2/compare/v1.6.9...v1.6.10) (2025-03-20)
[PCMT-248] : fix config number and dot

* ([276ac5b](https://github.com/lengow/plugin-magento2/commit/276ac5bf62f33ca7170c09a9211bca5183844dbd))
## [1.6.9](https://github.com/lengow/plugin-magento2/compare/v1.6.8...v1.6.9) (2025-03-20)
* **import:** [PCMT-191] option for product matching ([#41](https://github.com/lengow/plugin-magento2/issues/41)) ([22d8d94](https://github.com/lengow/plugin-magento2/commit/22d8d9478cd6594206ddc28e585ed86a3c6eb616))

## [1.6.8](https://github.com/lengow/plugin-magento2/compare/v1.6.7...v1.6.8) (2025-02-24)


### Bug Fixes

* **tax:** [PCMT-178] fix the function name ([#39](https://github.com/lengow/plugin-magento2/issues/39)) ([50b76cc](https://github.com/lengow/plugin-magento2/commit/50b76ccfa2dfeddac0f923cd598de2841d317391))

## [1.6.7](https://github.com/lengow/plugin-magento2/compare/v1.6.6...v1.6.7) (2024-10-28)


### Bug Fixes

* **import:** [PST-21918] fix NoSuchEntityException when customer has no default address ([#35](https://github.com/lengow/plugin-magento2/issues/35)) ([905e07b](https://github.com/lengow/plugin-magento2/commit/905e07bbcc8f5299801b62d20391502b73f51ad6))
* **settings:** [PST-21910] change preprod to sandbox word ([#34](https://github.com/lengow/plugin-magento2/issues/34)) ([d77cd3e](https://github.com/lengow/plugin-magento2/commit/d77cd3e6ba03516a3159649475a96d77d104cdc0))

## [1.6.6](https://github.com/lengow/plugin-magento2/compare/v1.6.5...v1.6.6) (2024-10-14)


### Miscellaneous

* **lengow:** [PST-21841] rate limit request for lengow API at 500 per minutes
* **ci-cd:** automatically update release-please version in files ([#30](https://github.com/lengow/plugin-magento2/issues/30)) ([f597212](https://github.com/lengow/plugin-magento2/commit/f597212a950c9e3604b2d6182fe3407769eb724e))
* **cicd:** Add a CI job to generate plugin checksums ([#31](https://github.com/lengow/plugin-magento2/issues/31)) ([1a966b4](https://github.com/lengow/plugin-magento2/commit/1a966b484bbc1c170edac1b7438796bac82760c6))

## [1.6.5](https://github.com/lengow/plugin-magento2/compare/v1.6.4...v1.6.5) (2024-09-23)


### Bug Fixes

* **lengow:** [ECP-107] Change lengow's logo for the new logo ([#28](https://github.com/lengow/plugin-magento2/issues/28)) ([87306fb](https://github.com/lengow/plugin-magento2/commit/87306fbc13e9e6a955799299242815e754b419d5))


### Miscellaneous
use a generator to fetch orders from lengow instead of fetching all at once

## [1.6.4](https://github.com/lengow/plugin-magento2/compare/v1.6.2...v1.6.4) (2024-08-28)

### Features

* **cicd:** [INFRA-2890] Setup a basic CI ([#22](https://github.com/lengow/plugin-magento2/issues/22)) ([5704361](https://github.com/lengow/plugin-magento2/commit/57043611bd597bb2808c631a7603889e26256636))


### Bug Fixes

* Release 1.6.4 ([#25](https://github.com/lengow/plugin-magento2/issues/25)) ([75aaa3d](https://github.com/lengow/plugin-magento2/commit/75aaa3df87cce34aadc9a1fd5dab2c91d049ace1))


### Miscellaneous

* **clean:** Precise Changelog format ([5704361](https://github.com/lengow/plugin-magento2/commit/57043611bd597bb2808c631a7603889e26256636))
* **clean:** Remove obsolete files ([5704361](https://github.com/lengow/plugin-magento2/commit/57043611bd597bb2808c631a7603889e26256636))

## Changelog

=============================================================
Version 1.6.3
=============================================================
- BugFix: fix blank page when order has not status history
- Feature: change api plan to api restrictions
- Feature: config for anonymize emails and encrypt anonymized emails
=============================================================
Version 1.6.2
=============================================================
 - BugFix: credit memo amounts (refund)
 - Feature: log php error shutdown
=============================================================
Version 1.6.1
=============================================================
- BugFix: Rounding amounts en Lengow Orders
- BugFix: Rounding amounts on credit memo (refund)
- BugFix: Name parser on fullName (amazon orders)
- BugFix: FBA orders not imported from amazon_us
- BUgFix: Matching carrier for manomano
- Feature: Disable Lengow Tracker
- Feature:  Add phone number to the order information in the "Lengow" tab
=============================================================
Version 1.6.0
=============================================================
- Feature: Return tracking management during shipment (Zalando and Otto)
- BugFix: No shippingTax for b2b orders (tax exempted)
=============================================================
Version 1.5.2
=============================================================
 - Feature: manage out of stock products export by configuration
 - BugFix: fixed help center constants urls
 - BugFix: B2B without tax for UE
=============================================================
Version 1.5.1
=============================================================
 - BugFix: Tax on quote creation when include tax config
 - BugFix: Plugin rules applier type array|Collection
 - BugFix: Customer email empty in billing json node or package delivery
=============================================================
Version 1.5.0
=============================================================
 - BugFix: Default address when customer has new address
 - BugFix: Matching carrier not found return code
 - BugFix: Sync order number with Lengow api. 5 tries
 - Feature: Cronjob for try to resend actions in errors 7 days max
=============================================================
Version 1.4.9
=============================================================
 - Feature: partial_refunded new state to accept

=============================================================
Version 1.4.8
=============================================================
 - Feature: B2b without tax config scopable to storeviews

=============================================================
Version 1.4.7
=============================================================
 - Feature: Switch environment prod/pre-prod from Configration Admin
 - BugFix:  Get plugin version from composer.json
 - BugFix:  Tax calculation

=============================================================
Version 1.4.6
=============================================================
 - Feature: Update VAT number in billingAddress, OrderBillingAddress, Customer
 - BugFix:  Fix tax amount round in the quote creation
 - BugFix:  Fix the matchCarrier
 - BugFix:  Fix the getPluginVersion

=============================================================
Version 1.4.5
=============================================================

 - Feature: [toolbox] Toolbox file changed details
 - Feature: [import] Log import params initialized
 - Feature: [import] Option anonymized email
 - Bugfix:   Vat number sync update
 - Bugfix:   Order duplicate when delivery_address_id changes
 - Bugfix:   php8.1 address strings data not be null for trim() etc ...
 - Bugfix:   Replace Zend_Mail by Laminas\Mail
 - BugFix:   Export child products attributes values

=============================================================
Version 1.4.4
=============================================================

    - BugFix: [Import] Get email for send notification from module when value is null
    - BugFix: [Import] Use Lamina_validator instead Send_validator for email validation
    - BugFix: [Import] Unblocking import of customer address in orders
    - BugFix: [Import] Fix error when importing orders caused by the CollectionFactory class by use array instead
    - BugFix: [Import] Fix errors data is null when using the character replacement function
    - BugFix: [Export] Fix error when user save catalog linked to CMS in first connexion
    - BugFix: [Export] Use ProductInterface instead of ProductInterceptor

=============================================================
Version 1.4.3
=============================================================

    - BugFix: [Import] Unblocking the CMS catalogue synchronisation for php 8.1
    - BugFix: [Import] Unblocking import of customer name in orders for php 8.1

=============================================================
Version 1.4.2
=============================================================

    - Feature: Removal of compatibility with Magento versions lower than 2.3
    - Feature: Adding the PHP version in the toolbox
    - Feature: Modification of the fallback urls of the Lengow Help Center
    - Feature: Adding extra field update date in external toolbox
    - BugFix: [Import] Registering a specific customer group for a new customer
    - BugFix: [Export] Fix a type error sometime happening while retrieving product shipping cost
    - BugFix: [Export] Convert custom attribute values to string
    - BugFix: [Import] Loading of order types at each order synchronization
    - BugFix: [Import] Checks if multi-stock is activated when sending the order

=============================================================
Version 1.4.1
=============================================================

    - BugFix: [Import] Unblocking the synchronization process when an order is refunded

=============================================================
Version 1.4.0
=============================================================

    - Feature: Integration of order synchronization in the toolbox webservice
    - Feature: Retrieving the status of an order in the toolbox webservice
    - Feature: Removal of compatibility with Magento 2.0

=============================================================
Version 1.3.2
=============================================================

    - BugFix: [Export] Fix retrieval of parent data on child products
    - BugFix: [Import] Replacing the special price with a custom price when importing orders

=============================================================
Version 1.3.1
=============================================================

    - Feature: Outsourcing of the toolbox via webservice
    - Feature: Setting up a modal for the plugin update
    - BugFix: [Import] Some catalog rule where still applied on product price when importing order
    - BugFix: [Import] Removal of FPT (Fixed Product Taxes) for Lengow orders

=============================================================
Version 1.3.0
=============================================================

    - Feature: Integration of the new connection process
    - BugFix: [export] Adding a check on the configurable product type
    - BugFix: [export] Unlocking the mass actions of the product grid for Magento 2.4.x versions
    - BugFix: [export] Added security on multi-stock export if the Magento module is disabled

=============================================================
Version 1.2.3
=============================================================

    - Feature: [export] Multi-stock available with new 'quantity_' field
    - Feature: [import] B2B orders can now be imported without taxes (optionnal)
    - Feature: Adding new links to the Lengow Help Center and Support
    - BugFix: Correction of Customer::getName behavior
    - BugFix: New security on pluginIsBlocked() function for php 7.4
    - Bugfix: Always load iframe over https

=============================================================
Version 1.2.2
=============================================================

    - Feature: [import] Addition of order types in the order management screen
    - Feature: [import] Integration of the region code in the delivery and billing addresses
    - Feature: [export] Add option to select which field should be taken from parent products
    - Bugfix: [import] Refactoring of the creation of delivery and billing addresses
    - Bugfix: [import] prevent magento from applying discount in imported orders
    - Bugfix: Update of the access token when recovering an http 401 code

=============================================================
Version 1.2.1
=============================================================

    - Bugfix: Addition of the http 201 code in the success codes

=============================================================
Version 1.2.0
=============================================================

    - Feature: Refactoring and optimization of the connector class
    - Feature: [import] Protection of the import of anonymized orders
    - Feature: [import] Protection of the import of orders older than 3 months
    - Feature: Optimization of API calls for synchronisation of orders and actions
    - Feature: Display of an alert when the plugin is no longer up to date
    - Feature: Renaming from Preprod Mode to Debug Mode
    - Bugfix: [import] Deleting order_date index on lengow_orders table
    - Bugfix: Refactoring and optimization of dates with the correct locale
    - Bugfix: [action] Improved carrier matching with a strict then approximate search
    - Bugfix: [export] Recovery of correct shipping costs for each product
    - Bugfix: [import] Saving the marketplaces.json file in the Magento media folder
    - Bugfix: [import] Enhanced security for orders that change their marketplace name

=============================================================
Version 1.1.5
=============================================================

    - Bugfix: [export] compatibility with php 7.3 for mode size and total

=============================================================
Version 1.1.4
=============================================================

    - Feature: Adding compatibility with php 7.3
    - Bugfix: [import] Tax rate recovery when product prices do not include tax
    - Bugfix: [import] Update address creation for compatibility with version 2.3.3

=============================================================
Version 1.1.3
=============================================================

    - Bugfix: [action] Using factory process for the instantiation of marketplace
    - Bugfix: [export] Dynamic recovery of the entity type id for the product attributes
    - Bugfix: [export] Checks whether an array-form product attribute contains another array
    - Bugfix: [toolbox] Adding security on the recovery of export files

=============================================================
Version 1.1.2
=============================================================

    - Feature: [action] Improved carrier matching with search on carrier code and label
    - Bugfix: [import] Save tracking number during updating process

=============================================================
Version 1.1.1
=============================================================

    - Feature: [import] Optimization of the order recovery system
    - Feature: [import] Setting up a cache for synchronizing catalogs ids
    - Feature: [action] Refactoring and optimization of actions on orders

=============================================================
Version 1.1.0
=============================================================

    - Feature: Disabling the Lengow tracker and changing the product ID
    - Feature: Registering marketplace data in a json file
    - Feature: Optimization of API calls between PrestaShop and Lengow
    - Bugfix: count() parameter must be an array for php 7.2
    - Bugfix: [action] Management of orders waiting to return from the marketplace
    - Bugfix: Update of the lengow_order table directly after the creation of the Magento order

=============================================================
Version 1.0.3
=============================================================

    - Feature: [action] Generating a generic error message when the Lengow API is unavailable
    - Feature: [import] Adding an error when a product does not have enough stock
    - Feature: [import] Import the order with the currency of the marketplace
    - Bugfix: [import] Improved security to avoid duplicate synchronization
    - Bugfix: Initializing an empty array for log decoding

=============================================================
Version 1.0.2
=============================================================

    - Feature: Adding links to the new Lengow help center
    - Bugfix: [import] Changing the cron url with the default store
    - Bugfix: Correction on Lengow models dependency injection
    - Bugfix: [Export] Management of duplicate fields
    - Bugfix: Optimizing settings backup without cleaning the configuration cache
    - Bugfix: [import] Saving tracking data in the lengow_order table
    - Bugfix: [import] Optimizing the creation of the order with the given quote
    - Bugfix: Modifying css classes for compatibility with version 2.3
    - Bugfix: [import] Adding warning when the quote contains disabled products

=============================================================
Version 1.0.1
=============================================================

    - Feature: Adding refunded status to order filters
    - Feature: [export] Loading parent categories for products not visible individually
    - Feature: Protocol change to https for API calls
    - Feature: Managing delivery_date and custom_carrier parameters for sending action
    - Feature: Check and complete an order not imported if it is canceled or refunded
    - Bugfix: Change css style for Lengow order status label
	- Bugfix: [action] Removing of action errors when orders are completed
	- Bugfix: [action] Deleting the shipping_date parameter in the action check request
	- Bugfix: Optimizing the display of errors in the order screen
	- Bugfix: Deleting the indefinite index user_id in the connector
	- Bugfix: [import] Fixed a multiple order import bug after the re-import action
	- Bugfix: [import] Resolving the client creation bug in the case of a multi-website magento
	- Bugfix: [export] Fixed the table name parameter in the clean log action
	- Bugfix: [import] Creating a new track only if the tracking number is present
	- Bugfix: [import] Correction of the from_lengow attribute on the customers

=============================================================
Version 1.0.0
=============================================================

	- Feature: Full compatibility with the new Lengow platform
	- Feature: Lengow Dashboard (statistics, helper center and quick links)
	- Feature: Product page with selection by store
	- Feature: Orders page with a specific screen to manage Lengow orders
	- Feature: Help page with all necessary support links
	- Feature: Toolbox with all Lengow information for support
	- Feature: Added Lengow simple tag on order validation
	- Feature: New management of the settings with the recording of the changes
	- Feature: Account creation and synchronization directly from the module
	- Feature: Management of actions on marketplaces with error recovery
	- Feature: Add new actions: re-import, re-send and re-synchronisation orders
