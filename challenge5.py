#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
# Alex Edwards
# Nov, 2014
# challenge5.py
#
# This script creates a Cloud Database instance, if it already exists it prompts a different name.
# It will also create X number of Databases and X number of users in the instance.
# It will then return the Cloud DB URL
#

from __future__ import print_function

try:
    import configparser
except ImportError:
    import ConfigParser as configparser
import os
import pyrax
import six
import sys
import time

# Setup our credentials
config_file="./.rackspace_credentials"
config = configparser.ConfigParser()
config.read(config_file)
username = config.get('rackspace', 'username').replace('"', '')
apikey = config.get('rackspace', 'apikey').replace('"', '')

pyrax.set_setting("identity_type", "rackspace")
pyrax.set_credentials(username, apikey)

# Get our Cloud Database object
cdb = pyrax.cloud_databases
instance_name = pyrax.utils.random_ascii(8)

# Set name for instance
instanceNames = cdb.list()
# Counter for if name already exists
cdbNamei = 0
# Check if name is valid
cdbNamev = False
cdbName = six.moves.input("Enter a name for your new instance: ")
while True:
    if( instanceNames ):
        for instance in instanceNames:
            if ( cdbName == instance.name ):
                print("That instace name is already in use!")
                cdbNamei+=1 # add to counter
                answer = six.moves.input("I can try " + cdbName + str(cdbNamei) + " for you. Y to continue, anything else to exit: ")
                if ( answer.upper() == "Y" ):
                    cdbName = cdbName + str(cdbNamei)
                else:
                    sys.exit()
                cdbNamev = False
                break
            else:
                cdbNamev = True
    else:
        cdbNamev = True

    if ( cdbNamev ):
        break
    else:
        print()

# Select flavor
flavors = cdb.list_flavors()
print("Available Flavors:")
for pos, flavor in enumerate(flavors):
    print("%s: %s, %s" % (pos, flavor.name, flavor.ram))
while True:
    try:
        flav = int(six.moves.input("Select a Flavor for your new instance: "))
        cdbFlavor = flavors[flav]
        break
    except IndexError:
        print("Invalid selection\n")
    except ValueError:
        print()

# Select size
while True:
    try:
        cdbSize = int(six.moves.input("Enter the volume size in GB (1-50): "))
        if ( cdbSize >= 1 and cdbSize <= 50 ):
            break
        else:
            print()
    except ValueError:
        print()

instance = cdb.create(cdbName, flavor=cdbFlavor, volume=cdbSize)

print("Creating instance")
# Check if built
while True:
    instance = cdb.get(instance.id)
    if( instance.status == "ACTIVE" ):
        break
    else:
        time.sleep(5)

print("Instance " + cdbName + " created at " + instance.hostname)

numDBs = six.moves.input("How many databases/users should be made in this instance: ")
if ( numDBs > 0 ):
    for i in range(1,int(numDBs)+1):
        instance.create_database("database" + str(i))
        instance.create_user(name = "user" + str(i), password = "theP1eisREAL", database_names = ["database" + str(i)], host="%")
else:
    sys.exit()

print("All done!")
