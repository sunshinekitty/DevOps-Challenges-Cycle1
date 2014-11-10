<?php

/*
 * Alex Edwards (sunshinekitty)
 * Nov, 2014
 * challenge3.php
 *
 * This script lists all of the domains on an account, then prompts
 * the user to create a new A record after a domain is selected.
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

// Create DNS service from Rackspace client
$dnsService = $client->dnsService();

// Get domain list
$domains = $dnsService->domainList();

// List domains
foreach ($domains as $index => $domain) {
  echo '[' . $index . '] -> ' . $domain->name . "\n";
}

// Select domain to change record for
while (true) {
  echo "Select the domain you would like to add an A record for: ";
  $domain = trim(fgets(STDIN));
  if ( $domain >= 0 && $domain <= count($domains) )
  {
    $domain = $dnsService->domain($domains[$domain]->id);
    break;
  }
  else
  {
    echo "Incorrect entry, try again.\n";
  }
}

// Get record text
echo "Enter the record text. \"www\" will create \"www." . $domain->name . "\": ";
$recordText = trim(fgets(STDIN));
$recordText = (strlen($recordText) == 0) ? $domain->name : $recordText . '.' . $domain->name;
echo $recordText . " selected.\n";

// Get IP address
while (true) {
  echo "Enter the IP to point to: ";
  $recordIP = trim(fgets(STDIN));
  if ( filter_var($recordIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) )
  { 
    break;
  }
  else
  {
    echo "Invalid IP, try again.\n";
  }
}

// Get TTL
while (true) {
  echo "Enter TTL for record (300-86400): ";
  $recordTTL = trim(fgets(STDIN));
  if ( $recordTTL >= 300 && $recordTTL <= 86400 )
  {
    break;
  }
  else
  {
    echo "Invalid TTL, try again.\n";
  }
}

// Confirmation
echo "Creating A record " . $recordText . " -> " . $recordIP . " with TTL of " . $recordTTL . " seconds.\nContinue? [y/n]: ";
strtoupper(trim(fgets(STDIN))) == "N" ? exit : false;

// Create
$record = $domain->record(array(
  'type' => 'A',
  'name' => $recordText,
  'data' => $recordIP,
  'ttl' => $recordTTL
));
$record->create();

echo "Added";

?>
