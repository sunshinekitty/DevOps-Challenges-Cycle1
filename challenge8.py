#!/usr/bin/env python
# -*- coding: utf-8 -*-
#
# Alex Edwards
# Nov, 2014
# challenge6.py
#
# This script creates a Cloud Performance 1G Server, and adds an A record for it.
# It will then create a cloud monitoring check and alarm based on ping of the public IP
# Finally it returns the public IP, FQDN, and Monitoring Check ID
#

from __future__ import print_function
import pyrax
import six
import sys
try:
    import configparser
except ImportError:
    import ConfigParser as configparser

# Setup our credentials
config_file="./.rackspace_credentials"
config = configparser.ConfigParser()
config.read(config_file)
username = config.get('rackspace', 'username').replace('"', '')
apikey = config.get('rackspace', 'apikey').replace('"', '')

pyrax.set_setting("identity_type", "rackspace")
pyrax.set_credentials(username, apikey)

# Create objects for managing servers, dns, monitoring
cs = pyrax.cloudservers
dns = pyrax.cloud_dns
cm = pyrax.cloud_monitoring

# Get flavor and image
flavor = [flavor for flavor in cs.flavors.list() if flavor.ram == 1024][0]
image = [img for img in cs.images.list() if "Ubuntu 12" in img.name][0]

# Create our server
server = cs.servers.create("challenge8", image.id, flavor.id)
print("Server creation process started")

# Wait for server
server = pyrax.utils.wait_until(server, "status", ["ACTIVE", "ERROR"])
print("Done!")

# Get domain to add A record for
dom = pyrax.cloud_dns.find(name='drunken-donuts.com')

# Add in two records, for www.@ and @
recs = [{
        "type": "A",
        "name": "drunken-donuts.com",
        "data": server.accessIPv4,
        "ttl": 3600,
        }, {
        "type": "A",
        "name": "www.drunken-donuts.com",
        "data": server.accessIPv4,
        "ttl": 3600,
        }]
records = dom.add_records(recs)

# Create entity for check
ent = cm.create_entity(name="entity_for_server", ip_addresses={"example": server.accessIPv4},
        metadata={"description": "I am Totoro"})

# Create our check
chk = cm.create_check(ent, label="ping_check", check_type="remote.ping",
        details={"url": server.accessIPv4}, period=900,
        timeout=20, monitoring_zones_poll=["mzdfw", "mzlon", "mzsyd"],
        target_hostname=server.accessIPv4)

# Create our notification plan should check fail
# I don't want to be emailed...no alert
np = cm.create_notification_plan(label="default")

# Create our alarm definitions to determine if check fails
alarm = cm.create_alarm(ent, chk, np, "if (rate(metric['average']) > 10) { return new AlarmStatus(WARNING); } return new AlarmStatus(OK);")

# Print out the details
print("IP Address: " + server.accessIPv4)
print("FQDN: " + records[1].name)
print("Check ID: " + chk.id)
