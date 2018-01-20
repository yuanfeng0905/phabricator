<?php

final class PhabricatorAuthSSHRevoker
  extends PhabricatorAuthRevoker {

  const REVOKERKEY = 'ssh';

  public function revokeAllCredentials() {
    $query = new PhabricatorAuthSSHKeyQuery();
    return $this->revokeWithQuery($query);
  }

  public function revokeCredentialsFrom($object) {
    $query = id(new PhabricatorAuthSSHKeyQuery())
      ->withObjectPHIDs(array($object->getPHID()));

    return $this->revokeWithQuery($query);
  }

  private function revokeWithQuery(PhabricatorAuthSSHKeyQuery $query) {
    $viewer = $this->getViewer();

    // We're only going to revoke keys which have not already been revoked.

    $ssh_keys = $query
      ->setViewer($viewer)
      ->withIsActive(true)
      ->execute();

    $content_source = PhabricatorContentSource::newForSource(
      PhabricatorDaemonContentSource::SOURCECONST);

    $auth_phid = id(new PhabricatorAuthApplication())->getPHID();
    foreach ($ssh_keys as $ssh_key) {
      $xactions = array();
      $xactions[] = $ssh_key->getApplicationTransactionTemplate()
        ->setTransactionType(PhabricatorAuthSSHKeyTransaction::TYPE_DEACTIVATE)
        ->setNewValue(1);

      $editor = id(new PhabricatorAuthSSHKeyEditor())
        ->setActor($viewer)
        ->setActingAsPHID($auth_phid)
        ->setContinueOnNoEffect(true)
        ->setContinueOnMissingFields(true)
        ->setContentSource($content_source)
        ->applyTransactions($ssh_key, $xactions);
    }

    return count($ssh_keys);
  }

}
