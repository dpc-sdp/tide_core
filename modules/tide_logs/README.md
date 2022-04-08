# Tide Logs
Provides a SumoLogic handler for Monolog.

## Requirements

  - [Lagoon logs](https://drupal.org/project/lagoon_logs)


## Activation

1. The module requires a working [Sumo Logic OTEL Collector](https://github.com/SumoLogic/sumologic-otel-collector) to which it can send logs.
   1. A helm chart has been created [here](https://github.com/dpc-sdp/sdp-helm-charts/tree/master/charts/sumologic-otel-collector) for easy installation on Kubernetes clusters.
   2. To test locally, in a test project:
      1. Create a file at `.docker/sumologic-otel/config.yaml` with the following content:
          ```yaml
          extensions:
            sumologic:
              # Get from https://service.au.sumologic.com/ui/#/security/installation-tokens
              install_token: <collector-install-token>
               # Highly recommend adding a unique suffix, otherwise you might run into conflicts with other collector instances.
              collector_name: SDP Syslog - OTEL - <random-suffix>
              clobber: true

          receivers:
            udplog:
              listen_address: "0.0.0.0:514"
              attributes:
                source_name: "foobar/otel/test"
              operators:
                - type: json_parser
                - type: metadata
                  id: metadata/source_host
                  if: '"source_host" in $$body'
                  attributes:
                    source_host: 'EXPR($$body.source_host)'
                - type: metadata
                  id: metadata/source_category
                  if: '"source_category" in $$body'
                  attributes:
                    source_category: 'EXPR($$body.source_category)'

          processors: {}

          exporters:
            logging:
              loglevel: debug
            sumologic:
              auth:
                authenticator: sumologic
              source_category: "%{source_category}"
              source_name: "%{source_name}"
              source_host: "%{source_host}"
              metadata_attributes:
                - source.*

          service:
            extensions: [sumologic]
            pipelines:
              logs:
                receivers: [udplog]
                exporters: [sumologic]

          ```
      2. Add a service to `docker-compose.yml` as follows:
          ```yml
          sumo-otel:
            image: "public.ecr.aws/sumologic/sumologic-otel-collector:${SUMO_OTEL_RELEASE_VERSION:-0.47.0-sumo-0}"
            volumes:
              - .docker/sumologic-otel:/etc/otel
            ports:
              - 514
            networks:
              - amazeeio-network
              - default
            labels:
              lagoon.type: none
          ```
      3. Running `ahoy up` will register the instance with Sumo Logic; it's now ready to start collecting logs. Run `docker-compose logs -f sumo-otel` to make sure there were no errors. The last line should be something like
          ```
          Everything is ready. Begin running and processing data.
          ```
2. Enable the Tide Logs module. Go to `/admin/config/development/tide_logs` and ensure `UDPlog host` and `UDPlog port` correspond to the service's name and the port in the `config.yaml` respectively, if running with docker-compose. The module's default is `udp://sdp-logs.lagoon.svc:5514` (see the default config [here](config/install/tide_logs.settings.yml)).
3. `SUMOLOGIC_CATEGORY` can also be set if a different category from the default (`sdp/dev/tide`) is required.
4. The following search query can then be used in SumoLogic to view the logs:
   ```
   _collector="SDP Syslog - OTEL - <random-suffix>" and _sourceCategory="sdp/dev/tide"
   ```

## Debug

Some very basic debug messages can be printed locally (or remotely if you have drush access) by setting the following config variable:
```sh
drush config:set tide_logs.settings debug 1
```
