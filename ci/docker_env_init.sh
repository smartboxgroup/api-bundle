#!/bin/bash

set -e

if [ "_" != "_$CI_COMPOSER_KEY" ]
then
    mkdir -p $HOME/.composer
    PARTA='{"github-oauth": {"github.com": "'
    PARTB='"}}'
    echo $PARTA$CI_COMPOSER_KEY$PARTB > $HOME/.composer/auth.json
    echo "+ Composer Key installed"
else
  echo "+ Warning: No Key Defined for composer, use CI_COMPOSER_KEY"
fi

#
# Load SSH deployment key for getting to private repos
# You need the ssh-agent already running
#
mkdir -p $HOME/.ssh
chmod 700 $HOME/.ssh
echo -e "Host *\n\tStrictHostKeyChecking no\n\n" > $HOME/.ssh/config
if [ "_" != "_$SSH_AGENT_PID" ]
then
  if [ "_" != "_$CI_SSH_PRIVATE_KEY" ]
  then
    ssh-add <(echo "$CI_SSH_PRIVATE_KEY")
  else
    echo "Warning: No deployment key defined for SSH use CI_SSH_PRIVATE_KEY to define the deploy key"
  fi
else
  echo "Warning: No SSH Agent is running, we will no be able to run composer with private repos"
fi
