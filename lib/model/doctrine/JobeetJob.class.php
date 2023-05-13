<?php

/**
 * JobeetJob
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    jobeet
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 6820 2009-11-30 17:27:49Z jwage $
 */
class JobeetJob extends BaseJobeetJob {

    public function updateLuceneIndex() {
        $index = $this->getTable()->getLuceneIndex();

        // remove an existing entry
        if ($hit = $index->find('pk:'.$this->getId())) {
            $index->delete($hit->id);
        }

        // don't index expired and non-activated jobs
        if ($this->isExpired() || !$this->getIsActivated()) {
            return;
        }

        $doc = new Zend_Search_Lucene_Document();

        // store job primary key URL to identify it in the search results
        $doc->addField(Zend_Search_Lucene_Field::UnIndexed('pk', $this->getId()));

        // index job fields
        $doc->addField(Zend_Search_Lucene_Field::UnStored('position', $this->getPosition(), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::UnStored('company', $this->getCompany(), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::UnStored('location', $this->getLocation(), 'utf-8'));
        $doc->addField(Zend_Search_Lucene_Field::UnStored('description', $this->getDescription(), 'utf-8'));

        // add job to the index
        $index->addDocument($doc);
        $index->commit();
    }


    public function asArray($host) {
        return array(
                'category'     => $this->getJobeetCategory()->getName(),
                'type'         => $this->getType(),
                'company'      => $this->getCompany(),
                'logo'         => $this->getLogo() ? 'http://'.$host.'/uploads/jobs/'.$this->getLogo() : null,
                'url'          => $this->getUrl(),
                'position'     => $this->getPosition(),
                'location'     => $this->getLocation(),
                'description'  => $this->getDescription(),
                'how_to_apply' => $this->getHowToApply(),
                'expires_at'   => $this->getCreatedAt(),
        );
    }


    public function getTypeName() {
        $types = Doctrine::getTable('JobeetJob')->getTypes();
        return $this->getType() ? $types[$this->getType()] : '';
    }

    public function isExpired() {
        return $this->getDaysBeforeExpires() < 0;
    }

    public function expiresSoon() {
        return $this->getDaysBeforeExpires() < 5;
    }

    public function getDaysBeforeExpires() {
        return floor(($this->getDateTimeObject('expires_at')->format('U') - time() )/ 86400);
    }
    public function __toString() {
        return sprintf('%s at %s (%s)', $this->getPosition(), $this->getCompany(), $this->getLocation());
    }

    public function getCompanySlug() {
        return Jobeet::slugify($this->getCompany());
    }

    public function getPositionSlug() {
        return Jobeet::slugify($this->getPosition());
    }

    public function getLocationSlug() {
        return Jobeet::slugify($this->getLocation());
    }

    public function publish() {
        $this->setIsActivated(true);
        $this->save();
    }

    public function extend($force = false) {
        if (!$force && !$this->expiresSoon()) {
            return false;
        }

        $this->setExpiresAt(date('Y-m-d', time() + 86400 * sfConfig::get('app_active_days')));
        $this->save();

        return true;
    }

    public function retrieveBackendJobList(Doctrine_Query $q) {
        $rootAlias = $q->getRootAlias();
        $q->leftJoin($rootAlias . '.JobeetCategory c');
        return $q;
    }


    public function save(Doctrine_Connection $conn = null) {
        if ($this->isNew() && !$this->getExpiresAt()) {
            $now = $this->getCreatedAt() ? $this->getDateTimeObject('created_at')->format('U') : time();
            $this->setExpiresAt(date('Y-m-d H:i:s', $now + 86400 * sfConfig::get('app_active_days')));
        }

        if (!$this->getToken()) {
            $this->setToken(sha1($this->getEmail().rand(11111, 99999)));
        }

        $conn = $conn ? $conn : $this->getTable()->getConnection();
        $conn->beginTransaction();
        try {
            $ret = parent::save($conn);

            $this->updateLuceneIndex();

            $conn->commit();

            return $ret;
        }
        catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }

    }

    public function delete(Doctrine_Connection $conn = null) {
        $index = $this->getTable()->getLuceneIndex();

        if ($hit = $index->find('pk:'.$this->getId())) {
            $index->delete($hit->id);
        }

        return parent::delete($conn);
    }
}