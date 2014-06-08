Amygdala
========

![Amygdala][1]

[![Build Status](https://api.travis-ci.org/Giuseppe-Mazzapica/Amygdala.svg)](https://travis-ci.org/Giuseppe-Mazzapica/Amygdala)

Amygdala is a package (not full plugin) that wraps request informations and ease the getting of data without having to deal with superglobals.

It makes use of [composer][2] to be embedded in larger projects.

It is part of the [Brain Project][3] module.

###API###

Amygdala package comes with an API that ease its usage, without having to get, instantiate or digging into package objects. API is defined in a class, stored in the Brain (Pimple) container with the id: `"amygdala"`.
So is possible to get it using Brain instance, something like: `$api = Brain\Container::instance()->get("amygdala")`, and then call all API function on the instance got in that way. However that's not very easy to use, especially for people used to just use a plain function to add and trigger hooks.
This is the reason why package also comes with a **facade class**. The term is not referred to [façade pattern][4], but more to [Laravel facades][5], whence the approach (not actual code) comes from: no *real* static method is present in the class, but a single `__callstatic` method that *proxy* API methods to proper instantiated objects.

The facade class is named `Request` inside Brain namespace. A simple example to get a variable from `$_GET` sanitized as url using `'example.com'` as default:

    Brain\Request::query( 'adomain', 'example.com', FILTER_SANITIZE_URL );


###Embed in OOP projects###

The static facade class is easy to use, however using in that way inside other classes, create there hardcoded dependency to Amygdala. In addition, unit testing other classes in isolation becomes pratically impossible.
To solve these problems, the easiest way is to use composition via dependency injection.
In facts, the `Brain\Request` facade class can be used in dynamic way, like so:

    $request = new Brain\Request;
    $request->query( 'adomain', 'example.com', FILTER_SANITIZE_URL );
    
There is absolutely no difference in the two methods, but using the latter is possible to inject an instance of the class inside other classes. See the following example:

    class A_Plugin_Class {
    
      function __construct( \Brain\Request $request ) {
        $this->request = $request;
      }
      
      function get_a_POST_value( $a_key, $default = '', $filter = FILTER_UNSAFE_RAW ) {
        return $this->request->post( $a_key, $default, $filter );
      }
      
    }

The method `get_a_POST_value` makes use of `$this->request` property to call the Amygdala API method.
Testing the method in isolation is very simple too, an example using PHPUnit and Mockery:

    class A_Plugin_Class_Test () {
    
      function test_get_a_POST_value() {
        $request = \Mockery::mock('\Brain\Request');
        $request->shouldReceive( 'post' )->once()->with( 'foo', 'bar' )->andReturn( 'A value!' );
        $class = new A_Plugin_Class( $request );
        $this->assertEquals( 'A value!', $class->get_a_POST_value( 'foo', 'bar' ) );
      }
      
    }

So the method is tested in isolation, mocking a $_POST request: easy and straightforward.

###Gotchas!###

Amygdala is a Brain module. As you can read in [Brain readme][6], it bootstrap itself and its modules on `after_setup_theme` with priority 0, this mean that you **can't use Amygdala before `after_setup_theme` is fired**.

###Requirements###

 - PHP 5.4+
 - Composer (to install)
 - WordPress 3.9 (it *maybe* works with earlier versions, but it's not tested and versions < 3.9 will never supported).

###Installation###

You need [Composer][7] to install the package. It is hosted on [Packagist][8], so the only thing needed is insert `"brain/amygdala": "dev-master"` in your `composer.json` `require` object

    {
        "require": {
            "php": ">=5.4",
            "brain/amygdala": "dev-master"
        }
    }

See [Composer documentation][9] on how to install Composer itself, and packages.
 
###Codename: Amygdala###

The *Amygdala*, is a part of the brain that shown in research to perform a primary role in the processing of decision-making.

Amygdala package is so called because is a [Brain][10] module, and an http request is what makes WordPress (most web applications indeed) makes decision on what to do.

###Developers & Contributors###

Package is open to contributors and pull requests. It comes with a set of unit tests written for [PHPUnit][11] suite. Please be sure all tests pass before submit a PR.
To run tests, please install package in stand-alone mode (i.e 'vendor' folder is inside package folder).
When installed in *dev* mode Striatum also install [Mockery][12], a powerful mocking test utility.

###License###

Amygdala own code is licensed under GPLv2+. Through Composer, it install code from:

 - [Composer][13] (MIT)
 - [Brain](https://github.com/Giuseppe-Mazzapica/Brain) (GPLv2+)
 - [Pimple][14] (MIT) - required by Brain -
 - [PHPUnit][15] (BSD-3-Clause) - only dev install -
 - [Mockery][16] (BSD-3-Clause) - only dev install -


  [1]: https://googledrive.com/host/0Bxo4bHbWEkMscmJNYkx6YXctaWM/amygdala.png
  [2]: https://getcomposer.org/
  [3]: http://giuseppe-mazzapica.github.io/Brain
  [4]: http://en.wikipedia.org/wiki/Facade_pattern
  [5]: http://laravel.com/docs/facades
  [6]: https://github.com/Giuseppe-Mazzapica/Brain/blob/master/README.md
  [7]: https://getcomposer.org/
  [8]: https://packagist.org/
  [9]: https://getcomposer.org/doc/
  [10]: https://github.com/Giuseppe-Mazzapica/Brain
  [11]: http://phpunit.de/
  [12]: https://github.com/padraic/mockery
  [13]: https://getcomposer.org/
  [14]: http://pimple.sensiolabs.org/
  [15]: http://phpunit.de/
  [16]: https://github.com/padraic/mockery
