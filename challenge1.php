<?php

/*
 * Alex Edwards (sunshinekitty)
 * Nov, 2014
 * challenge1.php
 *
 * This script creates a 512MB Cloud Server and then returns the root password and IP address.
*/

// Require composer's copy of php-opencloud
require '../vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\ServerState;
use Guzzle\Http\Exception\BadResponseException;

// Load in creds
$creds = parse_ini_file('./.rackspace_credentials');

// Create Rackspace client object
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
  'username' => $creds['username'],
  'apiKey'   => $creds['apikey']
));

// Create compute service from Rackspace client in ORD
$service = $client->computeService(null, 'ORD');

// Service for actually making the server
$server = $service->server();

// Select OS
$images = $service->imageList();
foreach ($images as $image) 
{
  if (strpos($image->name, 'Ubuntu') !== false) 
  {
    $serverImage = $image;
    break;
  }
}

// Get 512M
$flavors = $service->flavorList();
foreach ($flavors as $flavor) 
{
    if (strpos($flavor->name, '512M') !== false) 
    {
      $serverFlavor = $flavor;
      break;
    }
}

// Obtain server name
while (true) 
{ // Infinite loop until we get proper input
  echo "Enter a name for the server: ";
  $serverName = trim(fgets(STDIN));
  if ( strlen($serverName) > 1 ) 
  {
    break;
  }
  else
  {
    echo "\n";
  }
}
echo "Thanks! Creation process started.\n";

// Create server
try 
{
  $response = $server->create(array(
    'name'    =>  $serverName,
    'image'   =>  $serverImage,
    'flavor'  =>  $serverFlavor
  ));
} 
catch (BadRequestException $f) 
{ // If creation fails
  $response = $f->getResponse();
  echo sprintf(
    'Status: %s\nBody: %s\nHeaders: %s',
    $response->getStatusCode(),
    $response->getBody(true),
    implode(', ', $response->getHeaderLines())
  );
}

// Wait for server creation to finish
$server->waitFor(ServerState::ACTIVE);

// Print out root password and IP address
echo "Root password: " . $server->adminPass;
echo "\n";
echo "IP Address: " . $server->accessIPv4;

?>
