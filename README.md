# MkyEngine  
  
> *Php template engine by Micky-N* 

[![Generic badge](https://img.shields.io/badge/mky-master-orange.svg)](https://shields.io/) [![Generic badge](https://img.shields.io/badge/version-1.0.0-green.svg)](https://shields.io/)

The template engine uses `<mky/>` tags to generate php code on the view.

### Installation

`composer require micky/mky-engine`

### Configuration

The configuration must have the views, templates and includes directory and cache directory which will serve as a backup for  compiled views
```php
$config = [
    'views' => 'views directory',
	'layouts' => 'layouts directory', // optional, if not layouts dir = views dir
	'includes' => 'includes directory', // optional, if not includes dir = views dir
    'cache' => 'cache directory' // optional
]
```
this configuration is the constructor argument of the \MkyEngine\MkyEngine class
```php
$mkyEngine = new \MkyEngine\MkyEngine($config);
```
To see view file in browser `echo $mkyEngine->view($view, $params)`,  $view path are written with point 
 `todos.index` from config point : config_views/todos/index.mky and variables are pass as array `['todo' => value]`.

### MkyDirective

Example with "if" directive:
```html
<mky:if cond="$todo->task == 'Coder'">
    <div>true</div>
    <mky:else />
    <div>false</div>
</mky:if>
```
If the condition is true then it will display the text "true" otherwise "false", there are 2 types of directives: long range and short range. Long range directive includes an html code to transform it, ex: if, each, repeat... they are written
`<mky:directive params="">...html...</mky:directive>` and the short ranges display a result ex: json, and are written `<mky:directive params="" />`.

*.mky views use the system of **extends**, **yield** and **sections**. With the **include** directive a view can integrate other views, included views inherit parents variables and can receive custom variables.
```html
// views/layouts/template.mky
-- HEADER HTML --
	<mky:yield name="content"/>
-- FOOTER HTML --
```
```html
// views/todos/index.mky
<mky:extends name="layouts.template" />
<mky:section name="content">
-- HTML --
	<mky:include name="includeView" data="['var' => 'variable1']"/>
</mky:section>
```

the yield directive can have a default value with the default parameter `<mky:yield name="title" default="HomePage"/>` and section can become a short directive by adding the value parameter\
 `<mky:section name="title" value="TodoPage"/>`


#### List of directives

```yaml
script(src: null|string): short if src not null, otherwise long
// get js file or write js script

style(href: null|string): short if href not null, otherwise long
// get css file or write css


if(cond: bool): long
elseif(cond: bool): short
else: short
// if condition

each(loop: array, as: string|null, key: string|null): long
// foreach loop

repeat(for: int, step: int|null, key: string|null): long
// for loop

switch(cond: mixed): long
case(case: mixed): short
break: short
default: short
// switch condition

set(key: string, value: mixed|null): short if value not null, otherwise long
// set value for a new variable php ex: <mky:set key="k" value="5"/> => $k = 5
// or set('k', 'value') <mky:set key="k">--HTML--<mky:set/> => $k = --HTML--

php: long
// write php code in template
```
To create directives, create a class that implements the \MkyEngine\Interfaces\MkyDirectiveInterface interface and use the "addDirectives" method to add one or multiple directives\
`$mkyEngine->addDirectives(new TestDirective()) or $mkyEngine->addDirectives([new TestDirective(), new OtherDirective()])` 

```php
class TestDirective implements MkyDirectiveInterface  
{  
  
    public function getFunctions()  
    {  
        // set function in the return array like:
        return [  
            'shortTest' => [$this, 'shortTest'], // for short directive
            'longTest' => [[$this, 'longTest'], [$this, 'endlongTest']] // for long directive
        ];  
    }  
    
    // implement the functions
    public function shortTest($int)  
    {  
        // $var = 10;
	    // in the view: <div><mky:shortTest int="$var"/></div>
	    // become <div>$var = 15 (10 + 5)</div>
	    // to get the variable name: $this->getRealVariable($int) => $var
        return sprintf('%s = %s (%s + 5)', $this->getRealVariable($int), $int + 5, $int);  
    }

    // <mky:longTest cls="customClass">-- HTML --</mky:longTest>
    // become <div class="customClass">-- HTML --</div>
    public function longTest($cls)  { return '<div class="'.$cls.'">'; }
    public function endlongTest()   { return '</div>'; }
}
``` 

### MkyFormatter

The formatters can change the php variables in the views and are written with a "#" on the right of the variable
`{{ $var#currency }}`, the 'currency' formatter allows to change variable to a currency format, by default is euro "€" then 
if $var = 5 then `$var#currency => 5,00 €`.
To create formatters, create a class that implements \MkyEngine\Interfaces\MkyFormatterInterface interface, use the "addFormatters" method to add one or multiple formatters\
`$mkyEngine->addFormatters(new ArrayFormatter()) or $mkyEngine->addFormatters([new ArrayFormatter(), new OtherFormatter()])`

```php
class ArrayFormatter implements MkyFormatterInterface  
{  
  
    public function getFormats()  
    {  
        // set function in the return array like:
        return [  
            'join' => [$this, 'join'],  
            'count' => [$this, 'count']  
        ];  
    }  
  
    // implement the functions
    public function join(array $array, string $glue = ', ')  
    {  
	    // $var = [1,5,3]; {{ $var#join('!') }} => 1!5!3
        return join($glue, $array);  
    }  
  
    public function count(array $array)  
    {  
        // $var = [1,5,3]; {{ $var#count }} => 3
        return count($array);  
    }  
}
```
Formatters can be chained as `{{ $name #format1 #format2 }}`

#### List of formatters

```yaml
currency: value to currency, params: string currency (default: 'EUR'), string locale (default: 'fr_FR'),  
uppercase: value to uppercase,  
lowercase: value to lowercase,  
firstCapitalize: uppercase first letter,  
join: array to string, param: string separator (default: ', '),  
count: get the number of elements in the array,  
dateformat: set date to format, param: string format (default:'Y-m-d H:i:s')
```
<hr>
For help me to improve this package made of issues!

For see tests: `composer require micky/mky-engine:dev-test`
