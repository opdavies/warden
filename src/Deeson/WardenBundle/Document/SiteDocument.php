<?php

namespace Deeson\WardenBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(
 *     collection="sites"
 * )
 */
class SiteDocument extends BaseDocument {

  /**
   * @Mongodb\String
   */
  protected $name;

  /**
   * @Mongodb\Boolean
   */
  protected $isNew = TRUE;

  /**
   * @Mongodb\String
   */
  protected $url;

  /**
   * @Mongodb\Hash
   */
  protected $coreVersion;

  /**
   * @Mongodb\String
   */
  protected $wardenToken;

  /**
   * @Mongodb\String
   */
  protected $wardenEncryptToken;

  /**
   * @Mongodb\Collection
   */
  protected $modules;

  /**
   * @Mongodb\String
   */
  protected $authUser;

  /**
   * @Mongodb\String
   */
  protected $authPass;

  /**
   * @Mongodb\Hash
   */
  protected $additionalIssues;

  /**
   * @return mixed
   */
  public function getName() {
    return (empty($this->name)) ? '[Site Name]' : $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getWardenToken() {
    return $this->wardenToken;
  }

  /**
   * @param string $wardenToken
   */
  public function setWardenToken($wardenToken) {
    $this->wardenToken = $wardenToken;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param mixed $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  /**
   * @return mixed
   */
  public function getCoreVersion() {
    return (empty($this->coreVersion['current'])) ? '0' : $this->coreVersion['current'];
  }

  /**
   * @param mixed $coreVersion
   */
  public function setCoreVersion($coreVersion) {
    $majorRelease = ModuleDocument::getMajorVersion($coreVersion);
    if (!isset($this->coreVersion)) {
      $this->coreVersion = array();
    }
    /*$this->coreVersion = array_merge(array(
      'release' => $majorRelease,
      'current' => $coreVersion,
    ));*/
    $this->coreVersion['release'] = $majorRelease;
    $this->coreVersion['current'] = $coreVersion;
  }

  /**
   * @return mixed
   */
  public function getCoreReleaseVersion() {
    return (empty($this->coreVersion['release'])) ? '0' : $this->coreVersion['release'];
  }

  /**
   * @return mixed
   */
  public function getLatestCoreVersion() {
    return (empty($this->coreVersion['latest'])) ? '0' : $this->coreVersion['latest'];
  }

  /**
   * @param mixed $latestVersion
   * @param boolean $isSecurity
   */
  public function setLatestCoreVersion($latestVersion, $isSecurity = FALSE) {
    /*$this->coreVersion += array(
      'latest' => $latestVersion,
      'isSecurity' => $isSecurity,
    );*/
    $this->coreVersion['latest'] = $latestVersion;
    $this->coreVersion['isSecurity'] = $isSecurity;
  }

  /**
   * @return boolean
   */
  public function getIsSecurityCoreVersion() {
    return (empty($this->coreVersion['isSecurity'])) ? FALSE : $this->coreVersion['isSecurity'];
  }

  /**
   * Get the site modules.
   *
   * @todo move this into the DrupalSiteService
   * @return mixed
   */
  public function getModules() {
    return (!empty($this->modules)) ? $this->modules : array();
  }

  /**
   * Set the current modules for the site.
   *
   * @param array $modules
   *   List of modules to add to the site.
   * @param bool $update
   *   If true, update the site module versions while using the existing version
   *   information.
   */
  public function setModules($modules, $update = FALSE) {
    $currentModules = ($update) ? $this->getModules() : array();
    if (!empty($currentModules)) {
      $currentVersions = array();
      foreach ($currentModules as $value) {
        $currentVersions[$value['name']] = $value;
      }
    }

    $moduleList = array();
    foreach ($modules as $name => $version) {
      $module = array(
        'name' => $name,
        'version' => $version['version'],
        /*'version' => array(
          'current' => $version['version'],
          'latest' => '',
          'isSecurity' => 0,
        ),*/
      );

      // Set the current version if there was one.
      if (isset($currentVersions[$name])) {
        if (isset($currentVersions[$name]['latestVersion'])) {
          $module['latestVersion'] = $currentVersions[$name]['latestVersion'];
        }
        if (isset($currentVersions[$name]['isSecurity'])) {
          $module['isSecurity'] = $currentVersions[$name]['isSecurity'];
        }
      }

      $moduleList[] = $module;
    }
    $this->modules = $moduleList;
  }

  /**
   * Gets a modules latest version for the site.
   *
   * @param $module
   *
   * @return string
   */
  public function getModuleLatestVersion($module) {
    return (!isset($module['latestVersion'])) ? '' : $module['latestVersion'];
  }

  /**
   * Returns if the provided module has a security release.
   *
   * @param $module
   *
   * @return boolean
   */
  public function getModuleIsSecurity($module) {
    return (!isset($module['isSecurity'])) ? FALSE : $module['isSecurity'];
  }

  /**
   * Sets the latest versions of each of the modules for the site.
   *
   * @todo move this into the DrupalSiteService
   * @param $moduleLatestVersions
   */
  public function setModulesLatestVersion($moduleLatestVersions) {
    $siteModuleList = $this->getModules();
    foreach ($siteModuleList as $key => $module) {
      if (!isset($moduleLatestVersions[$module['name']])) {
        continue;
      }
      $moduleVersions = $moduleLatestVersions[$module['name']];

      $versionType = ModuleDocument::MODULE_VERSION_TYPE_RECOMMENDED;
      if (isset($moduleVersions[ModuleDocument::MODULE_VERSION_TYPE_OTHER])) {
        $latestVersion = ModuleDocument::getRelevantLatestVersion($module['version'], $moduleVersions[ModuleDocument::MODULE_VERSION_TYPE_OTHER]['version']);
        if ($latestVersion) {
          $versionType = ModuleDocument::MODULE_VERSION_TYPE_OTHER;
        }
      }

      if (!isset($moduleVersions[$versionType])) {
        print "ERROR : module (" . $module['name'] .") version is not valid: " . print_r(array($versionType, $moduleVersions), TRUE);
        continue;
      }
      /*$siteModuleList[$key] += array(
        'latestVersion' => $moduleVersions[$versionType]['version'],
        'isSecurity' => $moduleVersions[$versionType]['isSecurity'],
      );*/
      $siteModuleList[$key]['latestVersion'] = $moduleVersions[$versionType]['version'];
      $siteModuleList[$key]['isSecurity'] = $moduleVersions[$versionType]['isSecurity'];
    }
    $this->modules = $siteModuleList;
  }

  /**
   * @return mixed
   */
  public function getIsNew() {
    return $this->isNew;
  }

  /**
   * @param boolean $isNew
   */
  public function setIsNew($isNew) {
    $this->isNew = $isNew;
  }

  /**
   * Get a list of site modules that require updating.
   * @todo move this into the DrupalSiteService
   * @return array
   */
  public function getModulesRequiringUpdates() {
    $siteModuleList = $this->getModules();
    $modulesList = array();
    foreach ($siteModuleList as $module) {
      if (isset($module['latestVersion']) && $module['latestVersion'] == $module['version']) {
        continue;
      }

      if (is_null($module['version'])) {
        continue;
      }

      $severity = 1;
      if (isset($module['isSecurity'])) {
        $severity = !$module['isSecurity'];
      }

      $modulesList[$severity][] = $module;
    }
    ksort($modulesList);

    $modulesForUpdating = array();
    foreach ($modulesList as $severity => $moduleSeverity) {
      foreach ($moduleSeverity as $module) {
        $modulesForUpdating[$severity.$module['name']] = $module;
      }
    }
    ksort($modulesForUpdating);

    return $modulesForUpdating;
  }

  /**
   * @return mixed
   */
  public function getAuthPass() {
    return !empty($this->authPass) ? $this->authPass : NULL;
  }

  /**
   * @param mixed $authPass
   */
  public function setAuthPass($authPass) {
    $this->authPass = $authPass;
  }

  /**
   * @return mixed
   */
  public function getAuthUser() {
    return !empty($this->authUser) ? $this->authUser : NULL;
  }

  /**
   * @param mixed $authUser
   */
  public function setAuthUser($authUser) {
    $this->authUser = $authUser;
  }

  /**
   * @return mixed
   */
  public function getAdditionalIssues() {
    return !empty($this->additionalIssues) ? $this->additionalIssues : array();
  }

  /**
   * @param mixed $additionalIssues
   */
  public function setAdditionalIssues($additionalIssues) {
    // @todo format of these issues??
    $this->additionalIssues = array_merge($this->getAdditionalIssues(), $additionalIssues);
  }

  /**
   * Compare the current core version with the latest core version.
   *
   * @return bool
   */
  public function compareCoreVersion() {
    return $this->getCoreVersion() == $this->getLatestCoreVersion();
  }

}