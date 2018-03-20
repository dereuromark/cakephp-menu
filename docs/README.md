# Menu plugin
```
composer require .../cakephp-menu
```

and
```
bin/cake plugin load Menu
```

## Usage
Make sure to load the helper, e.g. in your AppView:
```php
	public function initialize() {
		$this->loadHelper('Menu.Menu');
	}
```


### In the View
First build your menu, either inside a helper, an element, or even else:
```php
use Menu\Menu;

$menu = Menu::create();
$menu->...
...
```

Now you can just output it somewhere:
```php
echo $this->Menu->render($menu);
```



## Contributing

Please add unit tests for new functionality.

Make sure tests are green:
```
composer test
```

You can check coverage with
```
composer test-coverage
```

Running CS sniffer:
```
composer cs-check
```

Running CS fixer:
```
composer cs-fix
```
