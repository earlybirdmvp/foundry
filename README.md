foundry
=======

Easily perform CRUD tasks on Eloquent models

This is a replacement for Laravel's ResourceControllers, which are very lightweight.

First create your Eloquent model:

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

Now you simply need to go to the URL `product` and you will see a list of all Product objects. There is a button to create a new product, and columns with unique indexes link to edit the individual resource.

## Features

* Eloquent `$hidden` and `$guarded` arrays are respected
* Validation is built in. `NOT NULL` columns are considered "required", any column containing "email" inside its name must be a valid email address, and columns with unique indexes are checked. These default validation rules can be overridden inside the Controller's constructor

```php
class ProductController extends BaseController {
  public function __construct() {
    parent::__construct();
    $this->rules['name'][] = 'min:8'; // add rule
    $this->rules['sku']    = 'required'; // replace rules
  }
}
```

## Todo

* package this better (as a bundle?)
* pagination on the index page
* filters on the index page
* supporting more data types
* supporting relationships (e.g. select drop down for "belongsTo")
* display error messages
* deleting resources
