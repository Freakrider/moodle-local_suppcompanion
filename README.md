# Moodle local plugin as companion for the support chat prototype

This local plugin defines some external functions and uses these and some core external functions in a webservice for the Support Chat Prototype `https://github.com/MoodleNRW/multi_agent_rag_system` (DevCamp @MoodleMootDACH 2024).

### Requirements and steps:

* `moodle-webservice_restful` plugin to accept data as json (since I did not try the new Moodle 4.5 webservices).
* Activate webservices
* Activate restful protocol
* Create user for webservices
* Create role for webservice user and assign this role the cap `webservice/rest:use`
* Assign this new role to the webservice user at context level
* Enable the webservice "Support Companion" of this plugin for the created user
* Generate a token for the user
* Use the token in the application to be able to call the externa functions provided by this webservice.

### External function `local_suppcompanion_create_course`
Create a course for a user in a given course category. Required parameters: userid, and course array with fields fullname, shortname, course category id.

##### Example curl post for `local_suppcompanion_create_course`

```
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
-d'{"userid":"4", "course": {"fullname": "test course", "shortname": "test course short", "categoryid": "2"}}' \
"<moodle-instance>/webservice/restful/server.php/local_suppcompanion_create_course"
```

### Core external function `core_user_get_users_by_field`

Make it possible for the application to lookup the userid given the username.

To use this function, add these capabilities to the role webservice user:
* moodle/user:viewdetails
* moodle/user:viewhiddendetails
* moodle/course:useremail
* moodle/user:update

##### Example curl post for `core_user_get_users_by_field`

```
curl -X POST \
-H "Content-Type: application/json" \
-H "Accept: application/json" \
-H 'Authorization: {token}' \
-d'{"field": "username", "values": ["<username>"]}' \
"<moodle-instance>/webservice/restful/server.php/core_user_get_users_by_field"
```