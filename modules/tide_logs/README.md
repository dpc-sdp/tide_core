# Tide Logs
Provides a SumoLogic handler for Monolog.

## Requirements

  - [Lagoon logs](https://drupal.org/project/lagoon_logs)


## Activation

1. Added the Tide Logs module which contains a SumoLogic handler for Monolog. To activate, enable the module and set the environment variable `SUMOLOGIC_COLLECTOR_CODE`.
2. `SUMOLOGIC_CATEGORY` can also be set if a different category from the default (`sdp/dev/tide`) is required.
3. The following search query can then be used in SumoLogic to view the logs:
   ```
   _source="SDP collector" and _collector="SDP" and _sourceCategory="sdp/dev/tide"
   ```

## Debug

Some very basic debug messages can be printed locally (or remotely if you have drush access) by setting the following config variable:
```
drush config:set tide_logs.settings debug 1
```
