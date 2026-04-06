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

## Setting up the Gratitude Journal API

### Redis Configuration
* Install redis: `sudo apt install redis-server`
* Start redis: `sudo systemctl enable --now redis-server`
* Test that it is running: `redis-cli ping`
* Make changes to config:
    ```
    redis-cli
    CONFIG SET supervised systemd  # This might not work. Can be edited directly in `/etc/redis/redis.conf`
    CONFIG SET requirepass "insert-password-here"
    CONFIG REWRITE
    ```

### Environment Variables
* `export JWT_SECRET_KEY="$(openssl rand --base64 32)"`
* `export REDIS_URL="redis://default:<insert-password-here>@localhost:6379"`

### Python Setup
* Install packages
    ```
    cd gratitude_journal_api
    uv init
    uv add -r pyproject.toml
    source .venv/bin/activate
    ```
* Clone the journal analysis repo and create the data file
    ```
    cd /var/www
    git clone <repo-ssh-path>
    cd gratitude_journal_analysis
    python src/create_df.py
    ls -l data | grep journal_df.pkl
    ```
* Update the global values in `/var/www/gratitude_journal_analysis/src/print_journal.py` to use absolute paths
    ```python
    # Global variables
    base_filepath = '/var/www/gratitude_journal_analysis/data'
    pickle_filepath = f'{base_filepath}/journal_df.pkl'
    random_pickle_filepath = f'{base_filepath}/random_journal_df.pkl'
    random_journal_timer_filepath = f'{base_filepath}/random_journal_time_of_last_use.txt'
    random_journal_timer_minutes = 10
    ```
* Create the security token (TODO: This should eventually be replaced with a DB users table)
    ```
    cd /var/www/html/gratitude_journal_api
    python3 create_hash.py <insert-secret-here>
    ```
* Start the API server
    ```
    uvicorn main:app --reload --host 127.0.0.1 --port 8000
    ```
* Test out the API (optional)
    ```
    curl -s -X POST http://127.0.0.1:8000/api/auth/login \
        -H "Content-Type: application/json" \
        -d '{"username":"wreames","password":"<insert-secret-here>"}'
    ```
* At this point, you should be able to go to `http://127.0.0.1/gratitude-journal` and find that it is working :)