<?php

/*
 * Alex Edwards (sunshinekitty)
 * Nov, 2014
 * challenge4.php
 *
 * This script creates a Cloud Files Container, uploads a dir, then 
 * changes it to a CDN and returns the CDN URL
*/

// Require composer's copy of php-opencloud
require '../vendor/autoload.php';

use OpenCloud\Rackspace;

// Load in creds
$creds = parse_ini_file( './.rackspace_credentials' );

// Create Rackspace client object
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
  'username' => $creds['username'],
  'apiKey'   => $creds['apikey']
));

// Create Object Store service from Rackspace client
$objectStoreService = $client->objectStoreService(null, 'ORD');

// Get name for container
while (true)
{
  echo "Enter a name for the container to create: ";
  $containerName = trim(fgets(STDIN));
  if ( strlen($containerName) > 1 )
  {
    break;
  }
  else
  {
    echo "\n";
  }
}

$container = $objectStoreService->createContainer($containerName);

if ( $container == false ) {
  echo "That container already exists.";
  exit; 
  // Could potentially loop through until container doesn't exit,
  // challenge calls for exiting though.
}

// Get folder to upload
while (true)
{
  echo "Provide path for folder to upload: ";
  $folder = trim(fgets(STDIN));
  if ( is_dir($folder) )
  {
    echo "Uploading folder.\n";
    break;
  }
  else
  {
    echo "That's not a valid directory, try again.\n";
  }
}

// Upload Dir
$container->uploadDirectory($folder);

// Enable CDN
$container->enableCdn();

// Get CDN URL
$cdnURL = $container->getCdn()->getCdnSslUri();

echo "CDN URL: " . $cdnURL;

?>
