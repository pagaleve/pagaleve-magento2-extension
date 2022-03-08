# Pagaleve plugin for Magento 2
Official Pagaleve plugin payments online for Magento 2.

## Integration
The plugin integrates with Pagaleve API.
https://pagaleve.stoplight.io/docs/public-apis/YXBpOjExOTgyMjU4-pagaleve-api

## Requirements
This plugin supports:
- PHP 7.3.0 version and higher.
- Magento2 version 2.3.3 and higher.

## Installation
You can install our plugin through Composer:

```bash
composer require pagaleve/pagaleve-magento2-extension
composer update
bin/magento module:enable Pagaleve_Payment
bin/magento setup:upgrade
```

## Configuration
After installation has completed go to:

Stores > Settings > Configuration

Sales > Payment Methods > Other Payment Methods > Pagaleve.

## Support and Contributing
You can create issues on our Magento Repository.
https://github.com/pagaleve/pagaleve-magento2-extension/issues/new

Pull requests are welcome.
