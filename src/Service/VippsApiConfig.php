<?php

declare(strict_types=1);

namespace Drupal\vipps_recurring_payments\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\vipps_recurring_payments\Form\SettingsForm;
use Drupal\Core\Url;

/**
 * Class VippsApiConfig. Stores all config data and functions.
 *
 * @package Drupal\vipps_recurring_payments\Service
 */
class VippsApiConfig {

  /**
   * Access token path.
   */
  private const ACCESS_TOKEN_PATH = '/accesstoken/get';

  /**
   * Draft agreement path.
   */
  private const DRAFT_AGREEMENT_PATH = '/recurring/v3/agreements';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $configFactory;

  /**
   * MSN.
   *
   * @var string
   */
  protected $msn;

  /**
   * Subscription key.
   *
   * @var string
   */
  protected $subscriptionKey;

  /**
   * Client id.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * Test mode.
   *
   * @var bool
   */
  protected $testMode;

  /**
   * Merchant agreement URL.
   *
   * @var string
   */
  private $merchantAgreementUrl;

  /**
   * VippsApiConfig constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory->getEditable(SettingsForm::SETTINGS);
    $this->initializeAttributes();
  }

  /**
   * Get MSN.
   *
   * @return string
   *   MSN.
   */
  public function getMsn():string {
    return $this->msn;
  }

  /**
   * Get subscription key.
   *
   * @return string
   *   Subscription key.
   */
  public function getSubscriptionKey():string {
    return $this->subscriptionKey;
  }

  /**
   * Get client id.
   *
   * @return string
   *   Client id.
   */
  public function getClientId():string {
    return $this->clientId;
  }

  /**
   * Get client secret.
   *
   * @return string
   *   Client secret.
   */
  public function getClientSecret():string {
    return $this->clientSecret;
  }

  /**
   * Get base URL.
   *
   * @return string
   *   URL.
   */
  public function getBaseUrl():string {
    return $this->isTest() ? 'https://apitest.vipps.no' : 'https://api.vipps.no';
  }

  /**
   * Get merchant redirect URL.
   *
   * @param array $params
   *   Parameters array.
   *
   * @return string
   *   URL.
   */
  public function getMerchantRedirectUrl(array $params = []):string {
    $urlObject = Url::fromRoute('vipps_recurring_payments.confirm_agreement', $params, ['absolute' => TRUE]);
    return $urlObject->toString();
  }

  /**
   * Get merchant agreement URL.
   *
   * @param array $params
   *   Parameters array.
   *
   * @return string
   *   URL.
   */
  public function getMerchantAgreementUrl(array $params = []):string {
    return $this->merchantAgreementUrl;
  }

  /**
   * Get the access token URL.
   *
   * @return string
   *   URL.
   */
  public function getAccessTokenRequestUrl():string {
    return $this->generateUrl(self::ACCESS_TOKEN_PATH);
  }

  /**
   * Get the URL to draft the agreement.
   *
   * @return string
   *   URL.
   */
  public function getDraftAgreementRequestUrl():string {
    return $this->generateUrl(self::DRAFT_AGREEMENT_PATH);
  }

  /**
   * Get the charge URL.
   *
   * @param string $orderId
   *   The order id.
   *
   * @return string
   *   URL.
   */
  public function getCreateChargeUrl(string $orderId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s/charges", $orderId));
  }

  /**
   * Get the retrieve agreement URL.
   *
   * @param string $agreementId
   *   Agreement id.
   *
   * @return string
   *   URL.
   */
  public function getRetrieveAgreementUrl(string $agreementId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s", $agreementId));
  }

  /**
   * Get the retrieve charges URL.
   *
   * @param string $agreementId
   *   Agreement id.
   *
   * @return string
   *   URL.
   */
  public function getRetrieveChargesUrl(string $agreementId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s/charges", $agreementId));
  }

  /**
   * Get the URL to update agreement.
   *
   * @param string $agreementId
   *   Agreement id.
   *
   * @return string
   *   URL.
   */
  public function getUpdateAgreementUrl(string $agreementId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s", $agreementId));
  }

  /**
   * Get the charge URL.
   *
   * @param string $agreementId
   *   Agreement id.
   * @param string $chargeId
   *   Charge id.
   *
   * @return string
   *   URL.
   */
  public function getChargeUrl(string $agreementId, string $chargeId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s/charges/%s", $agreementId, $chargeId));
  }

  /**
   * Get the refund URL.
   *
   * @param string $agreementId
   *   Agreement id.
   * @param string $chargeId
   *   Charge id.
   *
   * @return string
   *   URL.
   */
  public function getRefundUrl(string $agreementId, string $chargeId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s/charges/%s/refund", $agreementId, $chargeId));
  }

  /**
   * Get the capture URL.
   *
   * @param string $agreementId
   *   Agreement id.
   * @param string $chargeId
   *   Charge id.
   *
   * @return string
   *   URL.
   */
  public function getCaptureUrl(string $agreementId, string $chargeId):string {
    return $this->generateUrl(sprintf("/recurring/v3/agreements/%s/charges/%s/capture", $agreementId, $chargeId));
  }

  /**
   * Initialize the attributes function.
   */
  private function initializeAttributes():void {
    $rowData = $this->configFactory->getRawData();

    $this->msn = null;
    $this->subscriptionKey = null;
    $this->clientId = null;
    $this->clientSecret = null;
    $this->merchantAgreementUrl = null;
    $this->testMode = null;

    if (isset($rowData['msn'])) {
      $this->msn = $rowData['msn'];
    }
    if (isset($rowData['subscription_key'])) {
      $this->subscriptionKey = $rowData['subscription_key'];
    }
    if (isset($rowData['client_id'])) {
      $this->clientId = $rowData['client_id'];
    }
    if (isset($rowData['client_secret'])) {
      $this->clientSecret = $rowData['client_secret'];
    }
    if (isset($rowData['MerchantAgreementUrl'])) {
      $this->merchantAgreementUrl = $rowData['MerchantAgreementUrl'];
    }
    if (isset($rowData['test_mode'])) {
      $this->testMode = $rowData['test_mode'];
    }

    $this->msn = null;
    $this->subscriptionKey = null;
    $this->clientId = null;
    $this->clientSecret = null;

    if ($this->isTest()) {
      if (isset($rowData['test_msn'])) {
        $this->msn = $rowData['test_msn'];
      }
      if (isset($rowData['test_subscription_key'])) {
        $this->subscriptionKey = $rowData['test_subscription_key'];
      }
      if (isset($rowData['test_client_id'])) {
        $this->clientId = $rowData['test_client_id'];
      }
      if (isset($rowData['test_client_secret'])) {
        $this->clientSecret = $rowData['test_client_secret'];
      }
    }
  }

  /**
   * Generate the URL to the given path.
   *
   * @param string $path
   *   The path to generate.
   *
   * @return string
   *   The generated path.
   */
  private function generateUrl(string $path):string {
    return $this->getBaseUrl() . $path;
  }

  /**
   * Check is test.
   *
   * @return bool
   *   True of false.
   */
  private function isTest():bool {
    return boolval($this->testMode);
  }

}
