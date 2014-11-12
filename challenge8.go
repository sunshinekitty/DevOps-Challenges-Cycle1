/*
Alex Edwards
Nov, 2014
challenge8.go

This script creates a performance Cloud Server and assigns an A record to it
It will then create a monitoring check and alarm for the public IP
It then returns the IP Address, FQDN, and Monitoring Entity ID
*/

package main

import(
    "github.com/miguel-branco/goconfig"
    "github.com/rackspace/gophercloud"
    "github.com/rackspace/gophercloud/rackspace"
    "github.com/rackspace/gophercloud/rackspace/compute/v2/images"
    "github.com/rackspace/gophercloud/rackspace/compute/v2/flavors"
    "github.com/rackspace/gophercloud/rackspace/compute/v2/servers"
    "fmt"
    "log"
    "strings"
)

func main() {
    // Read in config
    c, conferr := goconfig.ReadConfigFile("./.rackspace_credentials")
    if conferr != nil {
        log.Fatalf("Failed to parse config data: %s", conferr)
    }

    // Define vars
    user, _ := c.GetString("rackspace", "username")
    apikey, _ := c.GetString("rackspace", "apikey")
    user = strings.Trim(user, "\"")
    apikey = strings.Trim(apikey, "\"")
    
    authopts := gophercloud.AuthOptions{
        Username: user,
        APIKey: apikey,
    }

    // Get our main provider
    provider, autherr := rackspace.AuthenticatedClient(authopts)
    if autherr != nil {
        log.Fatalf("Failed to authenticate: %s", autherr)
    }

    // Get server provider
    serviceClient, _ := rackspace.NewComputeV2(provider, gophercloud.EndpointOpts{
        Region: "ORD",
    })
    
    // Get image
    image, _ := images.Get(serviceClient, "ffa476b1-9b14-46bd-99a8-862d1d94eb7a").Extract()
    // Get Ubuntu 12 flavor
    flavor, _ := flavors.Get(serviceClient, "performance1-1").Extract()

    // Create our new server
    server, servercreateerr := servers.Create(serviceClient, servers.CreateOpts{
        Name:      "challenge8",
        ImageRef:  image.ID,
        FlavorRef: flavor.ID,
    }).Extract()

    if servercreateerr != nil {
        log.Fatalf("Failed to create server: %s", servercreateerr)
    }

    // Wait for server creation to finish
    fmt.Println("Server creation process started, waiting for it to complete.")
    serverpenderr := servers.WaitForStatus(serviceClient, server.ID, "ACTIVE", 600)

    if serverpenderr != nil {
        log.Fatalf("Failed to create server: %s", serverpenderr)
    }

}
