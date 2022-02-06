# Mky Template Engine  
  
> *Moteur de template php par Micky-N* 

[![Generic badge](https://img.shields.io/badge/mky-master-orange.svg)](https://shields.io/) [![Generic badge](https://img.shields.io/badge/version-1.0.1-green.svg)](https://shields.io/)

Inspirée de Jsp (java), le moteur de template utilise des balises `<mky/>` pour générer le code php sur la vue.

### Installation

`composer require mky/mky-engine`

### Configuration

La configuration doit être sous forme de tableau avec le chemin des vues et celui du cache qui servira de sauvegarde pour les vues compilées
```php
$config = [
    'views' => 'chemin des vues',
    'cache' => 'chemin du cache' // optionnel
]
```
cette configuration est l'argument du constructeur de la classe MkyEngine
```php
$mkyEngine = new Mky\MkyEngine($config);
```
pour afficher une vue utiliser la méthode `$mkyEngine->view($view, $params)`
le nom des $view s'écrit avec un point `todos.index`: dossier /chemin_des_vues/todos/index.mky et les variables passées en tableau `['todo' => valeur]`.

### MkyDirective

Exemple avec la directive "if":
```html
<mky:if cond="$todo->task == 'Coder'">
    <div>true</div>
    <mky:else />
    <div>false</div>
</mky:if>
```
Si la condition est vrai alors il affichera le texte true sinon false, il existe 2 types de directives : longue portée et courte portée. Les longues portées englobe le code html pour le transformer, ex: if, each, repeat... ils s'écrivent 
`<mky:directive params="">...html...</mky:directive>` 
et les courtes portées affichent un resultat ex: route, json, ... et s'écrivent
`<mky:directive params="" />` 
pour les paramètres les guillemets ne sont pas obligatoire.

les vues .mky utilisent le système d'**extends**, **yield** et **sections**, avec la directive **include** la vue peut intégrer d'autres vues, les vue inclues héritent des variables des parents et peuvent recevoir des variables personnalisées.
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

la directive yield peut avoir une valeur par défaut grâce au paramètre value `<mky:yield name="title" value="Home"/>` et section peut devenir une directive courte en rajoutant
le paramètre value `<mky:section name="title" value="TodoPage"/>` 


### Liste des directives

```yaml
assets: courte
script: court si src, sinon longue
style: court si href, sinon longue
if: longue
elseif: courte
else: courte
each: longue
repeat: longue
switch: longue
case: courte
break: courte
default: courte
dump: courte
permission: longue
notpermission: longue
auth: longue
json: courte
currentRoute: longue
route: courte
set: courte si value, sinon longue
php: longue
```
Pour créer des directives, déclarer une classe qui implémente l'interface Core\Interfaces\MkyDirectiveInterface et étendre de la classe Core\MkyCompiler\MkyDirectives\Directive. Pour ajouter un ou plusieurs directives, utiliser la méthode\
`$mkyEngine->addDirectives(new TestDirective()) ou $mkyEngine->addDirectives([new TestDirective(), new OtherDirective()])` 
```php
class TestDirective extends Directive implements MkyDirectiveInterface  
{  
  
    public function getFunctions()  
    {  
        // déclarer les fonctions dans le tableau
        return [  
            'shortTest' => [$this, 'shortTest'], // pour les courtes directives
            'longTest' => [[$this, 'longTest'], [$this, 'endlongTest']] // pour les longues directives
        ];  
    }  
    
    // implémenter les fonctions
    public function shortTest($int)  
    {  
        // $var = 10;
	    // dans la vue: <div><mky:shortTest int="$var"/></div>
	    // devient <div>$var = 15 (10 + 5)</div>
	    // pour récuperer le nom de la variable passée en paramètres: $this->getRealVariable($int) => $var
        return sprintf('%s = %s (%s + 5)', $this->getRealVariable($int), $int + 5, $int);  
    }

    // <mky:longTest cls="customClass">-- HTML --</mky:longTest>
    // devient <div class="customClass">-- HTML --</div>
    public function longTest($cls)  { return '<div class="'.$cls.'">'; }
    public function endlongTest()   { return '</div>'; }
}
``` 

### MkyFormatter

Les formatters permettent de modifier les variables php dans la vues et s'ecrivent avec un # devant la variable 
`{{ $var#euro }}`,
si le formatter 'euro' permet de mettre un chiffre en format devise en euro alors si  $var = 5 alors `$var#euro => 5,00 €`.
Pour créer des formatters, déclarer une classe qui implémente Core\Interfaces\MkyFormatterInterface. Pour ajouter un ou plusieurs formatters, utiliser la méthode\
`$mkyEngine->addFormatters(new ArrayFormatter()) ou $mkyEngine->addFormatters([new ArrayFormatter(), new OtherFormatter()])` 

```php
class ArrayFormatter implements MkyFormatterInterface  
{  
  
    public function getFormats()  
    {  
        // déclarer le fonctions dans le tableau
        return [  
            'join' => [$this, 'join'],  
            'count' => [$this, 'count']  
        ];  
    }  
  
    // implémenter les fonctions
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
les formatters peuvent s'enchainer à la suite `{{ $name#format1 #format2 }}`

Merci pour votre lecture, pour m'aider à améliorer ce package faite des Issues !
