# Pagaleve plugin for Magento 2
Official Pagaleve plugin payments online for Magento 2.

## Integration
The plugin integrates with Pagaleve API.
https://pagaleve.stoplight.io/docs/public-apis/YXBpOjExOTgyMjU4-pagaleve-api

## Requirements
This plugin version 1.4.x supports:
- PHP 8.1 version and higher.
- Magento 2 version 2.4.6 and higher.

This plugin version 1.3.x supports:
- PHP 7.4.0 version and higher.
- Magento 2 version 2.4.x to 2.4.5

This plugin version 1.2.x or higher supports:
- PHP 7.3.0 version and higher.
- Magento 2 version 2.3.4 to 2.3.7.

## Installation
To install our plugin through Composer::
```bash
composer require pagaleve/pagaleve-magento2-extension
composer update
bin/magento module:enable Pagaleve_Payment
bin/magento setup:upgrade
```
To install our plugin without Composer:
```bash
download zip
extract file in app/code/Pagaleve/Payment
```

## Configuration
After installation has completed go to:

Stores > Settings > Configuration

Sales > Payment Methods > Other Payment Methods > Pagaleve.

## Uninstall
If you installed our plugin through Composer:
```bash
composer remove pagaleve/pagaleve-magento2-extension
composer update
bin/magento setup:upgrade
```
If you haven't installed our plugin through Composer::
```bash
rm app/code/Pagaleve/ -rf
bin/magento setup:upgrade
```

## Support and Contributing
You can create issues on our Magento Repository.
https://github.com/pagaleve/pagaleve-magento2-extension/issues/new

Pull requests are welcome.
