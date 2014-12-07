# AngularJS, RESTful and Entity Validator form example

## Test on Simplytest.me (recommended)

1. [Launch sandbox](http://simplytest.me/project/2300389/7.x-1.x-sandbox) on simplytest.me
2. Login (admin/ admin)
3. clear cache via ``/admin/config/development/performance`` (as file upload is disabled by default)
4. Navigate to ``/restful-example/form``

## Test Locally
1. Get a clean Drupal installation with a "standard" profile (i.e. the ``Article``
content type is present)
2. (temporary) Get RESTful and Entity API [patches](https://github.com/Gizra/restful#module-dependencies)
3. Enable module and download Angular related libraries and navigate to ``restful-example/form``

```bash
# Enable uploading files for authenticated users.
drush vset restful_file_upload 1
drush cc menu

# cd into the example module and install node/ bower packages
cd `drush drupal-directory restful_angular_example`
cd components/restful-app
npm install
bower install
```

The RESTful resource is [here](https://github.com/Gizra/restful/blob/7.x-1.x/modules/restful_example/plugins/restful/node/articles/1.5/RestfulExampleArticlesResource__1_5.class.php), and the Entity-Validator handler is [here](https://github.com/Gizra/entity_validator/blob/7.x-1.x/modules/entity_validator_example/plugins/validator/node/article/EntityValidatorExampleArticleValidator.class.php).

Note that if you change the Angular script under ``/components/restful-app/src`` you will need to ``grunt build``. If you don't have grunt installed install it by running ``npm install -g grunt-cli``.
