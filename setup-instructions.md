# Steps Used to Create and Configure an Apache Server on Linux

## Initial Setup
* Setup firewall to allow TCP IN on 443 and 80
* Install git
    * Setup an ssh key
* Install apache2
* Start apache2
* Confirm that the default webpage is running
    * Connect on localhost
    * Connect using a separate device
* Create a new user group that includes `root` and the base user.
    ```bash
    sudo groupadd admin
    sudo usermod -aG admin wreames
    sudo usermod -aG admin root
    ```
* Update `/var/www/` to use this new user group
    ```bash
    sudo chgrp -R admin /var/www
    sudo chmod -R g+rwx /var/www
    ```
* Login to the account again to refresh access permissions
    ```bash
    su -l $USER
    ```
* Clone my website repo into `/var/www`, then rename as `/var/www/html`\
    ```bash
    git clone git@github.com:wdreames/website.git
    rm -rf html/
    mv website/ html/
    ```
* Load the site in a browser to make sure it was updated

## Updating apache2 config files
* Allowing for URLs without `.html`
    * Run this command: `sudo a2enmod rewrite`
    * Add the following to `/var/www/html/.htaccess`:
        ```
        RewriteEngine On

        # 1. Remove .html from the address bar (Redirect)
        # This turns ://example.com into ://example.com
        RewriteCond %{THE_REQUEST} \.html\s
        RewriteRule ^(.+)\.html$ /$1 [R=301,L]

        # 2. Map extensionless requests back to .html files (Internal Rewrite)
        # If the file exists with .html, serve it without changing the URL
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME}\.html -f
        RewriteRule ^(.*)$ $1.html [L]
        ```
    * Add the following to `/etc/apache2/sites-available/000-default.conf`
        ```
        <Directory /var/www/html>
            AllowOverride All
        </Directory>
        ```
    * Run this command: `systemctl restart apache2`



## Setting up the Gratitude Journal backend

* Install php (specific command may be different)
    ```
    sudo apt install php-8.3
    ```
* Clone the gratitude journal analysis repo into `/var/www`
    ```
    git clone git@github.com:wdreames/gratitude_journal_analysis.git
    ```
* Somehow fix the issue with Ds/Store not working... idk :/