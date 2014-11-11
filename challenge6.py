#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
# Alex Edwards
# Nov, 2014
# challenge6.py
#
# This script will create a backup of a Cloud Database.
# I assume this is to be done via the API, and the API
# does not allow you to backup using a specific db/user.
#
# Since this one sounded easy I decided to do it without a SDK 
# ...for bragging rights?
# Requires Requests
#

from __future__ import print_function
import json
import sys
import six
try:
    import requests
except ImportError:
    print("This script requires requests, install with pip install requests")
    sys.exit()
try:
    import configparser
except ImportError:
    import ConfigParser as configparser

class cdb:
    def __init__(self, username, apikey):
        # Grab our API Key on Intilization
        self.__creds = self.__auth(username, apikey)
    
    def __auth(self, user, apikey):    
        # Tell it what we're sending/receiving
        token_headers = { 'Accept': 'application/json',
                    'Content-Type': 'application/json'}

        # POST our login creds
        token_login = { 
            'auth': {
                'RAX-KSKEY:apiKeyCredentials': {
                    'username': user,
                    'apiKey': apikey
                }
            }
        }
        
        # Send POST to identity endpoint
        token = requests.post('https://identity.api.rackspacecloud.com/v2.0/tokens', 
                              data=json.dumps(token_login), 
                              headers=token_headers, verify=False)
        token = json.loads(token.text)
        
        try:
            apitoken = token["access"]["token"]["id"]
            # Why ["access"]["user"]["id"] returns token I will never know...We grab the last user "role" here to get the proper uid
            ID = token["access"]["user"]["roles"][len(token["access"]["user"]["roles"])-1]["tenantId"]
        except KeyError:
            print("Incorrect login credentials provided.")
            sys.exit()
    
        # Return the token
        return [apitoken, ID]

    def backup(self, name, instance, desc):
        # Get our creds
        apiToken = self.__creds[0]
        accountID = self.__creds[1]

        # Set our header to auth for this endpoint
        header = { 'X-Auth-Token': apiToken,
                   'Accept': 'application/json',
                   'Content-Type': 'application/json' }

        # Post data required for backing it up
        backup_data = { 
                        'backup': {
                            'name': name,
                            'instance': instance,
                            'description': desc 
                        }
                      }


        dbBackup = requests.post('https://ord.databases.api.rackspacecloud.com/v1.0/' + accountID + '/backups', headers=header, data=json.dumps(backup_data), verify=False)
        dbBackup = json.loads(dbBackup.text)

        try:
            return dbBackup["backup"]
        except KeyError:
            print("Something went wrong, here's the return data: ")
            print(dbBackup["instanceFault"])
            sys.exit()



    def list(self):
        # Get our creds
        apiToken = self.__creds[0]
        accountID = self.__creds[1]
        
        # Set our header to auth for this endpoint
        header = { 'X-Auth-Token': apiToken,
                   'Accept': 'application/json',
                   'Content-Type': 'application/json' }

        dbList = requests.get('https://ord.databases.api.rackspacecloud.com/v1.0/' + accountID + '/instances', headers=header, verify=False)
        dbList = json.loads(dbList.text)

        return dbList["instances"]



# Get our creds for the authenticating
config_file="./.rackspace_credentials"
config = configparser.ConfigParser()
config.read(config_file)
apiUser = config.get('rackspace', 'username').replace('"', '')
apiKey = config.get('rackspace', 'apikey').replace('"', '')

myCdb = cdb(apiUser, apiKey)
cdbList = myCdb.list()

# List the db's
for i in range(len(cdbList)):
    print("[" + str(i) + "] " + cdbList[i]["name"])

# Get number to backup
while True:
    backupNumber = int(six.moves.input("Select number to backup: "))
    if ( backupNumber <= len(cdbList) ):
        break
    else:
        print("Incorrect entry, try again.")

backup = myCdb.backup("My backup", cdbList[backupNumber]["id"], "A cool description")

print("Backed up!")
