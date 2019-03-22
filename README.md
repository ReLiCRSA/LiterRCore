# Lite-R Core

## Installation
1. Checkout the repo
2. Copy **config.ini.sample** to **config.ini**
3. Create database
4. Edit **config.ini** with your details
5. Make sure directory **resources/compiled** is writable by **web server** / **fpm** user
6. Make sure directory **logs** is writable by **web server** / **fpm** user
7. Go to db directory and run **migrate.php** to create tables and admin user

## Goal
Lite-R aims to be a simple and small PHP framework that is easy to extend. It is based on pure PHP and is completely stand alone.
Composer can be brought in to help extend it but it is currently not used.

## Validation
### Example
```
$postData = $this->request->getOnly(['email','password']);
$validator = new Validation();
$validator->validate([
    'email' => 'required|email|unique:users,email'
], $postData);

if (!$validator->passed()) {
    $this->view->setFlashMessage('error', $validator->getErrors());
    $this->view->redirect("/");
    return;
}
```
### Available Rules
```
'required' => Is a required field
'email' => Is a valid eMail address
'string' => Is a valid string with spaces included
'numeric' => Is a numeric value
'alphanum' => Is alpha numeric including spaces
'unique' => is unique in a table (unique:tablename,fieldname)
'equals' => One value equals another value passed
'min' => Minimum length (min:10 for a minimum length of 10)
'exists' => Exists in a table (exists:tablename,fieldname)
```

## TODO - Document how models work