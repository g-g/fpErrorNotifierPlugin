
#fpErrorNotifierPlugin

## Overview

This is a fork of (makasim / fpErrorNotifierPlugin )[https://github.com/makasim/fpErrorNotifierPlugin]. The only change is
that it uses app.yml iinstead of notify.yml for configuration because then the configuration is cached.

The plugin for those people how want to feel confident in your code. 
If something goes wrong you are the first to be notified about it.
The email not only contains an error message but a bunch of useful information.

It takes control over the system and catches every error: exceptions, fatal errors, notices, memory limit error, php parse errors and so on.
It easy to customize because the plugin was made as a set of components: _message_, _driver_, _handler_, _decorator_. 

## Requirements

* [Symfony](http://www.symfony-project.org) 1.1 - 1.4

## Attention
If this plugin does not work in a dev environment, that is because the WebDebugLogger installs it's own error handler later, and thus overwrites our handler.

## Installation

### Download:
#### Pear package

    php symfony plugin:install fpErrorNotifierPlugin

#### Git dev: 

    git clone git://github.com/g-g/fpErrorNotifierPlugin.git

#### Git tag:

    git clone git://github.com/makasim/fpErrorNotifierPlugin.git 
    cd fpErrorNotifierPlugin
    git tag
    # check out the latest tag - 1.0.0 for this example
    git checkout 1.0.0

### Enable it

    class ProjectConfiguration extends sfProjectConfiguration
    {
      public function setup()
      {
        $this->enablePlugins('fpErrorNotifierPlugin');
      }
    }

## Getting Started

The general way of using the plugin is to send emails about each error that happend on the server (including exceptions, notice, fatal and so on).
Let's look at this example to know how to do it with this plugin.


First we need to install the plugin. It was described earlier and it is a common symfony plugin installation
Second we have to edit _app.yml_ in any of the project config dir (I will store it in _SF_ROOT_DIR/config/app.yml_) with a next content:

_app.yml_

    all:
      fp_error_notifier:
        driver:
          class:                  fpErrorNotifierDriverMailNative
          options:
            to:                   'manager@example.com,teamleader@example.com,developer@example.com'
            from:                 'noreply@live.example.com'

That's it. Now we have all errors and exceptions caught and sent to the development team members.

## Features

### The app.yml config

After you setup the plugin it starts to work. By default it logs the last error into a file in the log dir.
To change this behavior you need to create/edit _app.yml_ in project or app config folder.

So let's say I copy _app.yml_ from plugin's config directory to _SF_ROOT_DIR/config/app.yml_

_app.yml_

    prod:
      fp_error_notifier:
        all:
          handler:
            class:                      fpErrorNotifierHandlerIgnore
            options:                    {ignore_@: false, ignore404: true}
            
          message:
            class:                   fpErrorNotifierMessage
            options:                 {}
        
          helper: 
            class:                   fpErrorNotifierMessageHelper
            options:                 {}
        
          decorator:
            class:                   fpErrorNotifierDecoratorHtml
            options:                 {}
      
          driver: 
            class:                   fpErrorNotifierDriverNull
            options:                 {}

As you can see we have some stuff like handler, message, helper, decorator and driver:

* Handler - it is the most valuable thing. Because it catches all errors and handles them.
* Message - is just a data container.
* Helper  - helps to fill the _message_ with an information (like fill message from Exception instance).
* Decorator - it wrapps the message and know hot the message can be rendered.
* Driver - it is a object which knows where to send or store the message.

### Handlers

There are two handlers which comes with the plugin:

* _fpErrorNotifierHandler_ - base implementation
* _fpErrorNotifierHandlerIgnore_ - extended version with some ignoring abilities.

_fpErrorNotifierHandler_ can take one option and can be configured like this:

_app.yml_

    all:
      fp_error_notifier:
        handler:
          class:                   fpErrorNotifierHandler
    # Set the notify404 option to true to get notified for missing pages.
          options:                 {notify404: false}

_fpErrorNotifierHandlerIgnore_:

_app.yml_

    all:
      fp_error_notifier:
        handler:
          class:                   fpErrorNotifierHandlerIgnore
            options:
            ignore_@:                false
            ignore_errors:           [<?php echo E_ERROR ?>, <?php echo E_NOTICE ?>]
            ignore_exceptions:       [FooException]
            log_ignored:             true
            ignore_duplication:      true
            ignore_duplication_time: 10 # seconds
            ignore404:               true

You can avoid sending duplicated errors for some period of time. Ignore some php errors or exception.
Also it is possible to get notifications that happend under the '@' command.

### Drivers

There are four drivers comes with the plugin:

* _fpErrorNotifierDriverMailNative_ - use php's mail function to send an email.

_app.yml_

    all:
      fp_error_notifier:
        driver:
          class:                  fpErrorNotifierDriverMailNative
          options:
            to:                   'manager@example.com,teamleader@example.com,developer@example.com'
            from:                 'noreply@live.example.com'

* _fpErrorNotifierDriverMailSymfony_ - use a mailer (It should be Swift) configured via _factories.yml_. It is taken from sfContext.

_app.yml_

    all:
      fp_error_notifier:
        driver:
          class:                  fpErrorNotifierDriverMailSymfony
          options:
            to:                   'manager@example.com,teamleader@example.com,developer@example.com'
            from:                 'noreply@live.example.com'
          
It is an example of SWIFT mailer configuration with _gmail.com_ account 

_factories.yml_

    mailer:
      class: sfMailer
      param:
        logging:           %SF_LOGGING_ENABLED%
        charset:           %SF_CHARSET%
        delivery_strategy: realtime
        transport:
          class: Swift_SmtpTransport
          param:
            host:       smtp.gmail.com
            port:       587
            encryption: tls
            username:   your-account@gmail.com
            password:   'password'

* _fpErrorNotifierDriverFile_ - store the last error to the file (It can be helpfull for testing services in development process).

_app.yml_

    driver:
      class:             sfErrorNotifierDriverFile
        options:         
          path:          '%SF_LOG_DIR%/last-error.html'

* _fpErrorNotifierDriverNull_ - just does do nothing

### Decorators

You can render the message as simple text or html (set by default).

* _fpErrorNotifierDecoratorHtml_

_app.yml_

    all:
      fp_error_notifier:
        decorator:
          class:                   fpErrorNotifierDecoratorHtml
          options:                 {}
        
* _fpErrorNotifierDecoratorText_

_app.yml_

    all:
       decorator:
          class:                   fpErrorNotifierDecoratorText
          options:                 {}

## Customizing 

### Send a custom message

    <?php
    
    $message = fpErrorNotifier::getInstance()->decoratedMessage('A Custom message title');
    $message->addSection('Detailed info', array('Detail 1' => 'Foo', 'Detail 2' => 'Bar'));
    
    fpErrorNotifier::getInstance()->driver()->notify($message);
    
But this code creates a hard coded relation between your code and the plugin isn't it? 
It can be done this way but it is not a good idea. 
So how can we do it better?
Below we are sending absolutly the same message using sfEventDispatcher:
    
    <?php
    
    $dispatcher = sfContext::getInstance()->getEventDispatcher();
    $event = new sfEvent('A Custom message title', 'notify.send_message', array('Detail 1' => 'Foo', 'Detail 2' => 'Bar'));
    
    $dispatcher->notify($event);
    
### Add more info to the error message

    <?php 
    
    function addMoreErrorInfo(sfEvent $event)
    {
      $message = $event->getSubject();
      $message->addSection('Detailed info', array('Detail 1' => 'Foo', 'Detail 2' => 'Bar'));
    }
    
    // notify.decorate_message for adding additional info to custom simple messages
    fpErrorNotifier::getInstance()->dispather()->connect('notify.decorate_exception', 'addMoreErrorInfo');
    
    // then when an error happend this event would be raised and additional info added.
    
    
    
### Use custom driver

    <?php 
    
    $driver = new sfErrorNotifierDriverMailNative(array(
      'to' => 'first.developer@example.com',
      'from,' => 'noreplay@yout-project.com'));

### Run the plugin tests

It's used _[sfPhpunitPlugin](http://www.symfony-project.org/plugins/sfPhpunitPlugin)_ as a testing framework.

So to run test you need this plugin first. Then you can run this command to execute the plugin tests.

    ./symfony phpunit --only-plugin=fpErrorNotifierPlugin

## Feedback

This is a fork of (makasim / fpErrorNotifierPlugin )[https://github.com/makasim/fpErrorNotifierPlugin]. The only change is
that it uses app.yml instead of notify.yml for configuration because then the configuration is cached. I do not intend to improve the plugin, because
it works very well. Please report only bugs with app.yml here, all other issues please send to the original maintainer, see below:

[quote from="makasim"]I am very welcome for any comments suggestions, bug fixes, implementations and so on. You can create a 
ticket at my [github repository](http://github.com/makasim/fpErrorNotifierPlugin/issues) or make a fork and do your changes.[/quote]