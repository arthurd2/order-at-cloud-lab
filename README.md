# Order@Cloud - Lab Tests

O@C Labs is where we share simulatios and comparison to test the Order@Cloud solution.

## Instalation
* First it's necessary to install some basic packages. (Last tested on Ubuntu LTS 18.04) 
```bash
sudo apt install php7.2-cli php7.2-json phpunit php-memcached
```
* Clone the Repo
```bash
git clone https://github.com/arthurd2/order-at-cloud-lab.git
```
* Execute the Tests
```bash
cd order-at-cloud-lab
phpunit tests/file.php
```
* Go take a coffe and come back in a while...

Obs1: Due academic reasons, our graphs are generated in Latex Code.
Obs2: In some cases the  '''markTestIncomplete''' function is used to skip some tests.
 
