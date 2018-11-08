<?php 

namespace Drupal\webform_geonames\libraries;

use GuzzleHttp\Exception\RequestException;

const API = 'http://api.geonames.org/';

class Geonames {
  
  private $geonamesLogin;
  private $api;

  public function __construct($geonamesLogin)
  {
    $this->geonamesLogin = $geonamesLogin;
    $this->api = API;
  }

  public function getCountryList() {

    $resource = $this->makeResource(null, 'countryInfoJSON');
    $response =  $this->makeRequest($resource);
    $options = $this->generateOptions('countryName', 'countryName', $response->geonames);

    $matchingOtions = $this->generateOptions('countryName', 'geonameId', $response->geonames);

    return ['options' => $options, 'matchingOptions' => $matchingOtions];

  }

  public function getStateListByCountryName($countryCode) {

    $resource = $this->makeResource(null, 'countryInfoJSON');
    $resource .= '&country=' . $countryCode;
    $country = $this->makeRequest($resource);
    $countryGeonameId = $this->getGeonameId($country);

    $stateOptions = $this->getStateList($countryGeonameId);
    
    return $stateOptions;

  }

  public function getStateList($countryGeonameId) {

    $resource = $this->makeResource($countryGeonameId, 'childrenJSON');
    $response = $this->makeRequest($resource);

    $options = $this->generateOptions('toponymName', 'toponymName', $response->geonames);
    $matchingOtions = $this->generateOptions('toponymName', 'geonameId', $response->geonames);

    return ['options' => $options, 'matchingOptions' => $matchingOtions];

  }

  public function getCityList($stateGeonameId) {

    $resource = $this->makeResource($stateGeonameId, 'childrenJSON');
    $response = $this->makeRequest($resource);
    $options = $this->generateOptions('toponymName', $response->geonames);

    return $options;

  }

  public function makeRequest ($resource) {
    
    $client = \Drupal::httpClient();
    $response = [];

    try {
      $request = $client->get($resource);
      $response = $request->getBody();
    } catch (RequestException $e) {
      drupal_set_message($e);
    }

    return json_decode($response);
  }

  public function makeResource ($geonameId, $resource) {
    
    $geonameIdParameter = $geonameId ? 'geonameId=' . $geonameId : '';
    $loginParameter = $this->geonamesLogin ? '&username=' . $this->geonamesLogin : '';
    $query = '?';

    $request = $this->api . $resource . $query . $geonameIdParameter . $loginParameter;

    return $request;

  }

  protected function generateOptions($key, $value, array $data) {

    $options = array_map(function($item) use ($key, $value) {
      
      $option[$item->{$key}] = $item->{$value};
      return $option;

    }, $data);

    return $options;

  }

  protected function getGeonameId($geonames) {

    return $geonames->geonames[0]->geonameId;

  }

}