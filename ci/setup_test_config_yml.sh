#!/bin/bash

set -e

YML_PARAMETERS_FILE="Tests/App/config/config.yml"

echo "Using ${YML_PARAMETERS_FILE} file."

sed \
  -e 's,redis://localhost,redis://redis,' \
  -i ${YML_PARAMETERS_FILE}
