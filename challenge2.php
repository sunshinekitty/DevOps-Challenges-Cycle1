<?php

/*
 * Alex Edwards (sunshinekitty)
 * Nov, 2014
 * challenge2.php
 *
 * This script builds 1-3 512M Cloud Servers and injects an SSH pub key for login.
 * It then returns the passwords and IP addresses for the servers.
*/

// Require composer's copy of php-opencloud
require '../vendor/autoload.php';

use OpenCloud\Rackspace;
use OpenCloud\Compute\Constants\ServerState;
use Guzzle\Http\Exception\BadResponseException;

// Load in creds
$creds = parse_ini_file( './.rackspace_credentials' );

// Create Rackspace client object
$client = new Rackspace(Rackspace::US_IDENTITY_ENDPOINT, array(
  'username' => $creds['username'],
  'apiKey'   => $creds['apikey']
));

// Create compute service from Rackspace client in ORD
$service = $client->computeService(null, 'ORD');

// Select OS
$images = $service->imageList();
foreach ($images as $image) 
{
  if ( strpos($image->name, 'Ubuntu') !== false ) 
  {
    $serverImage = $image;
    break;
  }
}

// Get 512M
$flavors = $service->flavorList();
foreach ($flavors as $flavor) 
{
    if ( strpos($flavor->name, '512M') !== false ) 
    {
      $serverFlavor = $flavor;
      break;
    }
}

// Obtain server count
while (true) 
{ // Infinite loop until we get proper input
  echo "Enter the number of servers to make (1-3): ";
  $serverCount = trim(fgets(STDIN));
  if ( $serverCount >= 1 && $serverCount <= 3 ) 
  {
    break;
  }
  else
  {
    echo "Sorry, select 1-3\n";
  }
}

// Obtain server name
while (true) 
{ // Infinite loop until we get proper input
  echo "Enter a name for the server" . (($serverCount > 1) ? "s: " : ": "); // ternary for plural
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

// First define our home dir (this works accross operating systems, Windows does not define $_SERVER['HOME'])
$homeDir = (isset($_SERVER['HOME']) ? $_SERVER['HOME'] : $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH']) . DIRECTORY_SEPARATOR;

// Obtain path for SSH key
while (true)
{ // Infinite loop until proper input
  echo "Enter path for SSH key to install: (" . $homeDir . ".ssh" . DIRECTORY_SEPARATOR . "id_rsa.pub): ";
  $keyPath = trim(fgets(STDIN));
  if ( strlen($keyPath) == 0 )
  {
    $keyPath =  $homeDir . ".ssh" . DIRECTORY_SEPARATOR . "id_rsa.pub";
  }
  if ( file_exists($keyPath) )
  {
    echo "Selected: $keyPath\n";
    break;
  }
  else
  {
    echo "File " . $keyPath . " does not exist.\nPlease try again.\n";
  }
}

// Instantiate array to hold server objects
$servers = array();

// Create server(s)
for ( $i = 1; $i <= $serverCount; $i++ )
{
  // Service for actually making the server
  $servers[$i] = $service->server();
  $servers[$i]->addFile('/root/.ssh/authorized_keys', file_get_contents($keyPath));
  try 
  {
    $response = $servers[$i]->create(array(
      'name'    =>  $serverName . $i,
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
}

// Wait for server creation to finish
echo "Creation process started for the " . $serverCount . " server" . (($serverCount > 1) ? "s" : "") . ", waiting.\n";
foreach ( $servers as $server )
{
  $server->waitFor(ServerState::ACTIVE);
  echo $server->name . " is ready.\n";
}

// Print out IP and Root Password
foreach ( $servers as $server )
{
  echo 
    "
    =======
    Server: " . $server->name . "\n
    Root password: " . $server->adminPass . " \n 
    IP Address: " . $server->accessIPv4 . "
    =======";
}
echo "\n"; // formatting

?>
