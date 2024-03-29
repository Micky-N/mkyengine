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
$loader = new \MkyEngine\DirectoryLoader('views_dir');
```
If you want to define component or layout subdirectory use setComponentDir or setLayoutDir
```php
$loader->setComponentDir('components_dir')->setLayoutDir('layouts_dir');
```
component directory will be `views_dir/components_dir` and layout directory will be `views_dir/layouts_dir`

### Environment

The environment stores all directories with the namespace and file extension of the view, you can optionally define shared variables
```php
$context = [] // Shared environment variables, optional

$environment = new \MkyEngine\Environment($loader, $context); 
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
Layout view can extend another layout.
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

### Injection

Thanks to injection method `$this->inject` you can set object used for HTML template like number formatter or form builder as View property

Example FormBuilder:
```php
class FormBuilder
{
    public function open(array $attr): string
    {
        return "<form method='{$attr['method']}' action='{$attr['action']}'>";
    }

    public function input(string $type, string $name, string $value = ''): string
    {
        return "<input type='$type' name='$name' value='$value'>";
    }

    public function submit(string $message, string $name = ''): string
    {
        return "<button type='submit'" . ($name ? " name='$name'" : '') . ">$message</button>";
    }

    public function close(): string
    {
        return "</form>";
    }
}
```
In the view:

```php
// view.php
<?php $this->inject('form', FormBuilder::class) ?>
```
The first parameter is the name of property you want to register in the view instance and the second is the class to instantiate, you can pass a class instance or a class name.
You will be able to use class via `$this->nameOfProperty`

```php
<?php $this->inject('form', FormBuilder::class) ?>
<?= $this->form->open(['method' => 'POST', 'action' => '/create']) ?>
    <?= $this->form->input('text', 'firstname', 'Micky') ?>
    <?= $this->form->input('text', 'lastname', 'Ndinga') ?>
    <?= $this->form->submit('Save') ?>
<?= $this->form->close() ?>
```
The HTML rendering will be:
```html
<!--Rendering-->
<form method="POST" action="/create">
    <input type="text" name="firstname" value="Micky">
    <input type="text" name="lastname" value="Ndinga">
    <button type="submit">Save</button>
</form>
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
You can pass multiple variables to the component with the method `binds()`.
```php
<?= $this->component('form')->multipleBind(['name' => 'Micky', 'lastname' => 'Ndinga']) ?>
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

*Example: in name-input.php component there a variable called 'name' for an input, with the callback each iterated component will have the name of the current user*
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
<?= $this->component('name-input')->each($users, ['name']) ?> // 'name' => user->name
<?= $this->component('name-input')->each($users, ['name' => 'firstname']) ?> // 'name' => user->firstname
```
If you need to pass a nested value, you can do so by concatenating `address.postcode` it's equal to:

- `$user->{address | getAddress() | magic getter for "address"}->{postcode | getPostcode() | magic getter for "postcode"}`  

Example:  
- `$user->address->postcode`
- `$user->getAddress()->postcode`
- `$user->address['postcode']`
- `$user->getAddress()['postcode']`
- `$user['address']->postcode`
- `$user['address']['postcode']`

All properties and nested properties are accessible if the property exists or a getter, like for `postcode`, `getPostcode()` exists or a magic method exists 

##### String
The component may need the object or array as parameter (like one user of users), for that you can set in the parameter the name of current iterated data
```php
// components/user-input.php
<input value="<?= $user->name ?>"/>

// view
<?= $this->component('user-input2')->each($users, 'user') ?>
```

##### Else component
If the data `$users` is empty you can set a third parameter as string to define the else component

```php
<?= $this->component('user-input')->each($users, 'user', 'empty-user') ?>
```

##### Component slot
In the case you have a component which you want to make the body dynamic like:

```php
// components/alert.php
<div class="alert alert-<?= $type ?>">
    <div>
        <p><?= ucfirst($type) ?>:</p>
        <?= $this->slot('default') ?>
    </div>
    <?php if ($this->hasSlot('confirm')): ?>
        <button id="confirm"><?= $this->slot('confirm') ?></button>
    <?php endif ?>
    <?php if ($this->hasSlot('close')): ?>
        <button id="close"><?= $this->slot('close') ?></button>
    <?php endif ?>
</div>
```
A simple alert component with a conditional button, to use this in your view, you have to set three slots: the `default`, `confirm` and `close` slot

```php
// view
<?php $this->component('alert')->bind('type', 'danger') ?>
    this is an alert
    <?php $this->addslot('confirm', 'confirm the alert') ?>
    // Or
    <?php $this->addslot('confirm') ?>
        <span>confirm</span> the alert
    <?php $this->endslot() ?>
    
    <?php $this->addslot('close', 'Close info') ?>

<?php $this->component('alert')->end() ?>
```

The HTML rendering will be:
```html
<div class="alert alert-danger">
    <div>
        <p>Danger:</p>
        this is an alert
    </div>
    <button id="confirm"><span>confirm</span> the alert</button>
    <button id="close">Close info</button>
</div>
```

All texts not in a slot will be placed in the default slot. Slots can be conditional:

```php
<?php $this->addslot('close', 'Close info')->if($type == 'info') ?>
```

You can also make a default value for empty slot to avoid error message
```php
<?= $this->slot('default', 'default text') ?>
```

## Licence

MIT