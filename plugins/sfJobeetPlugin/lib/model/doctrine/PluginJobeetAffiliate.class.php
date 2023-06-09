<?php

/**
 * JobeetAffiliate
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    jobeet
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
abstract class PluginJobeetAffiliate extends BaseJobeetAffiliate {

  public function activate()
  {
    $this->setIsActive(true);

    return $this->save();
  }

  public function deactivate()
  {
    $this->setIsActive(false);

    return $this->save();
  }


    public function __toString() {
        return $this->getUrl();
    }

    public function getActiveJobs() {
        $q = Doctrine_Query::create()
                ->select('j.*')
                ->from('JobeetJob j')
                ->leftJoin('j.JobeetCategory c')
                ->leftJoin('c.JobeetAffiliates a')
                ->where('a.id = ?', $this->getId());

        $q = Doctrine::getTable('JobeetJob')->addActiveJobsQuery($q);

        return $q->execute();
    }


    public function preValidate($event) {
        $object = $event->getInvoker();

        if (!$object->getToken()) {
            $object->setToken(sha1($object->getEmail().rand(11111, 99999)));
        }
    }


}
