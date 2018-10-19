1. Start services 
    ```
    sudo docker-compose up
    ```
2. Init wordpress installation by opening http:/localhost:800 and go through few steps.  
   **(This might be optional - need to check without)**

3. Load data from database

      Open container 
      ```
      sudo docker-compose exec db bash
      ```
      Then inside container:
      - Load the data
        ```
        mysql -u root -p wordpress < /repo/my-sql-backup-file
        ```
      -  Replace wordpress settings to run it on localhost:8000 by executing this SQL
        
        ```
        UPDATE dmg_options 
        SET option_value='http://localhost:8000'
        WHERE option_name = 'home' OR option_name = 'siteurl';
        ```
4. Add the data
    - Change the `$table_prefix` setting value in `wp-config.php` value by changing one expression to  `$table_prefix  = 'dmg_';`
    - Add all plugins from to `wp-content/plugins`.  
    Be careful and do not override this plugin dir witch is `wp-content/plugins/factchecked-api-wpplugin`.
    - Add `demagog` theme to  `wp-content/themes`
5. Install plugin `composer` dependencies by following steps from [plugin-only-setup](./plugin-only-setup.md); 
