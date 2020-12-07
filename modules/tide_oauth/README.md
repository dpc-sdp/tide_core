## Tide OAuth

### Prerequisites
1. Consumers module: https://www.drupal.org/project/consumers
2. Simple OAuth module: https://www.drupal.org/project/simple_oauth

### Installation
1. _(Optional)_ Generate a key pair and set environment variables
    ```shell script
    $ openssl genrsa -out /tmp/private.key 4096
    $ openssl rsa -in /tmp/private.key -pubout > /tmp/public.key
    $ export TIDE_OAUTH_PRIVATE_KEY=`cat /tmp/private.key`
    $ export TIDE_OAUTH_PUBLIC_KEY=`cat /tmp/public.key`
    ```
2. Enable the `tide_oauth` module.
    * If the TIDE_OAUTH_ environment variables are set, the module will copy
    the keys to `private://oauth.key` and `private://oauth.pub`.
    * Otherwise, the module will generate a new key pair.
3. _(Optional - **Lagoon only**)_ Add a `post-rollout` task to generate the OAuth
    key pair from environment variables upon deployment.
    ```yaml
    tasks:
       post-rollout:
          - run:
             name: Generate OAuth keys from ENV variables.
             command: 'drush tide-oauth:keygen'
             service: cli
    ```    

### Authentication
1. See the documentation of [Simple OAuth2](https://www.drupal.org/node/2843627)
2. Due to both JWT Authentication module and Simple OAuth module accept 
    `Authorization: Bearer {TOKEN}` header, Tide OAuth provides extra headers:
    * `Authorization: OAuth2 {TOKEN}`     
    * `X-OAuth2-Authorization: Bearer {TOKEN}`
    * `X-OAuth2-Authorization: OAuth2 {TOKEN}` 
    
    When Tide Authenticated Content or JWT module is enabled, all OAuth2 
    authentication calls should one of the custom headers as the normal 
    `Authorization` header is always authenticated against JWT Authentication.
3. By default, the module creates a client `Editorial Preview` with the scope
    `editor`. All OAuth2 authentication requests using this client will have
    permissions of the `Editor` role.
4. OAuth2 endpoints:
    * Authorization URL: `/oauth/authorize`
    * Access token URL: `/oauth/token`
5. Default expiration time:
    * Access token: 5 minutes
    * Authorization code: 5 minutes
    * Refresh token: 2 weeks
