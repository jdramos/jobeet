<?php

include(dirname(__FILE__).'/../../bootstrap/functional.php');

$browser = new JobeetTestFunctional(new sfBrowser());
$browser->loadData();

$browser->
  info('1 - When validating an affiliate, an email must be sent with its token')->
  get('/')->
  with('request')->begin()->
        isParameter('module', 'job')->
        isParameter('action', 'index')->
  end()->
  get('/login')->
  click('sign in', array('signin' => array(
    'username'      => 'admin',
    'password'      => 'admin',
  )))->
  with('user')->isAuthenticated()->
        with('response')->isRedirected()->
        followRedirect()->

  get('/backend_test.php/affiliate')->
  click('activate', array(), array('position' => 1))->
  with('mailer')->begin()->
    checkHeader('Subject', '/Jobeet affiliate token/')->
    checkBody('/Your token is symfony/')->
  end()
;
