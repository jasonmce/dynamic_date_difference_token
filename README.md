# Dynamic Date Difference Token for Drupal

A Drupal module that provides a type of dynamic token for Drupal that can be used to change text values over time.  It is a plugin for the [Dynamic Token Manager](https://www.drupal.org/project/dynamic_token_manager) module.

Given a date, the token will return the difference between the date and the current date in a human readable format of Years to 8 decimal places.

## Note!
This is only a plugin module.  It depends on the [Dynamic Token Manager](https://www.drupal.org/project/dynamic_token_manager) module.

## History

I have had this effect on my personal website's [Profile](https://www.JasonMcEachen.com/profile) page for a while.  I originally did it with hard coded javascript embeded into the raw source of the page to show my age as a dynamic value of years to eight decimal places.  I finally got around to refactoring it into a module.

## Table of contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Using Tokens](#using-tokens)
- [API](#api)
- [Unit Tests](#unit-tests)
- [Maintainers](#maintainers)

## Features

- Create reusable text tokens with static or dynamic values
- Configure tokens with default values and overrides
- Integrates with Drupal's Token system and Dynamic Token Manager
- Gracefully cross fades between values

## Requirements

- Drupal >= 10.x
- Dynamic token manager module

## Installation

1. Add the package to your composer file as a repository:

```json
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "jasonmce/dynamic_token_manager",
                "version": "^1.0",
                "type": "drupal-module",
                "source": {
                    "url": "https://github.com/jasonmce/dynamic_token_manager.git",
                    "type": "git",
                    "reference": "main"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "jasonmce/dynamic_date_difference_token",
                "version": "^1.0",
                "type": "drupal-module",
                "source": {
                    "url": "https://github.com/jasonmce/dynamic_date_difference_token.git",
                    "type": "git",
                    "reference": "main"
                }
            }
        },
```

2. Install the module using Composer:

```bash
composer require jasonmce/dynamic_date_difference_token:^1.0
```

3. Enable the module through the Drupal admin interface or with Drush:

```bash
drush en dynamic_date_difference_token
```

## Configuration

### To Add Dynamic Tokens to a site:

1. Navigate to Administration > Configuration > Content Authoring > Dynamic Tokens.
    /admin/config/content/dynamic-tokens
2. To add a Dynamic Token select "Add dynamic token".
      - label will be shown in administrative lists, and used to generate the token machine name.
      - speed determines the displayed token refresh rate.
      - Select "Dynamic Date Difference Token" from the select pulldown.
      - Provide a date value whose difference from the current date will be displayed.
      - @todo Provide a format string to format the difference value.
3. Save.


## Using Tokens

### Tokens module

If you enable the Tokens module, you can use your dynamic tokens anywhere Drupal tokens are supported.

### Filter module

If you enable the Filter module, you will be able to use your dynamic tokens in content fields that use a Text Format which has the "Dynamic Tokens" filter enabled.


```
[dynamic:my_token_name]
```

### Programmatic Usage

```php
// Get token value
$value = \Drupal::service('dynamic_date_difference_token.manager')->getTokenValue('my_token_name');

// Or in a service-injected class:
$value = $this->dynamicTokenManager->getTokenValue('my_token_name');
```

## API

### Hooks

- `hook_dynamic_token_info_alter(&$tokens)` - Modify available tokens
- `hook_dynamic_token_value_alter(&$value, $token_name, $context)` - Modify token values

### Services

- `dynamic_text_token.manager` - Main service for token operations

## Unit tests

```bash
# Run all tests
../vendor/bin/phpunit -c core modules/custom/dynamic_text_token/tests

# Run a specific test group
../vendor/bin/phpunit -c core --group=dynamic_text_token
```

## License

This project is GPL v2 software. See the LICENSE.txt file in this directory for complete text.

## Maintainers

- Jason McEachen - https://www.drupal.org/u/jasonmce

## Credits

- Sponsored by [BrightShinyObject, inc](https://www.brightshinyobject.com)
