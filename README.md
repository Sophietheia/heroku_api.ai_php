# heroku_api.ai_php.git

Trying to make Heroku work with Api.ai. Basically translating a Python script I found, into PHP.

An app developped with [Silex](http://silex.sensiolabs.org/) web framework, which can easily be deployed to Heroku.


## Deploying

Install the [Heroku Toolbelt](https://toolbelt.heroku.com/).

```sh
$ git clone https://github.com/tanohzana/heroku_api.ai_php.git # or clone your own fork
$ cd heroku_api.ai_php.git
$ heroku create (install heroku first, via gem in ruby)
$ git push heroku master
$ heroku open
```

or

[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.png)](https://heroku.com/deploy)

## Documentation

For more information about using PHP on Heroku, see these Dev Center articles:

- [Getting Started with PHP on Heroku](https://devcenter.heroku.com/articles/getting-started-with-php)
- [PHP on Heroku](https://devcenter.heroku.com/categories/php)
