<?php

namespace Simplesamlphp\Casserver;


use SimpleSAML\Module\casserver\Cas\Protocol\SamlValidateResponder;
use SimpleXMLElement;

class SamlValidateTest extends \PHPUnit_Framework_TestCase
{

    public function testSamlValidatXmlGeneration() {
        $serviceUrl = 'http://jellyfish.greatvalleyu.com:7777/ssomanager/c/SSB';
        $udcValue = '2F10C881AC7D55942329E149405DC2F5';
        $ticket = [
          'userName' => 'saisusr',
          'attributes' => [
            'UDC_IDENTIFIER' => [$udcValue]
          ],
            'service' => $serviceUrl,
        ];

        $samlValidate = new SamlValidateResponder();
        $xmlString = $samlValidate->convertToSaml($ticket);
//$this->assertEquals('a', $xmlString);
       // $xml = new SimpleXMLElement($xmlString, 0, false, 'SOAP-ENV', true);
        //var_dump($xml);
        //$body = $xml->children();
        //var_dump($body);
        $response = new SimpleXMLElement($xmlString);
        $this->assertEquals($serviceUrl, $response->attributes()->Recipient);
        $this->assertEquals('samlp:Success', $response->Status->StatusCode->attributes()->Value);
        $this->assertEquals('localhost', $response->Assertion->attributes()->Issuer);
        $this->assertEquals($serviceUrl, $response->Assertion->Conditions->AudienceRestrictionCondition->Audience);
        $attributeStatement= $response->Assertion->AttributeStatement;
        $this->assertEquals('saisusr', $attributeStatement->Subject->NameIdentifier);
        $this->assertEquals('urn:oasis:names:tc:SAML:1.0:cm:artifact', $attributeStatement->Subject->SubjectConfirmation->ConfirmationMethod);

        $attribute = $attributeStatement->Attribute;
        $this->assertEquals('UDC_IDENTIFIER', $attribute->attributes()->AttributeName);
        $this->assertEquals('http://www.ja-sig.org/products/cas/', $attribute->attributes()->AttributeNamespace);
        $this->assertEquals($udcValue, $attribute->AttributeValue);

        $asSoap = $samlValidate->wrapInSoap($xmlString);
        echo $asSoap;
    }

}