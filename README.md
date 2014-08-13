foundry
=======

Easily perform CRUD tasks on Eloquent models

This is a replacement for Laravel's ResourceControllers, which are very lightweight.

First add "doctrine/dbal" to your composer.json file and run `composer update`

Next create your Eloquent model:

```php
class Product extends Eloquent { }
```

Then create a Controller:

```php
class ProductController extends BaseController { }
```

Finally add a Route resource group. See http://laravel.com/docs/controllers#resource-controllers
All of the "only", "except", etc. options can be used.

```php
Route::resource('product', 'ProductController');
```

**That's all!**

Now you simply need to go to the URL `product` and you will see a paginated list of all Product objects. There is a button to create a new product, and columns with unique indexes link to edit the individual resource.

## Features

* This is meant to be a CMS, not a DB admin tool. Therefore Eloquent `$hidden` and `$guarded` arrays are respected and not editable
* If a column name has a comment, that is shown instead (can be used to create user-friendly labels)
* Supported data types: strings, integers, booleans, dates
* Validation is built in. `NOT NULL` columns are considered "required", any column containing "email" inside its name must be a valid email address, and columns with unique indexes are checked
* Validation errors are displayed next to the problematic column
* Default validation rules can be overridden inside the Controller's constructor

```php
class ProductController extends BaseController {
  public function __construct() {
    parent::__construct();
    $this->rules['name'][] = 'min:8'; // add rule
    $this->rules['sku']    = ['required']; // replace rules
  }
}
```
* Very basic `belongsTo` relationships are supported. The column must end in `_id` and must have the same prefix as the name of the relationship. For example, if the `products` table has a `category_id` column, and this Eloquent relationship then it will work:

```php
class Product extends Eloquent {
  public function category() {
    return $this->belongsTo('Category');
  }
}
``` 

* These `belongsTo` relationships are then shown in the list as the `name` attribute, and when editing, a select dropdown is shown as id -> name pairs.

## Todo

* package this better (as a bundle?)
* filters on the index page
* support more data types
* improve validation for dates
* improve support for relationships
* delete resources
* bulk updates and deletes
