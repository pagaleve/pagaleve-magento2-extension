# Pagaleve plugin for Magento 2
Official Pagaleve plugin payments online for Magento 2.

## Integration
The plugin integrates with Pagaleve API.
https://pagaleve.stoplight.io/docs/public-apis/YXBpOjExOTgyMjU4-pagaleve-api

## Requirements
This plugin supports:
- PHP 7.4.0 version and higher.
- Magento2 version 2.4.5 and higher.

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
