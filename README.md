# Moodle local plugin as companion for the support chat prototype

## Requirements:

* `moodle-webservice_restful` plugin to accept data as json (since I did not try the new Moodle 4.5 webservices).
* Activate webservices
* Activate restful protocol
* create user for webservices
* create role for webservice user giving them the cap `webservice/rest:use`
* assign this role to the webservice user at context level
* enable the webservice "Support Companion" of this plugin for the created user
* generate a token for the user

### Example curl post

```
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
-d'{"userid":"4", "course": {"fullname": "test course", "shortname": "test course short", "categoryid": "2"}}' \
"<moodle-instance>/webservice/restful/server.php/local_suppcompanion_create_course"
```

## Added core external function `core_user_get_users_by_field`

To use this function, add these capabilities to the role webservice user:
* moodle/user:viewdetails
* moodle/user:viewhiddendetails
* moodle/course:useremail
* moodle/user:update

### Example curl post for `core_user_get_users_by_field`

```
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
-d'{"field": "username", "values": ["<username>"]}' \
"<moodle-instance>/webservice/restful/server.php/core_user_get_users_by_field"
```