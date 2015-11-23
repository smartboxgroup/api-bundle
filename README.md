## API bundle
The API bundle allows you to create REST and SOAP APIs in a seamless way. It also auto generates documentation for both APIs

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

# Default configuration for extension with alias: "smartbox_api"
smartbox_api:
    default_controller:   'SmartboxApiBundle:API:handleCall'

    # List of error codes, e.g.::
    # 
    #                400: Bad Request, the request could not be understood by the server due to malformed syntax
    #                401: Unauthorized, the request requires user authentication
    #                403: Forbidden, the server understood the request, but is refusing to fulfill it
    #                404: Not Found, the server has not found anything matching the Request-URI
    #                **The success codes can be extended and changed
    errorCodes:           # Required

        # Prototype
        id:                   ~

    # List of success codes, e.g.::
    # 
    #                   200: Success, the information returned with the response is dependent on the method used in the request
    #                   201: Created, the request has been fulfilled and resulted in a new resource being created
    #                   202: Accepted, the request has been accepted for processing, but the processing has not been completed
    #                   204: No content, the server has fulfilled the request but does not need to return an entity-body
    #                   **The success codes can be extended and changed
    successCodes:         # Required

        # Prototype
        id:                   ~
    services:             # Required

        # Prototype
        id:
            parent:               ~
            name:                 ~ # Required
            version:              ~ # Required
            removed:              []

            # Endpoint definitions.
            #                 Example:
            #                 services:
            #                     demo_v1:
            #                         name: demo
            #                         version: v1
            #                         methods:
            # 
            #                         ## BOXES
            #                             createBox:
            #                                 description: Creates a box with the given information and returns its id
            #                                 successCode: 201
            #                                 input:
            #                                     box: { type: Smartbox\ApiBundle\Tests\Fixtures\Entity\Box, group: update, mode: body }
            #                                 output: { mode: header, type: Smartbox\ApiBundle\Entity\Location }
            #                                 rest:
            #                                     route: /box
            #                                     httpMethod: POST
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

                        # Determines if the parameter goes into the header (header mode, usually for location header) or the body (body mode) of the response
                        mode:                 body
                    rest:                 # Required

                        # Route for the this API method
                        route:                ~ # Required

                        # HTTP verb for this API method
                        httpMethod:           ~ # Required

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

## Contributors
Jose Rufino, Marcin Skurski, Luciano Mammino, Alberto Rodrigo
