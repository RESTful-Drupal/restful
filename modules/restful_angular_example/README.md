# AngularJS, RESTful and Entity Validator example

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
3. As logged in user navigate to ``restful-example/form``
