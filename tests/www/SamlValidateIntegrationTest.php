<?php
/**
 * Created by PhpStorm.
 * User: patrick
 * Date: 7/15/19
 * Time: 6:33 PM
 */

namespace Simplesamlphp\Casserver;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class SamlValidateIntegrationTest extends \PHPUnit\Framework\TestCase
{

    private static $LOGIN_URL = 'http://localhost:8732/module.php/casserver/login.php';
    private static $SAMLVALIDATE_URL = 'http://localhost:8732/module.php/casserver/samlvalidate.php';

    public function testSamlValidate()
    {
        // break this into a helper method
        $service_url = 'http://host1.domain:1234/path1';
        $client = new Client();
        // Use cookies Jar to store auth session cookies
        $jar = new CookieJar;
        // Setup authenticated cookies
        $this->authenticate($jar);
        $response = $client->get(
            self::$LOGIN_URL,
            [
                'query' => ['service' => $service_url],
                'cookies' => $jar,
                'allow_redirects' => false, // Disable redirects since the service url can't be redirected to
            ]
        );
        $this->assertEquals(302, $response->getStatusCode());

        $this->assertStringStartsWith(
            $service_url . '?ticket=ST-',
            $response->getHeader('Location')[0],
            'Ticket should be part of the redirect.'
        );

        $location =  $response->getHeader('Location')[0];
        $matches = [];
        $this->assertEquals(1, preg_match('@ticket=(.*)@', $location, $matches));
        $ticket = $matches[1];
        $soapRequest = <<<SOAP
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
	<SOAP-ENV:Header/>
	<SOAP-ENV:Body>
		<samlp:Request xmlns:samlp="urn:oasis:names:tc:SAML:1.0:protocol" MajorVersion="1" MinorVersion="1" RequestID="_192.168.16.51.1024506224022" IssueInstant="2002-06-19T17:03:44.022Z">
			<samlp:AssertionArtifact>$ticket</samlp:AssertionArtifact>
		</samlp:Request>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
SOAP;

        $response = $client->post(
            self::$SAMLVALIDATE_URL,
            [
                'query' => ['TARGET' => $service_url],
                'body' => $soapRequest

            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('testuser@example.com</NameIdentifier>', $response->getBody()->getContents());

    }

    /**
     * Sets up an authenticated session for the cookie $jar
     * @param CookieJar $jar
     */
    private function authenticate(CookieJar $jar)
    {
        $client = new Client();
        // Use cookies Jar to store auth session cookies
        $response = $client->get(
            self::$LOGIN_URL,
            [
                'cookies' => $jar
            ]
        );
        $this->assertEquals(200, $response->getStatusCode());
    }
}