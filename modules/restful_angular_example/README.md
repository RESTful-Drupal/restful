# AngularJS, RESTful and Entity Validator form example

1. Get a clean Drupal installation with a "standard" profile (i.e. the ``Article``
content type is present).
2. Enable module and download Angular related libraries:

```bash
# Enable uploading files for authenticated users.
drush vset restful_file_upload 1

# CD into the example module
cd `drush drupal-directory restful_angular_example`
cd components/restful-app
npm install
bower install
```

Now as logged in user navigate to ``restful-example/form``.

The RESTful resource is [here](https://github.com/Gizra/restful/blob/7.x-1.x/modules/restful_example/plugins/restful/node/articles/1.5/RestfulExampleArticlesResource__1_5.class.php), and the Entity-Validator handler is [here](https://github.com/Gizra/entity_validator/blob/7.x-1.x/modules/entity_validator_example/plugins/validator/node/article/EntityValidatorExampleArticleValidator.class.php).
