````
# Add job content type
# Install vagrant: https://www.vagrantup.com/downloads.html
# Install Virtualbox: https://www.virtualbox.org/wiki/Downloads
git clone https://github.com/geerlingguy/drupal-vm.git ~/Projects/drupal-vm-2
git clone https://github.com/danielnitschepeak/drupal-elasticsearch-demo.git
cd drupal-vm-2
cp ../drupal-elasticsearch-demo/config.yml .
diff config.yml default.config.yml
vagrant up
echo "192.168.88.88   drupalvm.dev drupalvm3.dev" | sudo tee -a /etc/hosts
open http://drupalvm3.dev/user
# Copy scraped data to root directory
vagrant ssh
cd /var/www/drupalvm/drupal
drupal init -n
cd web

# Import jobs
drupal generate:module --module=ImportJobs --description='Parse and import jobs into Drupal'
drupal module:install importjobs
drupal generate:command --module=importjobs --name=importjobs --class=ImportJobsCommand --container-aware
cd ../; composer require "thiagoalessio/tesseract_ocr"; sudo apt-get install tesseract-ocr
cp ../drupal-elasticsearch-demo/ImportJobsCommand.php drupal/web/modules/custom/importjobs/src/Command/
drupal importjobs ../../tj/jobs

# Index jobs
# elasticsearch demo with insomnia + explanation
# Run Drupal cron for drupal search
cd ..; composer require elasticsearch/elasticsearch; cd web
drupal generate:module --module=IndexJobs --description='Add jobs to elasticsearch'
drupal module:install indexjobs
drupal generate:command --module=indexjobs --name=indexjobs --class=IndexJobsCommand --container-aware
cp ../drupal-elasticsearch-demo/IndexJobsCommand.php drupal/web/modules/custom/indexjobs/src/Command/
drupal indexjobs

# Search jobs
drupal generate:module --module=SearchJobs --description='Search elasticsearch for jobs'
drupal generate:controller --module=searchjobs --class=SearchController
cp -R ../drupal-elasticsearch-demo/searchjobs/* drupal/web/modules/custom/searchjobs/
drupal module:install searchjobs
drupal cache:rebuild all

open http://drupalvm3.dev/searchjobs?query=css
open http://drupalvm3.dev/search/node?keys=css
open http://drupalvm3.dev/searchjobs?query=drupal
open http://drupalvm3.dev/search/node?keys=drupal
open http://drupalvm3.dev/searchjobs?query=peak
open http://drupalvm3.dev/search/node?keys=peak
open http://drupalvm3.dev/searchjobs?query=30%20years
open http://drupalvm3.dev/search/node?keys=30%20years
open http://drupalvm3.dev/searchjobs?query=communicate
open http://drupalvm3.dev/search/node?keys=communicate

# back to insomnia for aggregations
open http://www.peregrineadventures.com/en/search
open http://www.peregrineadventures.com/en/sri-lanka/classic-sri-lanka-93222
open http://www.geckosadventures.com/en/search
open http://www.adventuretours.com.au/en/search
open http://www.adventuretours.com.au/en/search
open https://www.wikipedia.org/

# questions?
````