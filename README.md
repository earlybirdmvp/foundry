foundry
=======

Easily perform CRUD tasks on Eloquent models

This is a replacement for Laravel's Resource Controllers, which are very lightweight.

## Installation 

Add "earlybirdmvp/foundry" to your composer.json file and run `composer update`.

Then add these lines to your `app/start/global.php` file:

```php
View::addLocation(base_path().'/vendor/earlybirdmvp/foundry/views');
View::addNamespace('foundry', base_path().'/vendor/earlybirdmvp/foundry/views');
```

Finally, you can optionally copy and modify the SASS file.

## Getting Started

First create your Foundry model. (Foundry extends Eloquent) 

```php
class Product extends Earlybird\Foundry { }
```

Then create a Controller:

```php
class ProductController extends Earlybird\FoundryController { }
```

Finally add a Route resource group. See http://laravel.com/docs/controllers#resource-controllers
All of the "only", "except", etc. options can be used.

```php
Route::resource('product', 'ProductController');
```

**That's all!**

Now you simply need to go to the URL `product` and you will see a paginated list of all Product objects. There is a button to create a new product, and columns with unique indexes link to edit the individual resource.

## Features

* This is meant to be a CMS, not a DB admin tool. Therefore the Eloquent `$hidden` and `$guarded` arrays are respected and not visible or editable, respectively.
* If a column name has a comment, that is shown instead (can be used to create user-friendly labels)
* Supported data types:

```
bigint, boolean, date, decimal, integer, string, text
```

* Validation is built in. `NOT NULL` columns are considered "required", any column containing "email" inside its name must be a valid email address, and columns with unique indexes are checked
* Validation errors are displayed next to the problematic input

<!--
* Default validation rules can be overridden inside the Model

```php
class Product extends Earlybird\Foundry {
  protected $rules = array(
    'name' => 'min:8'
  );
}
```
-->

* Very basic `belongsTo` relationships are supported. The column must end in `_id` and must have the same prefix as the name of the relationship. For example, if the `products` table has a `category_id` column, and this Eloquent relationship then it will work:

```php
class Product extends Earlybird\Foundry {
  public function category() {
    return $this->belongsTo('Category');
  }
}
``` 

* These `belongsTo` relationships are shown as select dropdowns where the value is the `id` and the option text is the `name` attribute. If the table does not have a `name` column, or you wish to change what is displayed, you can use `$appends`:

```php
class Product extends Earlybird\Foundry {
  protected $appends = array(
    'foundry_value',
  );
  public function getFoundryValueAttribute() {
    return $this->sku . ':  ' . $this->name;
  }
}
```

## Options

### Controller

* Specify the model with `protected $model`. Default is class name with "Controller" stripped off.
* Set the number of items shown per page with `protected $per_page`. Default 10.

## Todo

* sort and filter the index page
* support more data types
* improve validation for dates
* improve support for relationships
* delete resources
* bulk updates and deletes
* more flexible layout
