# MkyEngine

> *PHP template engine by Micky-N*   
<a href="https://packagist.org/packages/micky/mkyengine"><img src="https://img.shields.io/packagist/v/micky/mkyengine" alt="Latest Stable Version"></a> [![Generic badge](https://img.shields.io/badge/licence-MIT-1abc9c.svg)](https://shields.io/)

The template engine uses the block and extension system to define the different parts of the view.
```php
// index.php
<?php $this->extends('layout') ?>

<?php $this->block('content') ?>
  <p>content</p>
<?php $this->endblock() ?>
```
```php
// layout.php
<h4>Layout</h4>

<?= $this->section('content') ?>
  
<p>Footer</p>
```
```html
// render
<h4>Layout</h4>

<p>content</p>

<p>Footer</p>
```

## Installation

`composer require micky/mkyengine`

## Usage

### Directory Loader

The directory loader register view directory for template engine
```php
$loader = new \MkyEngine\DirectoryLoader(__DIR__ . '/views');
```
If you want to define component or layout sub directory use setComponentDir or setLayoutDir
```php
$loader->setComponentDir('components')->setLayoutDir('layouts');
```

### Environment

The environment stores all directories with the namespace and file extension of the view, you can optionally define shared variables
```php
$context = [] // Shared environmental variables, optional

$environment = new \MkyEngine\Environment($loader, '.php', $context); 
```  
By default, the first namespace is root, you can add another directory loader and its namespace with the method `addLoader()`
```php
$environment->addLoader('ns2', $loader2);

// Check if loader exists
$environment->hasLoader('ns2'); // true
```
To use view, component or layout from another namespace use `@namespace:view`
```php
<?= $this->component('form') ?> // From root namepsace
<?= $this->component('@ns2:form') ?> // From ns2 namespace
```
### View Compiler

The view compiler compiles the view by retrieving the blocks and displaying them in the layout sections. The first parameter is the environment, the second is the view to be displayed and the third is the view variables.
```php
$view = new \MkyEngine\ViewCompile($environment, 'index', [
   'name' => 'Micky'
]);
```
To render the view use `render()` method
```php
echo $view->render();
```

## Templating

### Layout

The layout is a background for the views, you can define which parts of the layout will be filled by the view.
```php
// layout.php
<h4>Layout</h4>
<?= $this->section('content') ?>
<p>Footer</p>
```
You can define a default value in the section that will be used if no 'title' block is defined.
```php
// layout.php
<?= $this->section('title', 'default') ?>
```
Layout view can extends another layout.
```php
// layout.php
<?php $this->extends('great-layout') ?>

<?php $this->block('title', 'new Title') ?>

<?php $this->block('content2') ?>

   <h4>Layout</h4>
   <?= $this->section('content') ?>

<?php $this->endblock() ?>
```
```php
// layout.php
<?= $this->section('title', 'title') ?>  
  
<h1>Layout2</h1>  
  
<?= $this->section('content2') ?>
```

### Block

#### Extends

Extends method is used to define the layout file.
```php
<?php $this->extends('layout') ?>
```

Blocks are a part of view use for layout. The param is the block name
```php
<?php $this->extends('layout') ?>

<?php $this->block('content') ?>
  <p>content</p>
<?php $this->endblock() ?>
```

You can set a simple block with a second parameter
```php
<?php $this->extends('layout') ?>

<?php $this->block('title', 'MkyFramework') ?>
```

To display this block use section() method in the layout with the block name to display
```php
// layout.php
<h4>Layout</h4>
<?= $this->section('content') ?>
<p>Footer</p>
```
You can define several blocks with the same name
```php
<?php $this->block('content') ?>
  <p>content</p>
<?php $this->endblock() ?>
<?php $this->block('content') ?>
  <p>second part</p>
<?php $this->endblock() ?>
```
It will show
```html
<p>content</p>
<p>second part</p>
```
Thanks to that, blocks can be conditioned by the method `if()`
```php
<?php $this->block('content')->if($condition) ?>
...
<?php $this->endblock() ?>
```
### Component

The component is a view piece, useful if you want to use it in several views.
```php
<?= $this->component('form') ?>
```
You can pass a variable to the component with the method `bind()`, the first parameter is the component variable and the second is the value.
```php
<?= $this->component('form')->bind('name', 'Micky') ?>
```

Same as block class, components can be conditioned by the method `if()`
```php
<?= $this->component('form')->if($condition) ?>
```
You can repeat a component in loop with 2 methods:

- for
- each

#### For loop

The first parameter is the number of iterations, the second is a callback that will be called at each iteration. In callback the first parameter is the view params, the second is the current loop index.

*Example:  in username-input.php component there a variable called 'name' for an input, with the callback each iterated component will have the name of the current user*
```php
// components/name-input.php
<input value="<?= $name ?>"/>
```
```php
<?= $this->component('name-input')->for(3, function(array $params, int $index) use ($users){  
     $params['name'] = $users[$index]->name;  
     return $params;  
}) ?>
```
#### Each

With the method `each()` , the component will iterate for each array value. The first parameter is the array and the second can be a callback, an array or a string.

##### Callback
The `each()` callback is the same as the `for()` callback but with a third parameter that it's the array.
```php
<?= $this->component('name-input')->each($users, function(array $params, int $index, array $users){
    $params['name'] = $users[$index]->name;  
    return $params;  
}) ?>
```
##### Array
You can bind variable with an array that index is the component variable and the value is the object property or array key of data `$users`
```php
<?= $this->component('name-input')->each($users, ['name' => 'name']) ?>
```
If you need to pass a nested value, you can do so by concatenating `name.firstname` it's equal to:

- `$user->name->firstname`
- `$user->name['firstname']`
- `$user['name']->firstname`
- `$user['name']['firtstname']`

##### String
The component may need the object or array as parameter (like one user of users), for that you can set in the parameter the name of current iterated data
```php
// components/name-input2.php
<input value="<?= $user->name ?>"/>

// view
<?= $this->component('name-input2')->each($users, 'user') ?>
```

##### Else component
If the data `$users` is empty you can set a third parameter as string to define the else component

```php
<?= $this->component('name-input2')->each($users, 'user', 'empty-user') ?>
```

## Licence

MIT