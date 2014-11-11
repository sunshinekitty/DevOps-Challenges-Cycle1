#
# Alex Edwards
# Nov, 2014
# challenge7.rb
#
# This script creates 2 servers, then creates a load balancer
# and adds the 2 servers via private IP to the load balancer.
#

require 'fog'
require 'parseconfig'

# Get our creds
config = ParseConfig.new('./.rackspace_credentials')
username = config['rackspace']['username'].tr '"', '|'
apikey = config['rackspace']['apikey'].tr '"', '|'

# Create compute object
client = Fog::Compute.new(
  :provider => 'rackspace',
  :rackspace_username => username,
  :rackspace_api_key => apikey,
  :rackspace_region => 'ORD'
)

# Create the servers
server1 = client.servers.create(
  :name       => 'server1',
  :flavor_id  => '2',
  :image_id   => 'ffa476b1-9b14-46bd-99a8-862d1d94eb7a'
)
server2 = client.servers.create(
  :name       => 'server2',
  :flavor_id  => '2',
  :image_id   => 'ffa476b1-9b14-46bd-99a8-862d1d94eb7a'
)

puts "Creating Servers"

server1.wait_for { ready? }
server2.wait_for { ready? }

# Create load balancer object
lbs = Fog::Rackspace::LoadBalancers.new(
  :rackspace_username  => username, 
  :rackspace_api_key   => apikey,
  :rackspace_region    => :ord
)

# Create the LB
balancer = lbs.load_balancers.create(
  :name => "loadbalancer", 
  :protocol => "HTTP", 
  :port => 8080,  
  :virtual_ips => [{:type => 'PUBLIC'}], 
  :nodes => []
)

puts "Creating Load Balancer"

balancer.wait_for { ready? }

puts "Adding nodes to load balancer"

balancer.nodes.create(
  :address => server1.private_ip_address, 
  :port => 8080, 
  :condition => 'ENABLED'
)

# This will fail without waiting for ready in between adding nodes
balancer.wait_for { ready? }

balancer.nodes.create(
  :address => server2.private_ip_address, 
  :port => 8080, 
  :condition => 'ENABLED'
)

puts "Done!"
