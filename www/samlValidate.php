<?php


use SAML2\DOMDocumentFactory;
use SAML2\Utils;
use SimpleSAML\Module\casserver\Cas\Protocol\SamlValidateResponder;

$target = $_GET['TARGET'];

// From SAML2\SOAP::recieve()
$postBody = file_get_contents('php://input');
if (empty($postBody)) {
    throw new \Exception('samlValidate expects a soap body.');
}
//$document = DOMDocumentFactory::fromString($postText);
//$xml = $document->firstChild;
//
//$results = Utils::xpQuery($xml, '/soap-env:Envelope/soap-env:Body/*[1]');
//$request = $results->saveXml();
$matches = [];
preg_match('|AssertionArtifact>(.*)<AssertionArtifact', $postBody, $matches);
if (count($matches) != 2 || empty($matches[1])) {
    throw new \Exception('Missing ticketId in AssertionArtifact');
}

$ticketId = $matches[1];

$ticketValidator = new \SimpleSAML\Module\casserver\Cas\TicketValidator();

$ticket = $ticketValidator->validateAndDeleteTicket($ticketId, $target);
if (!is_array($ticket) || empty($matches)) {
    throw new \Exception('Error loading ticket');
}
$samlValidator = new SamlValidateResponder();
$response = $samlValidator->convertToSaml($ticket);
$soap = $samlValidator->wrapInSoap($response);

echo $soap;