## API bundle

The API bundle allows you to create REST and SOAP APIs in a seamless way. It also auto generates documentation for both APIs

[![Build Status](https://travis-ci.com/smartboxgroup/api-bundle.svg?branch=master)](https://travis-ci.com/smartboxgroup/api-bundle)

## Installation and usage
To install the bundle, you just need to:

1. Add the repository to composer as:

```
   "require": {
     "smartbox/api-bundle": "dev-master"
   }
   "repositories": [
      {
        "type": "vcs",
        "url":  "git@gitlab.production.smartbox.com/:smartesb/api-bundle.git"
      }
    ],
```

2. Add it to your AppKernel.php file

3. Add to your config.yml file:
  imports:
      - { resource: "@SmartboxApiBundle/Resources/config/config.yml" }

4. Ammend the previous config.yml file to determine your endpoints. You can see the output by running:
```
  php console.php config:dump-reference smartbox-api
```

```
# Default configuration for extension with alias: "smartbox_api"
smartbox_api:

    # Id of user provider service which implements Symfony\Component\Security\Core\User\UserProviderInterface
    #
    #     f.e.: security.user.provider.concrete.in_memory
    #
    userProvider:         ~ # Required
    default_controller:   'SmartboxApiBundle:API:handleCall'

    # Enable/Disable throttling (dafault: false).
    #
    #     If throttling is enabled:
    #         - register bundles in AppKernel.php:
    #             new Noxlogic\RateLimitBundle\NoxlogicRateLimitBundle(),
    #             new Snc\RedisBundle\SncRedisBundle(),
    #         - configure bundles:
    #             noxlogic_rate_limit:
    #                 storage_engine:             redis
    #                 redis_client:               default_client
    #                 rate_response_message:      'You exceeded the rate limit'
    #                 display_headers:            true
    #
    #             snc_redis:
    #                 clients:
    #                     default:
    #                         type: predis
    #                         alias: default
    #                         dsn: redis://localhost
    #
    throttling:           false

    # List of error codes, e.g.::
    #
    #     400: Bad Request, the request could not be understood by the server due to malformed syntax
    #     401: Unauthorized, the request requires user authentication
    #     403: Forbidden, the server understood the request, but is refusing to fulfill it
    #     404: Not Found, the server has not found anything matching the Request-URI
    #     **The success codes can be extended and changed
    errorCodes:           # Required

        # Prototype
        id:                   ~

    # List of success codes, e.g.::
    #
    #     200: Success, the information returned with the response is dependent on the method used in the request
    #     201: Created, the request has been fulfilled and resulted in a new resource being created
    #     202: Accepted, the request has been accepted for processing, but the processing has not been completed
    #     204: No content, the server has fulfilled the request but does not need to return an entity-body
    #     **The success codes can be extended and changed
    successCodes:         # Required

        # Prototype
        id:                   ~

    # List of response codes were the APIBundle should enforce an empty body, e.g.: [301,202]
    restEmptyBodyResponseCodes:  [] # Required
    services:             # Required

        # Prototype
        id:
            parent:               ~
            name:                 ~ # Required
            version:              ~ # Required

            # The SOAP headers namespace need to be defined.
            soapHeadersNamespace:  ~ # Required

            # List of http headers to propagate as SOAP envelope headers in SOAP calls, e.g.::
            #
            #     X-RateLimit-Limit: rateLimitLimit
            #
            #     The headers must be present in order to be propagated. In this example the header is present when activating throttling
            propagateHttpHeadersToSoap:

                # Prototype
                id:                   ~
            removed:              []

            # Endpoint definitions.
            #     Example:
            #     services:
            #         demo_v1:
            #             name: demo
            #             version: v1
            #             methods:
            #
            #             ## BOXES
            #                 createBox:
            #                     description: Creates a box with the given information and returns its id
            #                     successCode: 201
            #                     input:
            #                         box: { type: Smartbox\ApiBundle\Tests\Fixtures\Entity\Box, group: update, mode: body }
            #                     rest:
            #                         route: /box
            #                         httpMethod: POST
            #                     throttling:
            #                         limit: 10
            #                         period: 60
            #
            methods:              # Required

                # Prototype
                name:

                    # Success code to be returned
                    successCode:          200

                    # Description of the method, it will be used in the documentation
                    description:          ~ # Required

                    # Controller to handle the requests to this method
                    controller:           default
                    roles:

                        # Prototype
                        role:                 ~
                    defaults:

                        # Prototype
                        key:                  ~

                    # Section where the input parameters are specified.
                    input:

                        # Prototype
                        name:

                            # The description of the parameter, it will be used in the documentation.
                            description:          ''

                            # The type of the input, it accepts scalar types (integer, double, string), entities (e.g.: MyNamespace\MyEntity) and arrays of them (integer[], MyNamespace\MyEntity[])
                            type:                 ~ # Required

                            # The group of the entity to be used, acts as a view of the entity model, determines the set of attributes to be used.
                            group:                public

                            # Defines if the parameter is a requirement, filter or the body.\nBody: There can be only one input as the body,\n and it must be an Entity or array of entities.\nRequirement: Requirements are scalar parameters which are required.\nFilter: Filters are scalar parameters which are optional.
                            mode:                 requirement

                            # Regex with the format for the parameter, e.g.: d+
                            format:               '[a-zA-Z0-9]+'

                    # Section where the output parameters are specified.
                    output:

                        # The type of the output, it accepts only entities (e.g.: MyNamespace\MyEntity) and arrays of them (MyNamespace\MyEntity[])
                        type:                 ~ # Required

                        # The group of the entity to be used, acts as a view of the entity model, determines the set of attributes to be used.
                        group:                public
                    rest:                 # Required

                        # Route for the this API method
                        route:                ~ # Required

                        # HTTP verb for this API method
                        httpMethod:           ~ # Required

                    # Throttling works only when smartbox_api.throttling is set to true.
                    #
                    #     Response headers:
                    #         X-RateLimit-Limit:          Limit of requests in a time window
                    #         X-RateLimit-Remaining:      Remaining requests in a time window
                    #         X-RateLimit-Reset:          The remaining window before the rate limit resets in UTC epoch seconds
                    #     Response status code when limit is exceeded: 429 Too Many Requests
                    #
                    throttling:

                        # Set limit of requests in specified period.
                        limit:                ~ # Required

                        # Set period to limit requests.
                        period:               ~ # Required
```

5. Add to your routing.yml file:

```
  smartbox_api:
      resource: "@SmartboxApiBundle/Resources/config/routing.yml"
      prefix:   /api
```

6. Create your own bundle

7. Create a controller class extending from APIController like:

```
  class APIController extends \Smartbox\ApiBundle\Controller\APIController
  {
      public function handleCallAction($serviceId, $serviceName, $methodConfig, $version, $methodName, $input)
      {

        // Checks authorization
        $this->checkAuthorization();

        // Check input
        $inputsConfig = $methodConfig['input'];
        $this->checkInput($version, $inputsConfig, $input);

        return $this->respond($response);
    }
  }
```

## Tools

### smartbox:api:generateSDK
Generate SDK for a given API.

**Usage:** ```php app/console smartbox:api:generateSDK --help```

### smartbox:api:export-list
Exports api and flows list into csv files. By default will be exported to /tmp

**Usage:** ```php app/console smartbox:api:export-list --help"```

**Examples:** 
* ```php app/console smartbox:api:export-list --export-dir "/tmp"```
* ```php app/console smartbox:api:export-list --async false --http-method GET```
* ```php app/console smartbox:api:export-list --async false --role ROLE_USER```

### smartbox:api:generate-soapui
Generate a sample SoapUI project for a flow, using the api defined in a YAML test file.

**Usage:** ```php app/console smartbox:api:generate-soapui --help```

**Examples:** 
* ```php app/console smartbox:api:generate-soapui -f \"Product/sendProductInformation\" -p ganesh-tt-one17.smartbox-test.local"```

### smartbox:api:dumpPrintable 
Dump the api documentation as a printable html file.

**Usage:** ```php app/console smartbox:api:dumpPrintable```

## Contributing
1. Fork it!
2. Create your feature branch: `git checkout -b my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin my-new-feature`
5. Submit a pull request :D

## Tests

Check out the small test app within Tests/Fixtures/



## History
v0.1.0: fixed small bug in ApiConfigurator
v0.1.1: fixed small typo in template folder name for documentation
v0.1.2: improvement for the documentation template for SOAP calls
v0.1.3: added missing entity Ok
v0.1.4: Improved support for arrays of entities as attribute of another entity
v0.1.5: Readme.md file creation
v0.1.6: Added WSI compliance
v0.1.7: Removed exception handling for non rest calls
v0.1.8: Added throttling
v0.1.9: Added header propagation to SOAP

## Contributors
Jose Rufino, Marcin Skurski, Luciano Mammino, Alberto Rodrigo
